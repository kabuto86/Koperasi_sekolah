<?php
// Simpan PO draf { id?, supplier_id, note, items: [{ product_id, variant_id, qty_ordered, unit_cost }], submit } (PELAN 18.8).
// Hanya draf boleh disunting; submit → 'ordered'. No. PO = PO-{tahun}-{0001} unik dalam koperasi. Jumlah dikira di pelayan.
class SavePurchaseOrderJob
{
    const MAX_QTY = 100000;
    const MAX_COST = 10000000;

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
        $data = $this->validate();
        $submit = !empty($this->input['submit']);

        Database::beginTransaction();
        try {
            if ($id > 0) {
                $po = PurchaseOrders::find($this->vendorId, $this->cooperativeId, $id, true);
                if ($po === null) {
                    throw new ValidationException(t('error.not_found'), [], 404);
                }
                if ($po['status'] !== 'draft') {
                    throw new ValidationException(t('po.not_editable'));
                }
                Database::execute(
                    "UPDATE purchase_orders SET supplier_id = ?, note = ?, total = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                    [$data['supplier_id'], $data['note'], $data['total'], $this->vendorId, $this->cooperativeId, $id]
                );
                Database::execute("DELETE FROM purchase_order_items WHERE vendor_id = ? AND purchase_order_id = ?", [$this->vendorId, $id]);
            } else {
                $id = Database::insert(
                    "INSERT INTO purchase_orders (vendor_id, cooperative_id, supplier_id, po_no, status, total, note, created_by)
                     VALUES (?, ?, ?, ?, 'draft', ?, ?, ?)",
                    [$this->vendorId, $this->cooperativeId, $data['supplier_id'], $this->nextNumber(), $data['total'], $data['note'], $this->actorId]
                );
            }
            foreach ($data['items'] as $item) {
                Database::insert(
                    "INSERT INTO purchase_order_items (vendor_id, purchase_order_id, product_id, variant_id, qty_ordered, unit_cost)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$this->vendorId, $id, $item['product_id'], $item['variant_id'], $item['qty_ordered'], $item['unit_cost']]
                );
            }
            if ($submit) {
                Database::execute(
                    "UPDATE purchase_orders SET status = 'ordered', ordered_at = NOW() WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                    [$this->vendorId, $this->cooperativeId, $id]
                );
            }
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        $detail = PurchaseOrders::detail($this->vendorId, $this->cooperativeId, $id);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'po_save', 'purchase_order', $id,
            $detail['po_no'] . ' (' . $detail['status'] . ')'))->run();
        return $detail;
    }

    private function validate()
    {
        $v = new Validator($this->input);
        $v->required('supplier_id')->maxLength('note', 255);
        $supplierId = (int) ($this->input['supplier_id'] ?? 0);
        if ($supplierId > 0 && !Database::fetchValue("SELECT 1 FROM suppliers WHERE vendor_id = ? AND id = ?", [$this->vendorId, $supplierId])) {
            $v->addError('supplier_id', t('error.invalid'));
        }
        $items = [];
        $list = is_array($this->input['items'] ?? null) ? $this->input['items'] : [];
        foreach ($list as $line) {
            $item = $this->cleanItem(is_array($line) ? $line : []);
            if ($item === false) {
                $v->addError('items', t('error.invalid'));
            } elseif ($item !== null) {
                $items[] = $item;
            }
        }
        if (!$items) {
            $v->addError('items', t('po.items_required'));
        }
        ValidationException::throwIfFails($v);

        $total = 0;
        foreach ($items as $item) {
            $total += $item['qty_ordered'] * $item['unit_cost'];
        }
        $note = trim((string) ($this->input['note'] ?? ''));
        return ['supplier_id' => $supplierId, 'note' => $note === '' ? null : $note, 'items' => $items, 'total' => $total];
    }

    // null = baris kosong (kuantiti 0) diabaikan · false = tidak sah (produk koperasi lain, varian salah, nombor salah)
    private function cleanItem(array $line)
    {
        $qty = $line['qty_ordered'] ?? 0;
        $cost = $line['unit_cost'] ?? 0;
        if (!self::isWhole($qty) || !self::isWhole($cost)) {
            return false;
        }
        if ((int) $qty === 0) {
            return null;
        }
        if ((int) $qty > self::MAX_QTY || (int) $cost > self::MAX_COST) {
            return false;
        }
        $product = ProductCatalog::find($this->vendorId, $this->cooperativeId, $line['product_id'] ?? 0);
        $variantId = (int) ($line['variant_id'] ?? 0);
        if ($product === null || ($variantId === 0) === (bool) $product['has_variants']) {
            return false;
        }
        if ($variantId > 0 && !Database::fetchValue(
            "SELECT 1 FROM product_variants WHERE vendor_id = ? AND product_id = ? AND id = ?",
            [$this->vendorId, (int) $product['id'], $variantId]
        )) {
            return false;
        }
        return ['product_id' => (int) $product['id'], 'variant_id' => $variantId, 'qty_ordered' => (int) $qty, 'unit_cost' => (int) $cost];
    }

    private static function isWhole($value)
    {
        return (is_int($value) && $value >= 0) || (is_string($value) && preg_match('/^\d+$/', $value));
    }

    // Nombor seterusnya dalam koperasi & tahun ini. Baris koperasi dikunci supaya dua PO serentak tidak dapat nombor sama
    private function nextNumber()
    {
        Database::fetchValue("SELECT id FROM cooperatives WHERE vendor_id = ? AND id = ? FOR UPDATE", [$this->vendorId, $this->cooperativeId]);
        $prefix = 'PO-' . date('Y') . '-';
        $last = Database::fetchValue(
            "SELECT MAX(CAST(SUBSTRING(po_no, ?) AS UNSIGNED)) FROM purchase_orders WHERE vendor_id = ? AND cooperative_id = ? AND po_no LIKE ?",
            [strlen($prefix) + 1, $this->vendorId, $this->cooperativeId, $prefix . '%']
        );
        return $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
    }
}
