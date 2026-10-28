<?php
// Kira semula troli POS di pelayan (PELAN 18.11) — SAMA dengan assets/js/vendor/pos-cart.js (posUnitPrice, posTotals,
// posBestAutomatic): harga seunit = harga + beza varian (≥ 0); satu promosi (kupon, atau automatik terbaik — Promotions::choose);
// diskaun = promosi + manual (≤ jumlah kecil); cukai koperasi atas (jumlah kecil − diskaun); jumlah = baki + cukai. Wang dalam sen.
// Harga / jumlah dari pelayar diabaikan. Dalam transaksi DB baris stok DIKUNCI (StockLedger::lock) — baki terakhir sahih.
// Pulangkan ['lines' => [...], 'promotion' => baris|null, 'tax_rate' => %, 'totals' => [...]]; ralat → ValidationException.
class PosCalculateCartJob
{
    const MAX_QTY = 9999;

    private $cooperative;
    private $vendorId;
    private $cooperativeId;
    private $input;

    public function __construct(array $cooperative, array $input)
    {
        $this->cooperative = $cooperative;
        $this->vendorId = (int) $cooperative['vendor_id'];
        $this->cooperativeId = (int) $cooperative['id'];
        $this->input = $input;
    }

    public function run()
    {
        $lines = $this->lines();
        $subtotal = array_sum(array_column($lines, 'line_total'));

        $chosen = Promotions::choose($this->cooperative, $this->input['coupon_code'] ?? '', $subtotal);
        if ($chosen['error'] !== null) {
            throw new ValidationException($chosen['error'], ['coupon_code' => $chosen['error']]);
        }
        $manual = $this->input['manual_discount'] ?? 0;
        $manual = ($manual === null || $manual === '') ? 0 : filter_var($manual, FILTER_VALIDATE_INT);
        if ($manual === false || $manual < 0 || $manual > $subtotal) {
            throw new ValidationException(t('pos.error.manual_discount'), ['manual_discount' => t('pos.error.manual_discount')]);
        }
        $taxRate = 0 + (float) CooperativeSettings::get($this->vendorId, $this->cooperativeId, 'tax_rate', '0');
        return [
            'lines'     => $lines,
            'promotion' => $chosen['promotion'],
            'tax_rate'  => $taxRate,
            'totals'    => self::totals($subtotal, $chosen['promotion'], $manual, $taxRate),
        ];
    }

    // posTotals (pos-cart.js) dalam PHP
    public static function totals($subtotal, $promotion, $manualDiscount, $taxRate)
    {
        $promotionDiscount = $promotion === null ? 0 : Promotions::discount($promotion, $subtotal);
        $discount = min($subtotal, $promotionDiscount + max(0, (int) $manualDiscount));
        $taxable = $subtotal - $discount;
        $tax = (int) round($taxable * $taxRate / 100);
        return [
            'subtotal'           => $subtotal,
            'promotion_discount' => $promotionDiscount,
            'manual_discount'    => $discount - $promotionDiscount,
            'discount'           => $discount,
            'tax'                => $tax,
            'total'              => $taxable + $tax,
        ];
    }

