<?php
// Butiran pesanan ibu bapa (PELAN 18.12 store/orders/get → StoreOrderDetail). Pesanan orang lain / koperasi lain → 404.
class GetCustomerOrderJob
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
        return CustomerOrders::detail($order, $this->cooperative);
    }
}
