<?php
// Harga, stok & had beli untuk store (PELAN 18.12) — SENTIASA dikira di pelayan; harga dari pelayar diabaikan.
// Setiap pertanyaan menapis vendor_id + cooperative_id dari baris koperasi slug (StoreSchool).
//
//   $pricing = new StorePricing($cooperative);
//   $price = $pricing->unitPrice($product, $variant);   // ['price', 'regular', 'clearance_item_id', 'qty_limit']
//   $left  = $pricing->available($productId, $variantId);
class StorePricing
{
    private $cooperative;
    private $vendorId;
    private $cooperativeId;
    private $clearance = null;
    private $stock = null;

    public function __construct(array $cooperative)
    {
        $this->cooperative = $cooperative;
        $this->vendorId = (int) $cooperative['vendor_id'];
        $this->cooperativeId = (int) $cooperative['id'];
    }

    // Produk aktif dalam koperasi ini (kategori nyahaktif → produk tidak dijual), atau null
    public function product($productId)
    {
        $rows = $this->products([(int) $productId]);
        return $rows[(int) $productId] ?? null;
    }

    // [id => produk aktif] dengan nama ikut bahasa semasa
    public function products(array $productIds)
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (!$productIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($productIds), '?'));
        $rows = Database::fetchAll(
            "SELECT p.id, p.category_id, p.name, p.description, p.sku, p.price, p.unit, p.image_path, p.max_per_order, p.has_variants,
                    c.name AS category_name, pt.name AS tr_name, pt.description AS tr_description
               FROM products p
               LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id
               LEFT JOIN product_translations pt ON pt.vendor_id = p.vendor_id AND pt.product_id = p.id AND pt.language_code = ?
              WHERE p.vendor_id = ? AND p.cooperative_id = ? AND p.is_active = 1 AND (p.category_id IS NULL OR c.is_active = 1)
                AND p.id IN ($marks)",
            array_merge([Lang::code(), $this->vendorId, $this->cooperativeId], $productIds)
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = self::shapeProduct($row);
        }
        return $result;
    }

    public static function shapeProduct(array $row)
    {
        return [
            'id'            => (int) $row['id'],
            'category_id'   => $row['category_id'] === null ? null : (int) $row['category_id'],
            'category_name' => (string) $row['category_name'],
            'name'          => $row['tr_name'] !== null && $row['tr_name'] !== '' ? $row['tr_name'] : $row['name'],
            'description'   => $row['tr_description'] !== null && $row['tr_description'] !== '' ? $row['tr_description'] : (string) $row['description'],
            'sku'           => (string) $row['sku'],
            'price'         => (int) $row['price'],
            'unit'          => $row['unit'],
            'image_url'     => Uploader::url($row['image_path']),
            'max_per_order' => $row['max_per_order'] === null ? null : (int) $row['max_per_order'],
            'has_variants'  => (bool) $row['has_variants'],
        ];
    }

    // [product_id => [varian aktif]]
    public function variants(array $productIds)
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (!$productIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($productIds), '?'));
        $rows = Database::fetchAll(
            "SELECT id, product_id, label, sku, price_delta FROM product_variants
              WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 AND product_id IN ($marks) ORDER BY id",
            array_merge([$this->vendorId, $this->cooperativeId], $productIds)
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['product_id']][] = [
                'id'          => (int) $row['id'],
                'label'       => $row['label'],
                'sku'         => (string) $row['sku'],
                'price_delta' => (int) $row['price_delta'],
            ];
        }
        return $result;
    }

    // Varian aktif milik produk ini, atau null
    public static function findVariant(array $variants, $variantId)
    {
        foreach ($variants as $variant) {
            if ($variant['id'] === (int) $variantId) {
                return $variant;
            }
        }
        return null;
    }

    // Harga seunit: jualan murah (modul clearance aktif & dalam tempoh) jika lebih murah, jika tidak harga + beza varian
    public function unitPrice(array $product, $variant = null)
    {
        $regular = max(0, $product['price'] + ($variant ? $variant['price_delta'] : 0));
        $sale = $this->clearanceFor($product['id'], $variant ? $variant['id'] : 0);
        return [
            'price'             => $sale ? min($regular, $sale['sale_price']) : $regular,
            'regular'           => $regular,
            'clearance_item_id' => $sale ? $sale['id'] : null,
            'qty_limit'         => $sale ? $sale['qty_limit'] : null,
        ];
    }

    // Had beli setiap pesanan: had produk dan/atau had jualan murah (yang lebih kecil). null = tiada had
    public static function maxQty(array $product, array $price)
    {
        $limits = array_filter([$product['max_per_order'], $price['qty_limit']], function ($n) {
            return $n !== null && $n > 0;
        });
        return $limits ? min($limits) : null;
    }

    // Baki boleh dijual = qty_on_hand − qty_reserved (tidak kurang dari 0)
    public function available($productId, $variantId)
    {
        $this->loadStock();
        return max(0, $this->stock[(int) $productId . '#' . (int) $variantId] ?? 0);
    }

    // Jumlah baki semua varian aktif (kad katalog)
    public function availableForProduct(array $product, array $variants)
    {
        if (!$product['has_variants']) {
            return $this->available($product['id'], 0);
        }
        $total = 0;
        foreach ($variants as $variant) {
            $total += $this->available($product['id'], $variant['id']);
        }
        return $total;
    }

    // StoreProduct (PELAN 18.12) — kad katalog
    public function card(array $product, array $variants, array $grades)
    {
        $prices = [];
        foreach ($product['has_variants'] ? $variants : [null] as $variant) {
            $prices[] = $this->unitPrice($product, $variant);
        }
        $lowest = null;
        $onSale = false;
        foreach ($prices as $price) {
            if ($lowest === null || $price['price'] < $lowest['price']) {
                $lowest = $price;
            }
            $onSale = $onSale || $price['clearance_item_id'] !== null;
        }
        $lowest = $lowest ?: ['price' => $product['price'], 'regular' => $product['price']];
        $priceFrom = false;
        foreach ($prices as $price) {
            $priceFrom = $priceFrom || ($product['has_variants'] && $price['price'] !== $lowest['price']);
        }
        return [
            'id'            => $product['id'],
            'name'          => $product['name'],
            'category_id'   => $product['category_id'],
            'category_name' => $product['category_name'],
            'price'         => $lowest['price'],
            'regular_price' => $lowest['regular'],
            'price_from'    => $priceFrom,
            'on_sale'       => $onSale,
            'unit'          => $product['unit'],
            'image_url'     => $product['image_url'],
            'has_variants'  => $product['has_variants'],
            'available'     => $this->availableForProduct($product, $variants),
            'grade_levels'  => $grades,
        ];
    }

    private function loadStock()
    {
        if ($this->stock !== null) {
            return;
        }
        $rows = Database::fetchAll(
            "SELECT product_id, variant_id, qty_on_hand - qty_reserved AS available FROM stock_levels WHERE vendor_id = ? AND cooperative_id = ?",
            [$this->vendorId, $this->cooperativeId]
        );
        $this->stock = [];
        foreach ($rows as $row) {
            $this->stock[(int) $row['product_id'] . '#' . (int) $row['variant_id']] = (int) $row['available'];
        }
    }

    // Item jualan murah aktif untuk produk + varian (variant_id 0 dalam clearance_items = semua saiz)
    private function clearanceFor($productId, $variantId)
    {
        if ($this->clearance === null) {
            $this->clearance = [];
            if (StoreSchool::hasModule($this->cooperative, 'clearance')) {
                $rows = Database::fetchAll(
                    "SELECT i.id, i.product_id, i.variant_id, i.sale_price, i.qty_limit
                       FROM clearance_items i JOIN clearance_sales s ON s.vendor_id = i.vendor_id AND s.id = i.clearance_sale_id
                      WHERE s.vendor_id = ? AND s.cooperative_id = ? AND s.is_active = 1 AND s.starts_at <= NOW() AND s.ends_at >= NOW()
                      ORDER BY i.sale_price",
                    [$this->vendorId, $this->cooperativeId]
                );
                foreach ($rows as $row) {
                    $key = (int) $row['product_id'] . '#' . (int) $row['variant_id'];
                    if (!isset($this->clearance[$key])) {
                        $this->clearance[$key] = [
                            'id'         => (int) $row['id'],
                            'sale_price' => (int) $row['sale_price'],
                            'qty_limit'  => $row['qty_limit'] === null ? null : (int) $row['qty_limit'],
                        ];
                    }
                }
            }
        }
        return $this->clearance[(int) $productId . '#' . (int) $variantId] ?? $this->clearance[(int) $productId . '#0'] ?? null;
    }
}
