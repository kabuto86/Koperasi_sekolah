<?php
// Jualan murah yang sedang berjalan (modul clearance — PELAN 18.13 store/clearance/list).
// Harga jualan dari pelayan; produk tidak aktif tidak dipapar.
class ListStoreClearanceJob
{
    private $cooperative;

    public function __construct(array $cooperative)
    {
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        StoreSchool::requireModule($this->cooperative, 'clearance');
        $vendorId = (int) $this->cooperative['vendor_id'];
        $sales = Database::fetchAll(
            "SELECT id, name, ends_at FROM clearance_sales
              WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 AND starts_at <= NOW() AND ends_at >= NOW()
              ORDER BY ends_at",
            [$vendorId, (int) $this->cooperative['id']]
        );
        if (!$sales) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($sales), '?'));
        $rows = Database::fetchAll(
            "SELECT clearance_sale_id, product_id, variant_id, sale_price, qty_limit FROM clearance_items
              WHERE vendor_id = ? AND clearance_sale_id IN ($marks) ORDER BY id",
            array_merge([$vendorId], array_map('intval', array_column($sales, 'id')))
        );
        $pricing = new StorePricing($this->cooperative);
        $products = $pricing->products(array_column($rows, 'product_id'));
        $variants = $pricing->variants(array_column($rows, 'product_id'));

        $items = [];
        foreach ($rows as $row) {
            $product = $products[(int) $row['product_id']] ?? null;
            $variantId = (int) $row['variant_id'];
            $variant = $variantId ? StorePricing::findVariant($variants[(int) $row['product_id']] ?? [], $variantId) : null;
            if ($product === null || ($variantId && $variant === null)) {
                continue;
            }
            $regular = max(0, $product['price'] + ($variant ? $variant['price_delta'] : 0));
            $items[(int) $row['clearance_sale_id']][] = [
                'product_id'    => $product['id'],
                'name'          => $product['name'],
                'variant_label' => $variant ? $variant['label'] : null,
                'image_url'     => $product['image_url'],
                'regular_price' => $regular,
                'sale_price'    => min($regular, (int) $row['sale_price']),
                'qty_limit'     => $row['qty_limit'] === null ? null : (int) $row['qty_limit'],
                'available'     => $product['has_variants'] && !$variant ? null : $pricing->available($product['id'], $variantId),
            ];
        }
        return array_map(function ($sale) use ($items) {
            return [
                'id'      => (int) $sale['id'],
                'name'    => $sale['name'],
                'ends_at' => IsoDate::format($sale['ends_at']),
                'items'   => $items[(int) $sale['id']] ?? [],
            ];
        }, $sales);
    }
}
