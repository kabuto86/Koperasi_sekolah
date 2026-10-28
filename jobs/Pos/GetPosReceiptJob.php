<?php
// Resit jualan POS (vendor/pos/receipt) — jualan POS koperasi ini sahaja (pesanan store → 404).
class GetPosReceiptJob
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
        return PosSales::receipt(PosSales::require($this->vendorId, $this->cooperativeId, $this->orderId));
    }
}
