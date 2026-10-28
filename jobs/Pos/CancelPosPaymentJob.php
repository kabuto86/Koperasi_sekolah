<?php
// Batal bayaran QR jualan POS (vendor/pos/cancel-payment): `pending_payment` sahaja → `cancelled`, tahanan stok & guna promosi
// dilepas (CustomerOrders::releaseHolds), bil pending → failed. Bil ditanya semula ke gateway dahulu — jika pembeli sudah
// membayar, jualan diselesaikan (bukan dibatal) dan pos.payment_not_pending dipulangkan.
class CancelPosPaymentJob
{
    private $vendorId;
    private $cooperative;
    private $cashier;
    private $orderId;
    private $recheck;

    // $recheck = false hanya bila bil gagal dicipta (PosCheckoutJob)
    public function __construct($vendorId, array $cooperative, array $cashier, $orderId, $recheck = true)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->cashier = $cashier;
        $this->orderId = (int) $orderId;
        $this->recheck = (bool) $recheck;
    }

    public function run()
    {
        $coopId = (int) $this->cooperative['id'];
        $order = PosSales::require($this->vendorId, $coopId, $this->orderId);
        if ($this->recheck && $order['status'] === 'pending_payment') {
            PosSales::recheckPayment($order);
        }
        Database::beginTransaction();
        try {
            $order = PosSales::require($this->vendorId, $coopId, $this->orderId, true);
            if ($order['status'] !== 'pending_payment') {
                throw new ValidationException(t('pos.payment_not_pending'));
            }
            CustomerOrders::releaseHolds($order);
            Database::execute(
                "UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), expires_at = NULL WHERE id = ? AND vendor_id = ?",
                [(int) $order['id'], $this->vendorId]
            );
            Database::execute(
                "UPDATE payments SET status = 'failed' WHERE vendor_id = ? AND order_id = ? AND status = 'pending'",
                [$this->vendorId, (int) $order['id']]
            );
            CustomerOrders::logStatus($order, 'cancelled', 'vendor_user', (int) $this->cashier['id'],
                Lang::getFor($this->cooperative['default_language'], 'pos.payment_cancelled'));
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return ['order_id' => (int) $order['id']];
    }
}
