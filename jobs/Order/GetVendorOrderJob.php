<?php
// Butiran pesanan untuk panel vendor (PELAN 18.9 vendor/orders/get) — termasuk jualan POS koperasi ini.
class GetVendorOrderJob
{
    private $vendorId;
    private $cooperativeId;
    private $orderId;

    public function __construct($vendorId, $cooperativeId, $orderId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->orderId = (int) $orderId;
    }

    public function run()
    {
        return VendorOrders::detail(VendorOrders::require($this->vendorId, $this->cooperativeId, $this->orderId));
    }
}
