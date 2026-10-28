<?php
// Ibu bapa kembali dari gateway (store/orders/check-payment). Parameter redirect gateway TIDAK dibaca — pelayan
// bertanya sendiri ke API gateway untuk bil pending pesanan ini (PaymentSettlement). Pulangkan StoreOrderDetail terkini.
class CheckOrderPaymentJob
{
    const MAX_BILLS = 3;

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
        if ($order['status'] === 'pending_payment') {
            $this->recheck($order);
        }
        return (new GetCustomerOrderJob($this->customerId, $this->cooperative, $this->orderId))->run();
    }

    private function recheck(array $order)
    {
        $payments = Database::fetchAll(
            "SELECT * FROM payments WHERE vendor_id = ? AND cooperative_id = ? AND order_id = ? AND method = 'online' AND status = 'pending'
              ORDER BY id DESC LIMIT " . self::MAX_BILLS,
            [(int) $order['vendor_id'], (int) $order['cooperative_id'], (int) $order['id']]
        );
        foreach ($payments as $payment) {
            $gateway = CooperativeGateways::find($order['vendor_id'], $order['cooperative_id'], $payment['gateway_code']);
            if ($gateway === null) {
                continue;
            }
            try {
                if (PaymentSettlement::recheck($payment, CooperativeGateways::driver($gateway)) === 'paid') {
                    return;
                }
            } catch (GatewayException $error) {
                // Gateway tidak dapat dihubungi — status kekal; panggilan balik / cron akan menyelesaikan
                error_log('[KoperasiKu] semak bayaran ' . $payment['gateway_ref'] . ': ' . $error->getMessage());
            }
        }
    }
}
