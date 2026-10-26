<?php
// Butiran PO + item (PELAN 18.8). null = tiada / koperasi atau vendor lain.
class GetPurchaseOrderJob
{
    private $vendorId;
    private $cooperativeId;
    private $purchaseOrderId;

    public function __construct($vendorId, $cooperativeId, $purchaseOrderId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->purchaseOrderId = (int) $purchaseOrderId;
    }

    public function run()
    {
        return PurchaseOrders::detail($this->vendorId, $this->cooperativeId, $this->purchaseOrderId);
    }
}
