<?php
// Pilihan produk/varian rata untuk PO (PELAN 18.8): [{ product_id, variant_id (0 = tiada), name, sku, cost_price }].
class ListProductOptionsJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT p.id AS product_id, COALESCE(v.id, 0) AS variant_id, p.name, v.label, COALESCE(v.sku, p.sku) AS sku, p.cost_price
               FROM products p
               LEFT JOIN product_variants v ON p.has_variants = 1 AND v.vendor_id = p.vendor_id AND v.product_id = p.id
              WHERE p.vendor_id = ? AND p.cooperative_id = ? AND (p.has_variants = 0 OR v.id IS NOT NULL)
              ORDER BY p.sort_order, p.name, v.id",
            [$this->vendorId, $this->cooperativeId]
        );
        return array_map(function ($row) {
            return [
                'product_id' => (int) $row['product_id'],
                'variant_id' => (int) $row['variant_id'],
                'name'       => $row['label'] === null ? $row['name'] : $row['name'] . ' — ' . $row['label'],
                'sku'        => (string) $row['sku'],
                'cost_price' => (int) $row['cost_price'],
            ];
        }, $rows);
    }
}
