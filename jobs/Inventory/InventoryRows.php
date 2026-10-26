<?php
// Bentuk InventoryRow (PELAN 18.8) dari stock_levels + produk + varian + kategori. Ditapis vendor_id + cooperative_id.
class InventoryRows
{
    // $where/$args = tapisan tambahan (alias s, p, v, c)
    public static function fetch($vendorId, $cooperativeId, $where = '', array $args = [], $tail = '')
    {
        $rows = Database::fetchAll(
            "SELECT " . self::columns() . self::from() . " WHERE s.vendor_id = ? AND s.cooperative_id = ? $where
              ORDER BY p.sort_order, p.name, s.variant_id $tail",
            array_merge([(int) $vendorId, (int) $cooperativeId], $args)
        );
        return array_map([self::class, 'shape'], $rows);
    }

    public static function count($vendorId, $cooperativeId, $where = '', array $args = [])
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*)" . self::from() . " WHERE s.vendor_id = ? AND s.cooperative_id = ? $where",
            array_merge([(int) $vendorId, (int) $cooperativeId], $args)
        );
    }

    public static function find($vendorId, $cooperativeId, $productId, $variantId)
    {
        $rows = self::fetch($vendorId, $cooperativeId, 'AND s.product_id = ? AND s.variant_id = ?', [(int) $productId, (int) $variantId]);
        return $rows[0] ?? null;
    }

    // Baris stok yang sah: produk wujud dalam koperasi; varian (jika ada) milik produk itu
    private static function from()
    {
        return " FROM stock_levels s
                 JOIN products p ON p.vendor_id = s.vendor_id AND p.cooperative_id = s.cooperative_id AND p.id = s.product_id
                 LEFT JOIN product_variants v ON v.vendor_id = s.vendor_id AND v.product_id = s.product_id AND v.id = s.variant_id
                 LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id";
    }

    private static function columns()
    {
        return "s.product_id, s.variant_id, p.name AS product_name, v.label AS variant_label, COALESCE(v.sku, p.sku) AS sku,
                p.category_id, c.name AS category_name, p.unit, s.qty_on_hand, s.qty_reserved, s.min_stock, p.is_active";
    }

    public static function shape($row)
    {
        $available = (int) $row['qty_on_hand'] - (int) $row['qty_reserved'];
        return [
            'product_id'    => (int) $row['product_id'],
            'variant_id'    => (int) $row['variant_id'],
            'product_name'  => $row['product_name'],
            'variant_label' => $row['variant_label'],
            'sku'           => (string) $row['sku'],
            'category_id'   => $row['category_id'] === null ? null : (int) $row['category_id'],
            'category_name' => (string) $row['category_name'],
            'unit'          => $row['unit'],
            'qty_on_hand'   => (int) $row['qty_on_hand'],
            'qty_reserved'  => (int) $row['qty_reserved'],
            'qty_available' => $available,
            'min_stock'     => (int) $row['min_stock'],
            'is_low'        => $available <= (int) $row['min_stock'],
            'is_active'     => (bool) $row['is_active'],
        ];
    }
}
