<?php
// Bayar semula pesanan online (PELAN 18.12 store/orders/pay). Hanya pending_payment yang belum luput —
// bil gateway dicipta (atau bil pending terkini diguna semula) oleh StartOnlinePaymentJob (B8).
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

    // { order_id, payment: { reference, gateway_name, amount, mode, redirect_url } }
    public function run()
    {
        return (new StartOnlinePaymentJob($this->customerId, $this->cooperative, $this->orderId))->run();
    }
}
