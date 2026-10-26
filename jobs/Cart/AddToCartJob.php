<?php
// Tambah barang ala carte { product_id, variant_id, qty, child_id } (PELAN 18.12 store/cart/add).
// Anak mesti milik ibu bapa sesi DAN di sekolah ini; varian wajib jika produk ada varian;
// stok (on_hand − reserved) & had beli dikira merentas seluruh troli. Harga tidak diterima dari pelayar.
class AddToCartJob
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
        $pricing = new StorePricing($this->cooperative);
        $product = $pricing->product((int) ($this->input['product_id'] ?? 0));
        if ($product === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $child = Children::find($this->customerId, (int) ($this->input['child_id'] ?? 0), $this->cooperative);
        if ($child === null) {
            throw new ValidationException(t('store.cart.choose_child'), ['child_id' => t('store.cart.choose_child')]);
        }
        $variant = null;
        if ($product['has_variants']) {
            $variant = StorePricing::findVariant($pricing->variants([$product['id']])[$product['id']] ?? [], (int) ($this->input['variant_id'] ?? 0));
            if ($variant === null) {
                throw new ValidationException(t('pos.error.choose_variant', ['name' => $product['name']]), ['variant_id' => t('error.required')]);
            }
        }
        $qty = Carts::validQty($this->input['qty'] ?? null);
        if ($qty === null) {
            throw Carts::qtyError();
        }
        $variantId = $variant ? $variant['id'] : 0;

        Database::beginTransaction();
        $cart = Carts::ensure($this->customerId, $this->cooperative);
        $items = Carts::items($cart['id']);
        if (count($items) >= Carts::MAX_LINES) {
            throw new ValidationException(t('store.cart.too_many_lines', ['n' => Carts::MAX_LINES]));
        }
        Carts::assertFits($pricing, $product, $variant, Carts::qtyInCart($items, $product['id'], $variantId), $qty);

        $existing = null;
        foreach ($items as $item) {
            if ((int) $item['product_id'] === $product['id'] && (int) $item['variant_id'] === $variantId
                && (int) $item['child_id'] === (int) $child['id'] && $item['source'] === 'ala_carte') {
                $existing = $item;
            }
        }
        if ($existing !== null) {
            if ((int) $existing['qty'] + $qty > Carts::MAX_QTY) {
                throw Carts::qtyError();
            }
            Database::execute("UPDATE cart_items SET qty = qty + ? WHERE cart_id = ? AND id = ?", [$qty, $cart['id'], $existing['id']]);
        } else {
            Database::insert(
                "INSERT INTO cart_items (cart_id, product_id, variant_id, child_id, qty, source) VALUES (?, ?, ?, ?, ?, 'ala_carte')",
                [$cart['id'], $product['id'], $variantId, $child['id'], $qty]
            );
        }
        Carts::touch($cart['id']);
        Database::commit();
        return ['cart_count' => Carts::count($this->customerId, $this->cooperative), 'child_name' => $child['full_name']];
    }
}
