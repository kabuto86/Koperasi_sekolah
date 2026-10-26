<?php
// Butiran produk store (PELAN 18.12 store/product): StoreProduct + description, sku, max_per_order, varian aktif.
class StoreProductJob
{
    private $cooperative;
    private $productId;

    public function __construct(array $cooperative, $productId)
    {
        $this->cooperative = $cooperative;
        $this->productId = (int) $productId;
    }

    public function run()
    {
        $pricing = new StorePricing($this->cooperative);
        $product = $pricing->product($this->productId);
        if ($product === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $variants = $pricing->variants([$product['id']])[$product['id']] ?? [];
        $grades = ProductCatalog::gradeLevels($this->cooperative['vendor_id'], [$product['id']]);

        $data = $pricing->card($product, $variants, $grades[$product['id']] ?? []);
        $data['description'] = $product['description'];
        $data['sku'] = $product['sku'];
        $data['max_per_order'] = StorePricing::maxQty($product, $pricing->unitPrice($product));
        $data['variants'] = array_map(function ($variant) use ($pricing, $product) {
            $price = $pricing->unitPrice($product, $variant);
            return [
                'id'            => $variant['id'],
                'label'         => $variant['label'],
                'price'         => $price['price'],
                'regular_price' => $price['regular'],
                'available'     => $pricing->available($product['id'], $variant['id']),
                'max_per_order' => StorePricing::maxQty($product, $price),
            ];
        }, $product['has_variants'] ? $variants : []);
        return $data;
    }
}
