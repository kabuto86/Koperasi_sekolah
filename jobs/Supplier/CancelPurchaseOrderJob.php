<?php
// Batal PO { id } — draf, atau dipesan tanpa barang diterima.
class CancelPurchaseOrderJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;
    private $purchaseOrderId;

    public function __construct($vendorId, $cooperativeId, $actorId, $purchaseOrderId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
        $this->purchaseOrderId = (int) $purchaseOrderId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $po = PurchaseOrders::find($this->vendorId, $this->cooperativeId, $this->purchaseOrderId, true);
            if ($po === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            $received = (int) Database::fetchValue(
                "SELECT COALESCE(SUM(qty_received), 0) FROM purchase_order_items WHERE vendor_id = ? AND purchase_order_id = ?",
                [$this->vendorId, $this->purchaseOrderId]
            );
            if (!in_array($po['status'], ['draft', 'ordered'], true) || $received > 0) {
                throw new ValidationException(t('po.cannot_cancel'));
            }
            Database::execute(
                "UPDATE purchase_orders SET status = 'cancelled' WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                [$this->vendorId, $this->cooperativeId, $this->purchaseOrderId]
            );
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'po_cancel', 'purchase_order',
            $this->purchaseOrderId, $po['po_no']))->run();
        return PurchaseOrders::detail($this->vendorId, $this->cooperativeId, $this->purchaseOrderId);
    }
}
