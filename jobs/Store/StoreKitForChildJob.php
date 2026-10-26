<?php
// Kit sekolah untuk anak (PELAN 5.4, 18.12 store/kit): kit DITERBITKAN ikut darjah & tahun persekolahan anak.
// Draf tidak pernah dipulangkan (SchoolKits::findPublished). Harga setiap item = harga semasa (kit bukan harga pakej — PELAN 16.7).
class StoreKitForChildJob
{
    private $customerId;
    private $cooperative;
    private $childId;

    public function __construct($customerId, array $cooperative, $childId)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->childId = (int) $childId;
    }

    public function run()
    {
        $children = Children::all($this->customerId, $this->cooperative);
        $data = ['children' => array_map(['Children', 'publicShape'], $children), 'child' => null, 'kit' => null];
        if ($this->childId === 0) {
            return $data;
        }
        $child = Children::find($this->customerId, $this->childId, $this->cooperative);
        if ($child === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data['child'] = Children::publicShape($child);

        $kit = SchoolKits::findPublished($this->cooperative, $child['grade_level_code'], $child['school_year']);
        if ($kit === null) {
            return $data;
        }
        $inCart = $this->cartLines((int) $kit['id'], (int) $child['id']);
        $data['kit'] = [
            'id'               => (int) $kit['id'],
            'name'             => (string) $kit['name'],
            'note'             => (string) $kit['note'],
            'grade_level_code' => $kit['grade_level_code'],
            'school_year'      => (int) $kit['school_year'],
            'in_cart'          => count($inCart) > 0,
            'items'            => self::storeItems($this->cooperative, $kit, $inCart),
        ];
        return $data;
    }

    // KitItem (PELAN 18.12) untuk item aktif sahaja; $inCart = [kit_item_id => baris troli]
    public static function storeItems(array $cooperative, array $kit, array $inCart = [])
    {
        $rows = SchoolKits::items($cooperative['vendor_id'], [(int) $kit['id']])[(int) $kit['id']] ?? [];
        $pricing = new StorePricing($cooperative);
        $products = $pricing->products(array_column($rows, 'product_id'));
        $variants = $pricing->variants(array_column($rows, 'product_id'));

        $items = [];
        foreach ($rows as $row) {
            $product = $products[$row['product_id']] ?? null;
            if (!$row['is_active'] || $product === null) {
                continue;
            }
            $productVariants = $variants[$product['id']] ?? [];
            $fixed = $row['variant_id'] !== null ? StorePricing::findVariant($productVariants, $row['variant_id']) : null;
            if ($row['variant_id'] !== null && $fixed === null) {
                continue;
            }
            $choose = $product['has_variants'] && $fixed === null;
            $price = $pricing->unitPrice($product, $fixed);
            $line = $inCart[$row['id']] ?? null;
            $items[] = [
                'id'             => $row['id'],
                'product_id'     => $product['id'],
                'name'           => $product['name'],
                'image_url'      => $product['image_url'],
                'unit'           => $product['unit'],
                'variant_id'     => $fixed ? $fixed['id'] : null,
                'variant_label'  => $fixed ? $fixed['label'] : null,
                'choose_variant' => $choose,
                'variants'       => $choose ? array_map(function ($variant) use ($pricing, $product) {
                    return [
                        'id'        => $variant['id'],
                        'label'     => $variant['label'],
                        'price'     => $pricing->unitPrice($product, $variant)['price'],
                        'available' => $pricing->available($product['id'], $variant['id']),
                    ];
                }, $productVariants) : [],
                'unit_price'     => $price['price'],
                'qty'            => $row['qty'],
                'is_mandatory'   => $row['is_mandatory'],
                'note'           => $row['note'],
                'available'      => $choose ? null : $pricing->available($product['id'], $fixed ? $fixed['id'] : 0),
                'max_per_order'  => StorePricing::maxQty($product, $price),
                'in_cart'        => $line ? ['qty' => (int) $line['qty'], 'variant_id' => (int) $line['variant_id'] ?: null] : null,
            ];
        }
        return $items;
    }

    // Baris troli kit ini untuk anak ini: [kit_item_id => baris]
    private function cartLines($kitId, $childId)
    {
        $cart = Carts::find($this->customerId, $this->cooperative);
        if ($cart === null) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT school_kit_item_id, qty, variant_id FROM cart_items WHERE cart_id = ? AND school_kit_id = ? AND child_id = ? AND source = 'kit'",
            [$cart['id'], $kitId, $childId]
        );
        return array_column($rows, null, 'school_kit_item_id');
    }
}
