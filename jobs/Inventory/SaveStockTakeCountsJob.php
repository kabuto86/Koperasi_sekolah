<?php
// Simpan kiraan { id, counts: [{ item_id, qty_counted }] } — qty_counted null/'' = belum dikira. Stock take terbuka sahaja.
class SaveStockTakeCountsJob
{
    private $vendorId;
    private $cooperativeId;
    private $input;

    public function __construct($vendorId, $cooperativeId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $counts = is_array($this->input['counts'] ?? null) ? $this->input['counts'] : [];
        Database::beginTransaction();
        try {
            $take = StockTakes::find($this->vendorId, $this->cooperativeId, $id, true);
            if ($take === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($take['status'] !== 'open') {
                throw new ValidationException(t('stocktake.not_open'));
            }
            foreach ($counts as $count) {
                $this->saveCount($id, is_array($count) ? $count : []);
            }
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        return StockTakes::summary($this->vendorId, $this->cooperativeId, $id);
    }

    // Item asing (stock take lain) diabaikan oleh tapisan stock_take_id + vendor_id
    private function saveCount($stockTakeId, array $count)
    {
        $value = $count['qty_counted'] ?? null;
        if ($value === null || $value === '') {
            $qty = null;
        } elseif (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value))) {
            $qty = min((int) $value, StockInJob::MAX_QTY);
        } else {
            throw new ValidationException(t('error.integer'));
        }
        Database::execute(
            "UPDATE stock_take_items SET qty_counted = ?, variance = IF(? IS NULL, NULL, ? - qty_system)
              WHERE vendor_id = ? AND stock_take_id = ? AND id = ?",
            [$qty, $qty, $qty, $this->vendorId, $stockTakeId, (int) ($count['item_id'] ?? 0)]
        );
    }
}