    // Satu baris setiap produk + varian (baris berganda dari pelayar dicantum)
    private function lines()
    {
        $wanted = [];
        foreach ((array) ($this->input['items'] ?? []) as $row) {
            if (!is_array($row)) {
                throw new ValidationException(t('pos.error.invalid_item'));
            }
            $productId = filter_var($row['product_id'] ?? null, FILTER_VALIDATE_INT);
            $variantId = (int) ($row['variant_id'] ?? 0);
            $qty = filter_var($row['qty'] ?? null, FILTER_VALIDATE_INT);
            if ($productId === false || $productId < 1 || $variantId < 0 || $qty === false || $qty < 1 || $qty > self::MAX_QTY) {
                throw new ValidationException(t('pos.error.invalid_item'));
            }
            $key = $productId . '#' . $variantId;
            $wanted[$key] = ($wanted[$key] ?? 0) + $qty;
        }
        if (!$wanted) {
            throw new ValidationException(t('pos.cart_empty'));
        }
        CustomerOrders::sortStockKeys($wanted);

        $products = $this->products(array_map(function ($key) { return (int) explode('#', $key)[0]; }, array_keys($wanted)));
        $lines = [];
        foreach ($wanted as $key => $qty) {
            list($productId, $variantId) = array_map('intval', explode('#', $key));
            $product = $products[$productId] ?? null;
            if ($product === null) {
                throw new ValidationException(t('pos.error.invalid_item'));
            }
            $variant = $this->variant($product, $variantId);
            $name = $product['name'] . ($variant ? ' — ' . $variant['label'] : '');
            $available = $this->available($productId, $variantId);
            if ($qty > $available) {
                throw new ValidationException(t('pos.error.stock', ['name' => $name, 'n' => max(0, $available)]));
            }
            if ($product['max_per_order'] !== null && $qty > (int) $product['max_per_order']) {
                throw new ValidationException(t('pos.error.max_per_order', ['name' => $product['name'], 'n' => (int) $product['max_per_order']]));
            }
            $price = max(0, (int) $product['price'] + ($variant ? (int) $variant['price_delta'] : 0));
            $lines[] = [
                'key'           => $key,
                'product_id'    => $productId,
                'variant_id'    => $variantId,
                'product_name'  => $product['name'],
                'variant_label' => $variant ? $variant['label'] : null,
                'sku'           => ($variant && (string) $variant['sku'] !== '') ? $variant['sku'] : ((string) $product['sku'] !== '' ? $product['sku'] : null),
                'unit_price'    => $price,
                'qty'           => $qty,
                'line_total'    => $price * $qty,
            ];
        }
        return $lines;
    }

    // [id => produk aktif koperasi ini] (kategori nyahaktif → tidak dijual, sama seperti katalog POS)
    private function products(array $ids)
    {
        $ids = array_values(array_unique($ids));
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT p.id, p.name, p.sku, p.price, p.max_per_order, p.has_variants FROM products p
               LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id
              WHERE p.vendor_id = ? AND p.cooperative_id = ? AND p.is_active = 1 AND (p.category_id IS NULL OR c.is_active = 1)
                AND p.id IN ($marks)",
            array_merge([$this->vendorId, $this->cooperativeId], $ids)
        );
        return array_column($rows, null, 'id');
    }

    // Produk bervarian mesti ada varian aktif; produk tanpa varian mesti variant_id 0
    private function variant(array $product, $variantId)
    {
        if (!(int) $product['has_variants']) {
            if ($variantId !== 0) {
                throw new ValidationException(t('pos.error.invalid_item'));
            }
            return null;
        }
        $variant = $variantId === 0 ? null : Database::fetchOne(
            "SELECT id, label, sku, price_delta FROM product_variants
              WHERE id = ? AND vendor_id = ? AND cooperative_id = ? AND product_id = ? AND is_active = 1",
            [$variantId, $this->vendorId, $this->cooperativeId, (int) $product['id']]
        );
        if ($variant === null) {
            throw new ValidationException(t('pos.error.choose_variant', ['name' => $product['name']]));
        }
        return $variant;
    }

    // Baki boleh dijual — dikunci dalam transaksi checkout, bacaan biasa di luar
    private function available($productId, $variantId)
    {
        if (Database::connection()->inTransaction()) {
            $level = StockLedger::lock($this->vendorId, $this->cooperativeId, $productId, $variantId);
        } else {
            $level = Database::fetchOne(
                "SELECT qty_on_hand, qty_reserved FROM stock_levels WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ?",
                [$this->vendorId, $this->cooperativeId, $productId, $variantId]
            );
        }
        return $level === null ? 0 : (int) $level['qty_on_hand'] - (int) $level['qty_reserved'];
    }
}
