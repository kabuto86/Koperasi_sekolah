<?php
// Stok masuk tanpa PO (hadiah, pindahan): { product_id, variant_id, qty ≥ 1, note } → pergerakan 'restock'.
class StockInJob
{
    const MAX_QTY = 100000;

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
        $productId = (int) ($this->input['product_id'] ?? 0);
        $variantId = (int) ($this->input['variant_id'] ?? 0);
        if (InventoryRows::find($this->vendorId, $this->cooperativeId, $productId, $variantId) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('qty')->integer('qty', 1, self::MAX_QTY)->maxLength('note', 200);
        if ($v->fails() && isset($v->errors()['qty'])) {
            throw new ValidationException(t('inventory.qty_positive'), ['qty' => t('inventory.qty_positive')]);
        }
        ValidationException::throwIfFails($v);
        $qty = (int) $this->input['qty'];
        $note = trim((string) ($this->input['note'] ?? ''));

        Database::beginTransaction();
        try {
            StockLedger::move($this->vendorId, $this->cooperativeId, $productId, $variantId, 'restock', $qty, $this->actorId, null, null, $note);
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        $row = InventoryRows::find($this->vendorId, $this->cooperativeId, $productId, $variantId);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'stock_in', 'product', $productId,
            $row['product_name'] . ($row['variant_label'] ? ' — ' . $row['variant_label'] : '') . ' +' . $qty))->run();
        return $row;
    }
}
