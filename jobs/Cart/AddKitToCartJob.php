<?php
// Masukkan kit ke troli { child_id, kit_id, items: [{ kit_item_id, qty, variant_id }] } (PELAN 5.4, 18.12 store/cart/add-kit).
// Hanya item yang masih DITANDA dihantar. Kit mesti kit diterbitkan untuk darjah & tahun anak (SchoolKits::findPublished).
// Saiz wajib untuk item "ibu bapa pilih saiz". Baris kit yang sama untuk anak yang sama diganti (bukan ditambah).
class AddKitToCartJob
{
    private $customerId;
    private $cooperative;
    private $input;

    public function __construct($customerId, array $cooperative, array $input)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->input = $input;
    }

    public function run()
    {
        $child = Children::find($this->customerId, (int) ($this->input['child_id'] ?? 0), $this->cooperative);
        if ($child === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $kit = SchoolKits::findPublished($this->cooperative, $child['grade_level_code'], $child['school_year']);
        if ($kit === null || (int) $kit['id'] !== (int) ($this->input['kit_id'] ?? 0)) {
            throw new ValidationException(t('store.kit.not_available'));
        }
        $picked = $this->input['items'] ?? [];
        if (!is_array($picked) || count($picked) === 0) {
            throw new ValidationException(t('store.kit.nothing_selected'));
        }

        Database::beginTransaction();
        $cart = Carts::ensure($this->customerId, $this->cooperative);
        $others = array_values(array_filter(Carts::items($cart['id']), function ($item) use ($kit, $child) {
            return !((int) $item['school_kit_id'] === (int) $kit['id'] && (int) $item['child_id'] === (int) $child['id']);
        }));
        $lines = $this->buildLines($kit, $picked, $others);
        if (count($others) + count($lines) > Carts::MAX_LINES) {
            throw new ValidationException(t('store.cart.too_many_lines', ['n' => Carts::MAX_LINES]));
        }

        Database::execute(
            "DELETE FROM cart_items WHERE cart_id = ? AND school_kit_id = ? AND child_id = ?",
            [$cart['id'], $kit['id'], $child['id']]
        );
        foreach ($lines as $line) {
            Database::insert(
                "INSERT INTO cart_items (cart_id, product_id, variant_id, child_id, qty, source, school_kit_id, school_kit_item_id)
                 VALUES (?, ?, ?, ?, ?, 'kit', ?, ?)",
                [$cart['id'], $line['product_id'], $line['variant_id'], $child['id'], $line['qty'], $kit['id'], $line['school_kit_item_id']]
            );
        }
        Carts::touch($cart['id']);
        Database::commit();
        return [
            'cart_count' => Carts::count($this->customerId, $this->cooperative),
            'added'      => count($lines),
            'child_name' => $child['full_name'],
        ];
    }

    // Sahkan setiap item yang ditanda → baris troli. Stok & had beli dikira bersama baris troli lain
    private function buildLines(array $kit, array $picked, array $others)
    {
        $kitItems = array_column(SchoolKits::items($this->cooperative['vendor_id'], [(int) $kit['id']])[(int) $kit['id']] ?? [], null, 'id');
        $pricing = new StorePricing($this->cooperative);
        $products = $pricing->products(array_column($kitItems, 'product_id'));
        $variants = $pricing->variants(array_column($kitItems, 'product_id'));

        $lines = [];
        $seen = [];
        foreach ($picked as $row) {
            $itemId = is_array($row) ? (int) ($row['kit_item_id'] ?? 0) : 0;
            $kitItem = $kitItems[$itemId] ?? null;
            $product = $kitItem ? ($products[$kitItem['product_id']] ?? null) : null;
            $qty = is_array($row) ? Carts::validQty($row['qty'] ?? null) : null;
            if ($kitItem === null || !$kitItem['is_active'] || $product === null || $qty === null || isset($seen[$itemId])) {
                throw new ValidationException(t('error.invalid'));
            }
            $seen[$itemId] = true;

            $productVariants = $variants[$product['id']] ?? [];
            $variantId = $kitItem['variant_id'] !== null ? $kitItem['variant_id'] : (int) ($row['variant_id'] ?? 0);
            $variant = $product['has_variants'] ? StorePricing::findVariant($productVariants, $variantId) : null;
            if ($product['has_variants'] && $variant === null) {
                $message = t('pos.error.choose_variant', ['name' => $product['name']]);
                throw new ValidationException($message, ['variant_' . $itemId => $message]);
            }
            $variantId = $variant ? $variant['id'] : 0;
            $already = Carts::qtyInCart(array_merge($others, $lines), $product['id'], $variantId);
            Carts::assertFits($pricing, $product, $variant, $already, $qty, 'qty_' . $itemId);
            $lines[] = ['product_id' => $product['id'], 'variant_id' => $variantId, 'qty' => $qty, 'school_kit_item_id' => $itemId];
        }
        return $lines;
    }
}
