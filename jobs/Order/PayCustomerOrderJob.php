<?php
// Bayar semula pesanan online (PELAN 18.12 store/orders/pay). Hanya pending_payment.
// B7: belum ada driver gateway — bayaran online dibuka dalam B8 (StartOnlinePaymentJob).
class PayCustomerOrderJob
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
        $order = CustomerOrders::require($this->customerId, $this->cooperative, $this->orderId);
        if ($order['status'] !== 'pending_payment') {
            throw new ValidationException(t('store.order.not_payable'));
        }
        throw new ValidationException(t('store.checkout.method_unavailable'));
    }
}
