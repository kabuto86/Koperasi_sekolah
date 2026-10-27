<?php
// Ibu bapa batalkan pesanan (PELAN 18.12 store/orders/cancel): hanya pending_payment / unpaid_cash.
// Dalam transaksi: kunci pesanan → lepas tahanan stok, slot & promosi → status cancelled + log.
class CancelOrderJob
{
    private $customerId;
    private $cooperative;
    private $orderId;

    public function __construct($customerId, array $cooperative, $orderId)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->orderId = (int) $orderId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $order = CustomerOrders::require($this->customerId, $this->cooperative, $this->orderId, true);
            if (!in_array($order['status'], CustomerOrders::CANCELLABLE, true)) {
                throw new ValidationException(t('store.order.cannot_cancel'));
            }
            CustomerOrders::releaseHolds($order);
            Database::execute(
                "UPDATE orders SET status = 'cancelled', cancelled_at = NOW() WHERE id = ? AND vendor_id = ?",
                [(int) $order['id'], (int) $order['vendor_id']]
            );
            // Nota untuk kakitangan koperasi — bahasa koperasi
            $note = Lang::getFor($this->cooperative['default_language'], 'store.order.cancelled_by_parent');
            CustomerOrders::logStatus($order, 'cancelled', 'customer', $this->customerId, $note);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return (new GetCustomerOrderJob($this->customerId, $this->cooperative, $this->orderId))->run();
    }
}
