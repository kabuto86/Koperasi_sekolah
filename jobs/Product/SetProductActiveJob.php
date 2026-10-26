<?php
// Aktif / nyahaktif produk { id, is_active } — produk tidak dipadam (sejarah pesanan & stok kekal).
class SetProductActiveJob
{
    private $vendorId;
    private $cooperativeId;
    private $productId;
    private $active;

    public function __construct($vendorId, $cooperativeId, $productId, $active)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->productId = (int) $productId;
        $this->active = (bool) $active;
    }

    public function run()
    {
        if (ProductCatalog::find($this->vendorId, $this->cooperativeId, $this->productId) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute(
            "UPDATE products SET is_active = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [$this->active ? 1 : 0, $this->vendorId, $this->cooperativeId, $this->productId]
        );
        $rows = ProductCatalog::summaries($this->vendorId, $this->cooperativeId, 'AND p.id = ?', [$this->productId]);
        return $rows[0];
    }
}
