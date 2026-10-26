<?php
// Pelarasan stok: { product_id, variant_id, new_qty (kiraan sebenar), reason, note, min_stock } → pergerakan 'adjust'.
// Tidak boleh di bawah kuantiti ditahan (pesanan belum diambil). min_stock boleh dikemas kini sekali.
class AdjustStockJob
{
    const REASONS = ['count_correction', 'damaged', 'lost', 'found', 'other'];

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
        $v->required('new_qty')->integer('new_qty', 0, StockInJob::MAX_QTY)
          ->required('reason')->inList('reason', self::REASONS)
          ->maxLength('note', 200)
          ->integer('min_stock', 0, StockInJob::MAX_QTY);
        ValidationException::throwIfFails($v);

        $newQty = (int) $this->input['new_qty'];
        $minStock = $this->input['min_stock'] ?? null;
        $note = t('inventory.reason.' . $this->input['reason']);
        if (trim((string) ($this->input['note'] ?? '')) !== '') {
            $note .= ' — ' . trim((string) $this->input['note']);
        }

        Database::beginTransaction();
        try {
            $level = StockLedger::lock($this->vendorId, $this->cooperativeId, $productId, $variantId);
            if ($newQty < (int) $level['qty_reserved']) {
                $message = t('inventory.below_reserved', ['n' => (int) $level['qty_reserved']]);
                throw new ValidationException(t('error.check_form'), ['new_qty' => $message]);
            }
            $change = $newQty - (int) $level['qty_on_hand'];
            if ($change !== 0) {
                StockLedger::move($this->vendorId, $this->cooperativeId, $productId, $variantId, 'adjust', $change, $this->actorId, null, null, $note);
            }
            if ($minStock !== null && $minStock !== '') {
                Database::execute(
                    "UPDATE stock_levels SET min_stock = ? WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ?",
                    [(int) $minStock, $this->vendorId, $this->cooperativeId, $productId, $variantId]
                );
            }
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        $row = InventoryRows::find($this->vendorId, $this->cooperativeId, $productId, $variantId);
        if ($change !== 0) {
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'stock_adjust', 'product', $productId,
                $row['product_name'] . ($row['variant_label'] ? ' — ' . $row['variant_label'] : '') . ' ' . sprintf('%+d', $change) . ' (' . $note . ')'))->run();
        }
        return $row;
    }
}
