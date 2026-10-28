<?php
// Katalog POS (vendor/pos/catalog — PELAN 18.11): produk aktif koperasi (kategori nyahaktif → tidak dijual), varian aktif,
// barcode & SKU untuk pengimbas, stok boleh dijual (on_hand − reserved) dan promosi yang sedang berjalan (paparan troli —
// pelayan mengira semula semasa checkout). Imbas barcode dipadan di pelayar dari data ini (tiada laluan carian berasingan).
class PosCatalogJob
{
    private $cooperative;
    private $vendorId;
    private $cooperativeId;

    public function __construct(array $cooperative)
    {
        $this->cooperative = $cooperative;
        $this->vendorId = (int) $cooperative['vendor_id'];
        $this->cooperativeId = (int) $cooperative['id'];
    }

    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT p.id, p.category_id, p.name, p.sku, p.barcode, p.price, p.unit, p.image_path, p.max_per_order, p.has_variants,
                    pt.name AS tr_name, c.name AS category_name, c.sort_order AS category_sort
               FROM products p
               LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id
               LEFT JOIN product_translations pt ON pt.vendor_id = p.vendor_id AND pt.product_id = p.id AND pt.language_code = ?
              WHERE p.vendor_id = ? AND p.cooperative_id = ? AND p.is_active = 1 AND (p.category_id IS NULL OR c.is_active = 1)
              ORDER BY p.sort_order, p.name",
            [Lang::code(), $this->vendorId, $this->cooperativeId]
        );
        $stock = $this->stock();
        $variants = $this->variants($stock);
        $categories = [];
        $products = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $own = $variants[$id] ?? [];
            $available = $row['has_variants']
                ? array_sum(array_map(function ($variant) { return max(0, $variant['stock_available']); }, $own))
                : ($stock[$id . '#0'] ?? 0);
            if ($row['category_id'] !== null) {
                $categories[(int) $row['category_id']] = ['id' => (int) $row['category_id'], 'name' => $row['category_name'],
                    'sort' => (int) $row['category_sort']];
            }
            $products[] = [
                'id'              => $id,
                'category_id'     => $row['category_id'] === null ? null : (int) $row['category_id'],
                'name'            => $row['name'],
                'display_name'    => $row['tr_name'] !== null && $row['tr_name'] !== '' ? $row['tr_name'] : $row['name'],
                'sku'             => (string) $row['sku'],
                'barcode'         => (string) $row['barcode'],
                'price'           => (int) $row['price'],
                'unit'            => $row['unit'],
                'image_url'       => Uploader::url($row['image_path']),
                'max_per_order'   => $row['max_per_order'] === null ? null : (int) $row['max_per_order'],
                'has_variants'    => (bool) $row['has_variants'],
                'stock_available' => $available,
                'variants'        => $own,
            ];
        }
        uasort($categories, function ($a, $b) {
            return [$a['sort'], $a['name']] <=> [$b['sort'], $b['name']];
        });
        return [
            'categories' => array_values(array_map(function ($category) {
                return ['id' => $category['id'], 'name' => $category['name']];
            }, $categories)),
            'products'   => $products,
            'promotions' => array_map([__CLASS__, 'promotionShape'], Promotions::active($this->cooperative)),
        ];
    }

    // Promosi untuk paparan troli (pos-cart.js posTotals / posBestAutomatic)
    public static function promotionShape(array $row)
    {
        return [
            'id'        => (int) $row['id'],
            'name'      => $row['name'],
            'code'      => $row['code'],
            'type'      => $row['type'],
            'value'     => (int) $row['value'],
            'min_spend' => (int) $row['min_spend'],
        ];
    }

    // ["produk#varian" => on_hand − reserved]
    private function stock()
    {
        $rows = Database::fetchAll(
            "SELECT product_id, variant_id, qty_on_hand - qty_reserved AS available FROM stock_levels WHERE vendor_id = ? AND cooperative_id = ?",
            [$this->vendorId, $this->cooperativeId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['product_id'] . '#' . (int) $row['variant_id']] = (int) $row['available'];
        }
        return $result;
    }

    // [product_id => [varian aktif]]
    private function variants(array $stock)
    {
        $rows = Database::fetchAll(
            "SELECT id, product_id, label, sku, barcode, price_delta FROM product_variants
              WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 ORDER BY id",
            [$this->vendorId, $this->cooperativeId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['product_id']][] = [
                'id'              => (int) $row['id'],
                'label'           => $row['label'],
                'sku'             => (string) $row['sku'],
                'barcode'         => (string) $row['barcode'],
                'price_delta'     => (int) $row['price_delta'],
                'stock_available' => $stock[(int) $row['product_id'] . '#' . (int) $row['id']] ?? 0,
            ];
        }
        return $result;
    }
}
