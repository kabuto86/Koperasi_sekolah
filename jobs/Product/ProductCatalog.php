<?php
// Pembantu katalog produk (dikongsi oleh job produk, inventori & PO). Setiap pertanyaan menapis vendor_id + cooperative_id.
class ProductCatalog
{
    // Baris produk dalam koperasi vendor sesi, atau null
    public static function find($vendorId, $cooperativeId, $productId)
    {
        return Database::fetchOne(
            "SELECT * FROM products WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [(int) $vendorId, (int) $cooperativeId, (int) $productId]
        );
    }

    // Ringkasan senarai (PELAN 18.8). $where/$args = tapisan tambahan atas alias p
    public static function summaries($vendorId, $cooperativeId, $where = '', array $args = [], $tail = 'ORDER BY p.sort_order, p.name')
    {
        $rows = Database::fetchAll(
            "SELECT p.id, p.name, p.sku, p.barcode, p.category_id, c.name AS category_name, p.price, p.unit, p.image_path,
                    p.has_variants, p.is_active,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.vendor_id = p.vendor_id AND v.product_id = p.id) AS variant_count,
                    COALESCE(st.available, 0) AS stock_available, COALESCE(st.is_low, 0) AS is_low
               FROM products p
               LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id
               LEFT JOIN (" . self::stockSql() . ") st ON st.product_id = p.id
              WHERE p.vendor_id = ? AND p.cooperative_id = ? $where
              $tail",
            array_merge([(int) $vendorId, (int) $cooperativeId, (int) $vendorId, (int) $cooperativeId], $args)
        );
        $grades = self::gradeLevels($vendorId, array_column($rows, 'id'));
        return array_map(function ($row) use ($grades) {
            return [
                'id'              => (int) $row['id'],
                'name'            => $row['name'],
                'sku'             => (string) $row['sku'],
                'barcode'         => (string) $row['barcode'],
                'category_id'     => $row['category_id'] === null ? null : (int) $row['category_id'],
                'category_name'   => (string) $row['category_name'],
                'price'           => (int) $row['price'],
                'unit'            => $row['unit'],
                'image_url'       => Uploader::url($row['image_path']),
                'has_variants'    => (bool) $row['has_variants'],
                'variant_count'   => (int) $row['variant_count'],
                'grade_levels'    => $grades[(int) $row['id']] ?? [],
                'stock_available' => (int) $row['stock_available'],
                'is_low'          => (bool) $row['is_low'],
                'is_active'       => (bool) $row['is_active'],
            ];
        }, $rows);
    }

    // Baki boleh dijual & stok rendah setiap produk (parameter: vendor_id, cooperative_id)
    public static function stockSql()
    {
        return "SELECT product_id, SUM(qty_on_hand - qty_reserved) AS available,
                       MAX(CASE WHEN qty_on_hand - qty_reserved <= min_stock THEN 1 ELSE 0 END) AS is_low
                  FROM stock_levels WHERE vendor_id = ? AND cooperative_id = ? GROUP BY product_id";
    }

    // [product_id => ['D1', 'D4']] — tiada = semua darjah
    public static function gradeLevels($vendorId, array $productIds)
    {
        if (!$productIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($productIds), '?'));
        $rows = Database::fetchAll(
            "SELECT pg.product_id, pg.grade_level_code FROM product_grade_levels pg JOIN grade_levels g ON g.code = pg.grade_level_code
              WHERE pg.vendor_id = ? AND pg.product_id IN ($marks) ORDER BY g.sort_order",
            array_merge([(int) $vendorId], array_map('intval', $productIds))
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['product_id']][] = $row['grade_level_code'];
        }
        return $result;
    }

    public static function variants($vendorId, $productId)
    {
        $rows = Database::fetchAll(
            "SELECT id, label, sku, barcode, price_delta, is_active FROM product_variants WHERE vendor_id = ? AND product_id = ? ORDER BY id",
            [(int) $vendorId, (int) $productId]
        );
        return array_map(function ($row) {
            return [
                'id'          => (int) $row['id'],
                'label'       => $row['label'],
                'sku'         => (string) $row['sku'],
                'barcode'     => (string) $row['barcode'],
                'price_delta' => (int) $row['price_delta'],
                'is_active'   => (bool) $row['is_active'],
            ];
        }, $rows);
    }

    // Varian (atau baris tanpa varian, $variantId = 0) yang sudah ada stok / sejarah stok / pesanan / PO / kit tidak boleh dibuang
    public static function variantInUse($vendorId, $cooperativeId, $productId, $variantId)
    {
        $args = [(int) $vendorId, (int) $productId, (int) $variantId];
        $stock = Database::fetchOne(
            "SELECT qty_on_hand, qty_reserved FROM stock_levels WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ?",
            [(int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId]
        );
        if ($stock !== null && ((int) $stock['qty_on_hand'] !== 0 || (int) $stock['qty_reserved'] !== 0)) {
            return true;
        }
        foreach (['stock_movements', 'order_items', 'purchase_order_items', 'stock_take_items', 'clearance_items', 'school_kit_items'] as $table) {
            if (Database::fetchValue("SELECT 1 FROM $table WHERE vendor_id = ? AND product_id = ? AND variant_id = ? LIMIT 1", $args)) {
                return true;
            }
        }
        // cart_items tiada vendor_id — product_id sudah disahkan milik vendor sesi
        return (bool) Database::fetchValue(
            "SELECT 1 FROM cart_items WHERE product_id = ? AND variant_id = ? LIMIT 1",
            [(int) $productId, (int) $variantId]
        );
    }
}
