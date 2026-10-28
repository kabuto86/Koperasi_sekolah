<?php
// Terima bayaran tunai di kaunter sebelum serah (PELAN 18.9 vendor/handover/receive-cash). amount_received dalam sen ≥ jumlah.
// Dalam transaksi: kunci pesanan → baris payments (cash, paid, received_by juruwang — muncul dalam vendor/payments/list)
// → orders.paid_at, amount_received, change_given; expires_at dikosongkan (tidak diluputkan cron lagi). Status pesanan kekal.
class ReceiveCashPaymentJob
{
    const PAYABLE = ['unpaid_cash', 'preparing', 'ready', 'partially_collected'];

    private $vendorId;
    private $cooperative;
    private $userId;
    private $orderId;
    private $amountReceived;

    public function __construct($vendorId, array $cooperative, $userId, $orderId, $amountReceived)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->orderId = (int) $orderId;
        $this->amountReceived = $amountReceived;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], $this->orderId, true);
            $received = $this->validate($order);
            $total = (int) $order['total'];
            Database::insert(
                "INSERT INTO payments (vendor_id, cooperative_id, order_id, method, amount, status, verified_at, received_by)
                 VALUES (?, ?, ?, 'cash', ?, 'paid', NOW(), ?)",
                [$this->vendorId, (int) $this->cooperative['id'], (int) $order['id'], $total, $this->userId]
            );
            Database::execute(
                "UPDATE orders SET paid_at = NOW(), amount_received = ?, change_given = ?, expires_at = NULL WHERE id = ? AND vendor_id = ?",
                [$received, $received - $total, (int) $order['id'], $this->vendorId]
            );
            $note = Lang::getFor($this->cooperative['default_language'], 'handover.cash_log', ['amount' => Money::format($total)]);
            CustomerOrders::logStatus($order, $order['status'], 'vendor_user', $this->userId, $note);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return [
            'change' => $received - $total,
            'order'  => (new GetVendorOrderJob($this->vendorId, $this->cooperative['id'], $this->orderId))->run(),
        ];
    }

    private function validate(array $order)
    {
        if ($order['paid_at'] !== null) {
            throw new ValidationException(t('handover.already_paid'));
        }
        if ($order['channel'] !== 'online' || $order['payment_method'] !== 'cash' || !in_array($order['status'], self::PAYABLE, true)) {
            throw new ValidationException(t('error.invalid'));
        }
        $received = filter_var($this->amountReceived, FILTER_VALIDATE_INT);
        if ($received === false || $received < (int) $order['total'] || $received > 100000000) {
            throw new ValidationException(t('handover.cash_not_enough'), ['amount_received' => t('handover.cash_not_enough')]);
        }
        return $received;
    }
}
