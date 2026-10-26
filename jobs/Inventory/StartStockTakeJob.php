<?php
// Mula stock take: salin baki semasa (qty_system) setiap baris stok produk aktif. Satu stock take terbuka setiap koperasi.
class StartStockTakeJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;

    public function __construct($vendorId, $cooperativeId, $actorId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            // Kunci koperasi: dua permintaan serentak tidak boleh membuka dua stock take
            Database::fetchValue("SELECT id FROM cooperatives WHERE vendor_id = ? AND id = ? FOR UPDATE", [$this->vendorId, $this->cooperativeId]);
            $open = Database::fetchValue(
                "SELECT 1 FROM stock_takes WHERE vendor_id = ? AND cooperative_id = ? AND status = 'open'",
                [$this->vendorId, $this->cooperativeId]
            );
            if ($open) {
                throw new ValidationException(t('stocktake.already_open'));
            }
            $id = Database::insert(
                "INSERT INTO stock_takes (vendor_id, cooperative_id, status, started_by, started_at) VALUES (?, ?, 'open', ?, NOW())",
                [$this->vendorId, $this->cooperativeId, $this->actorId]
            );
            Database::execute(
                "INSERT INTO stock_take_items (vendor_id, stock_take_id, product_id, variant_id, qty_system)
                 SELECT s.vendor_id, ?, s.product_id, s.variant_id, s.qty_on_hand
                   FROM stock_levels s JOIN products p ON p.vendor_id = s.vendor_id AND p.id = s.product_id
                  WHERE s.vendor_id = ? AND s.cooperative_id = ? AND p.cooperative_id = s.cooperative_id AND p.is_active = 1",
                [$id, $this->vendorId, $this->cooperativeId]
            );
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        return StockTakes::summary($this->vendorId, $this->cooperativeId, $id);
    }
}
