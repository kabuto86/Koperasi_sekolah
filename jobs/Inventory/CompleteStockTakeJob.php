<?php
// Selesai stock take: item yang dikira → pergerakan 'stocktake' sebanyak (kiraan − baki semasa mula). Belum dikira → tidak diubah.
// Jualan semasa kiraan sudah direkod sendiri, jadi perbezaan dikira terhadap qty_system (salinan semasa mula).
class CompleteStockTakeJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;
    private $stockTakeId;

    public function __construct($vendorId, $cooperativeId, $actorId, $stockTakeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
        $this->stockTakeId = (int) $stockTakeId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $take = StockTakes::find($this->vendorId, $this->cooperativeId, $this->stockTakeId, true);
            if ($take === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($take['status'] !== 'open') {
                throw new ValidationException(t('stocktake.not_open'));
            }
            $items = Database::fetchAll(
                "SELECT product_id, variant_id, qty_system, qty_counted FROM stock_take_items
                  WHERE vendor_id = ? AND stock_take_id = ? AND qty_counted IS NOT NULL AND qty_counted <> qty_system",
                [$this->vendorId, $this->stockTakeId]
            );
            foreach ($items as $item) {
                $this->applyVariance($item);
            }
            Database::execute(
                "UPDATE stock_takes SET status = 'completed', completed_at = NOW() WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                [$this->vendorId, $this->cooperativeId, $this->stockTakeId]
            );
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'stocktake_complete', 'stock_take',
            $this->stockTakeId, count($items)))->run();
        return StockTakes::summary($this->vendorId, $this->cooperativeId, $this->stockTakeId);
    }

    // Baki tidak boleh jatuh bawah sifar (jualan semasa kiraan melebihi kiraan)
    private function applyVariance($item)
    {
        $level = StockLedger::lock($this->vendorId, $this->cooperativeId, $item['product_id'], $item['variant_id']);
        $change = (int) $item['qty_counted'] - (int) $item['qty_system'];
        $change = max($change, -(int) $level['qty_on_hand']);
        if ($change !== 0) {
            StockLedger::move($this->vendorId, $this->cooperativeId, $item['product_id'], $item['variant_id'], 'stocktake', $change,
                $this->actorId, 'stock_take', $this->stockTakeId);
        }
    }
}
