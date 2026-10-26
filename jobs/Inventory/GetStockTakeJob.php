<?php
// Butiran stock take + item (qty_counted null = belum dikira), disusun ikut kategori & nama.
class GetStockTakeJob
{
    private $vendorId;
    private $cooperativeId;
    private $stockTakeId;

    public function __construct($vendorId, $cooperativeId, $stockTakeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->stockTakeId = (int) $stockTakeId;
    }

    public function run()
    {
        $data = StockTakes::summary($this->vendorId, $this->cooperativeId, $this->stockTakeId);
        if ($data === null) {
            return null;
        }
        $rows = Database::fetchAll(
            "SELECT i.id, i.product_id, i.variant_id, p.name AS product_name, v.label AS variant_label, COALESCE(v.sku, p.sku) AS sku,
                    c.name AS category_name, i.qty_system, i.qty_counted
               FROM stock_take_items i
               JOIN products p ON p.vendor_id = i.vendor_id AND p.id = i.product_id
               LEFT JOIN product_variants v ON v.vendor_id = i.vendor_id AND v.product_id = i.product_id AND v.id = i.variant_id
               LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id
              WHERE i.vendor_id = ? AND i.stock_take_id = ?
              ORDER BY c.sort_order, p.sort_order, p.name, i.variant_id",
            [$this->vendorId, $this->stockTakeId]
        );
        $data['items'] = array_map(function ($row) {
            return [
                'id'            => (int) $row['id'],
                'product_id'    => (int) $row['product_id'],
                'variant_id'    => (int) $row['variant_id'],
                'product_name'  => $row['product_name'],
                'variant_label' => $row['variant_label'],
                'sku'           => (string) $row['sku'],
                'category_name' => (string) $row['category_name'],
                'qty_system'    => (int) $row['qty_system'],
                'qty_counted'   => $row['qty_counted'] === null ? null : (int) $row['qty_counted'],
            ];
        }, $rows);
        return $data;
    }
}
