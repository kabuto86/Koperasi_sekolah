<?php
// Terima barang PO { id, lines: [{ item_id, qty }] } — separa dibenarkan, melebihi baki ditolak.
// Stok masuk automatik (pergerakan 'po_receive') dalam satu transaksi; status → 'partial' / 'received'.
class ReceivePurchaseOrderJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $lines = $this->cleanLines();
        $units = 0;
        Database::beginTransaction();
        try {
            $po = PurchaseOrders::find($this->vendorId, $this->cooperativeId, $id, true);
            if ($po === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if (!in_array($po['status'], ['ordered', 'partial'], true)) {
                throw new ValidationException(t('po.cannot_receive'));
            }
            if (!$lines) {
                throw new ValidationException(t('po.receive_nothing'));
            }
            $items = $this->lockItems($id);
            foreach ($lines as $itemId => $qty) {
                $item = $items[$itemId] ?? null;
                if ($item === null || $qty > (int) $item['qty_ordered'] - (int) $item['qty_received']) {
                    throw new ValidationException(t('po.receive_too_many'));
                }
                Database::execute(
                    "UPDATE purchase_order_items SET qty_received = qty_received + ? WHERE vendor_id = ? AND purchase_order_id = ? AND id = ?",
                    [$qty, $this->vendorId, $id, $itemId]
                );
                StockLedger::move($this->vendorId, $this->cooperativeId, $item['product_id'], $item['variant_id'], 'po_receive', $qty,
                    $this->actorId, 'purchase_order', $id, $po['po_no']);
                $units += $qty;
            }
            $this->updateStatus($id);
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'po_receive', 'purchase_order', $id,
            $po['po_no'] . ' +' . $units))->run();
        return PurchaseOrders::detail($this->vendorId, $this->cooperativeId, $id);
    }

    // [item_id => qty] — baris kuantiti 0 diabaikan; item sama dijumlahkan
    private function cleanLines()
    {
        $result = [];
        $lines = is_array($this->input['lines'] ?? null) ? $this->input['lines'] : [];
        foreach ($lines as $line) {
            $qty = is_array($line) ? ($line['qty'] ?? 0) : 0;
            if (!(is_int($qty) || (is_string($qty) && preg_match('/^\d+$/', $qty)))) {
                throw new ValidationException(t('error.integer'));
            }
            if ((int) $qty > 0) {
                $itemId = (int) ($line['item_id'] ?? 0);
                $result[$itemId] = ($result[$itemId] ?? 0) + (int) $qty;
            }
        }
        return $result;
    }

    private function lockItems($purchaseOrderId)
    {
        $rows = Database::fetchAll(
            "SELECT id, product_id, variant_id, qty_ordered, qty_received FROM purchase_order_items
              WHERE vendor_id = ? AND purchase_order_id = ? FOR UPDATE",
            [$this->vendorId, $purchaseOrderId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = $row;
        }
        return $result;
    }

    private function updateStatus($purchaseOrderId)
    {
        $pending = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM purchase_order_items WHERE vendor_id = ? AND purchase_order_id = ? AND qty_received < qty_ordered",
            [$this->vendorId, $purchaseOrderId]
        );
        Database::execute(
            "UPDATE purchase_orders SET status = ?, received_at = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [$pending === 0 ? 'received' : 'partial', $pending === 0 ? date('Y-m-d H:i:s') : null, $this->vendorId, $this->cooperativeId, $purchaseOrderId]
        );
    }
}
