<?php
// Pilihan produk untuk borang kit (produk & varian aktif sahaja). Produk bervarian boleh dipilih tanpa varian
// (variant_id null = ibu bapa pilih saiz) atau dengan varian tertentu.
// → [{ product_id, variant_id|null, name, variant_label, price, has_variants }]
class ListKitProductOptionsJob
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
        $products = Database::fetchAll(
            "SELECT id, name, price, has_variants FROM products
              WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 ORDER BY sort_order, name",
            [$this->vendorId, $this->cooperativeId]
        );
        $variants = $this->activeVariants();
        $options = [];
        foreach ($products as $product) {
            $id = (int) $product['id'];
            $hasVariants = (bool) $product['has_variants'];
            $options[] = self::option($id, null, $product['name'], null, (int) $product['price'], $hasVariants);
            if (!$hasVariants) {
                continue;
            }
            foreach ($variants[$id] ?? [] as $variant) {
                $options[] = self::option($id, (int) $variant['id'], $product['name'], $variant['label'],
                    max(0, (int) $product['price'] + (int) $variant['price_delta']), true);
            }
        }
        return $options;
    }

    private function activeVariants()
    {
        $rows = Database::fetchAll(
            "SELECT id, product_id, label, price_delta FROM product_variants
              WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 ORDER BY id",
            [$this->vendorId, $this->cooperativeId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['product_id']][] = $row;
        }
        return $result;
    }

    private static function option($productId, $variantId, $name, $label, $price, $hasVariants)
    {
        return ['product_id' => $productId, 'variant_id' => $variantId, 'name' => $name, 'variant_label' => $label,
            'price' => $price, 'has_variants' => $hasVariants];
    }
}
