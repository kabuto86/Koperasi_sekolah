<?php
// Pembantu troli ibu bapa (PELAN 5.4, 18.12). Satu troli setiap ibu bapa + koperasi (UNIQUE customer_id, cooperative_id).
// customer_id dari sesi; koperasi dari slug (StoreSchool). Harga, stok & had beli dikira semula setiap kali (CartQuote).
class Carts
{
    const MAX_QTY = 99;
    const MAX_LINES = 200;

    // Troli ibu bapa di koperasi ini, atau null
    public static function find($customerId, array $cooperative, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM carts WHERE customer_id = ? AND vendor_id = ? AND cooperative_id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $customerId, (int) $cooperative['vendor_id'], (int) $cooperative['id']]
        );
    }

    // Troli (dicipta jika belum ada) dan dikunci FOR UPDATE — panggil dalam transaksi
    public static function ensure($customerId, array $cooperative)
    {
        Database::execute(
            "INSERT IGNORE INTO carts (customer_id, cooperative_id, vendor_id) VALUES (?, ?, ?)",
            [(int) $customerId, (int) $cooperative['id'], (int) $cooperative['vendor_id']]
        );
        return self::find($customerId, $cooperative, true);
    }

    public static function items($cartId)
    {
        return Database::fetchAll("SELECT * FROM cart_items WHERE cart_id = ? ORDER BY id", [(int) $cartId]);
    }

    public static function touch($cartId)
    {
        Database::execute("UPDATE carts SET updated_at = NOW() WHERE id = ?", [(int) $cartId]);
    }

    // Bilangan unit dalam troli koperasi ini
    public static function count($customerId, array $cooperative)
    {
        return (int) Database::fetchValue(
            "SELECT COALESCE(SUM(ci.qty), 0) FROM carts ca JOIN cart_items ci ON ci.cart_id = ca.id
              WHERE ca.customer_id = ? AND ca.vendor_id = ? AND ca.cooperative_id = ?",
            [(int) $customerId, (int) $cooperative['vendor_id'], (int) $cooperative['id']]
        );
    }

    // Kuantiti 1–99 atau null
    public static function validQty($value)
    {
        if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', trim($value)))) {
            $qty = (int) $value;
            return $qty >= 1 && $qty <= self::MAX_QTY ? $qty : null;
        }
        return null;
    }

    public static function qtyError()
    {
        $message = t('error.range', ['min' => 1, 'max' => self::MAX_QTY]);
        return new ValidationException($message, ['qty' => $message]);
    }

    // Jumlah kuantiti produk + varian ini dalam baris troli (semua anak, kit & ala carte)
    public static function qtyInCart(array $items, $productId, $variantId)
    {
        $total = 0;
        foreach ($items as $item) {
            if ((int) $item['product_id'] === (int) $productId && (int) $item['variant_id'] === (int) $variantId) {
                $total += (int) $item['qty'];
            }
        }
        return $total;
    }

    // Stok & had beli merentas troli: $already = kuantiti lain dalam troli, $qty = kuantiti baharu. Gagal → ValidationException
    public static function assertFits(StorePricing $pricing, array $product, $variant, $already, $qty, $field = 'qty')
    {
        $variantId = $variant ? $variant['id'] : 0;
        $name = $product['name'] . ($variant ? ' — ' . $variant['label'] : '');
        $available = $pricing->available($product['id'], $variantId);
        if ($already + $qty > $available) {
            $message = t('pos.error.stock', ['name' => $name, 'n' => max(0, $available - $already)]);
            throw new ValidationException($message, [$field => $message]);
        }
        $max = StorePricing::maxQty($product, $pricing->unitPrice($product, $variant));
        if ($max !== null && $already + $qty > $max) {
            $message = t('store.cart.max_per_order', ['name' => $product['name'], 'n' => $max]);
            throw new ValidationException($message, [$field => $message]);
        }
    }

    // CartQuote (PELAN 18.12). Promosi & kupon dikira semasa checkout (CheckoutQuote, B7) — di sini diskaun 0
    public static function quote($customerId, array $cooperative)
    {
        return self::quoteWithLines($customerId, $cooperative)['quote'];
    }

    // CartQuote + baris dalaman (produk, varian, harga, anak, baris troli) untuk checkout (B7)
    public static function quoteWithLines($customerId, array $cooperative)
    {
        $cart = self::find($customerId, $cooperative);
        $items = $cart ? self::items($cart['id']) : [];
        $lines = self::priceLines($customerId, $cooperative, $items);

        $groups = [];
        $subtotal = 0;
        $count = 0;
        $hasProblem = false;
        foreach ($lines as $line) {
            $childId = (int) $line['child_id'];
            if (!isset($groups[$childId])) {
                $child = $line['child'];
                $groups[$childId] = [
                    'child_id'         => $childId,
                    'child_name'       => $child ? $child['full_name'] : '',
                    'grade_level_code' => $child ? $child['grade_level_code'] : '',
                    'class_name'       => $child ? (string) $child['class_name'] : '',
                    'subtotal'         => 0,
                    'items'            => [],
                ];
            }
            $public = $line['public'];
            $groups[$childId]['items'][] = $public;
            $groups[$childId]['subtotal'] += $public['line_total'];
            if ($public['problem'] === null) {
                $subtotal += $public['line_total'];
                $count += $public['qty'];
            } else {
                $hasProblem = true;
            }
        }

        $settings = CooperativeSettings::all($cooperative['vendor_id'], $cooperative['id']);
        $taxRate = is_numeric($settings['tax_rate'] ?? null) ? 0 + $settings['tax_rate'] : 0;
        $tax = (int) round($subtotal * $taxRate / 100);
        $total = $subtotal + $tax;
        $codeAbove = (int) ($settings['pickup_code_required_above'] ?? 0);
        $methods = explode(',', (string) ($settings['pickup_methods'] ?? 'A,B,C,D'));
        $quote = [
            'groups'              => array_values($groups),
            'item_count'          => $count,
            'has_problem'         => $hasProblem,
            'subtotal'            => $subtotal,
            'discount'            => 0,
            'promotion_name'      => null,
            'promotion_code'      => null,
            'coupon_code'         => '',
            'coupon_error'        => null,
            'tax_rate'            => $taxRate,
            'tax'                 => $tax,
            'total'               => $total,
            'payment_methods'     => StoreSchool::paymentMethods($cooperative),
            'gateway_name'        => null,
            'cash_expiry_days'    => (int) ($settings['cash_order_expiry_days'] ?? 3),
            'requires_code'       => in_array('E', $methods, true) || ($codeAbove > 0 && $total > $codeAbove),
            'code_required_above' => $codeAbove,
        ];
        return ['quote' => $quote, 'lines' => $lines];
    }

    // Kira semula setiap baris: produk/varian/anak masih sah, harga semasa, stok & had beli (dijumlahkan merentas troli)
    private static function priceLines($customerId, array $cooperative, array $items)
    {
        $pricing = new StorePricing($cooperative);
        $productIds = array_column($items, 'product_id');
        $products = $pricing->products($productIds);
        $variants = $pricing->variants($productIds);
        $children = [];
        foreach (Children::all($customerId, $cooperative) as $child) {
            $children[(int) $child['id']] = $child;
        }

        $needed = [];
        $lines = [];
        foreach ($items as $item) {
            $product = $products[(int) $item['product_id']] ?? null;
            $variant = $product ? StorePricing::findVariant($variants[$product['id']] ?? [], $item['variant_id']) : null;
            $child = $children[(int) $item['child_id']] ?? null;
            $valid = $product !== null && $child !== null
                && ($product['has_variants'] ? $variant !== null : (int) $item['variant_id'] === 0);
            $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
            $line = ['child_id' => (int) $item['child_id'], 'child' => $child, 'key' => $key, 'valid' => $valid,
                'item' => $item, 'product' => $product, 'variant' => $variant];
            $qty = (int) $item['qty'];
            if ($valid) {
                $price = $pricing->unitPrice($product, $variant);
                $needed[$key] = ($needed[$key] ?? 0) + $qty;
                $line['available'] = $pricing->available($product['id'], (int) $item['variant_id']);
                $line['max'] = StorePricing::maxQty($product, $price);
            } else {
                $price = ['price' => 0, 'regular' => 0, 'clearance_item_id' => null, 'qty_limit' => null];
                $line['available'] = 0;
                $line['max'] = null;
            }
            $line['price'] = $price;
            $line['public'] = [
                'id'            => (int) $item['id'],
                'product_id'    => (int) $item['product_id'],
                'variant_id'    => (int) $item['variant_id'],
                'name'          => $product ? $product['name'] : t('store.cart.removed_product'),
                'variant_label' => $variant ? $variant['label'] : null,
                'image_url'     => $product ? $product['image_url'] : null,
                'unit_price'    => $price['price'],
                'regular_price' => $price['regular'],
                'qty'           => $qty,
                'line_total'    => $price['price'] * $qty,
                'source'        => $item['source'],
                'school_kit_id' => $item['school_kit_id'] === null ? null : (int) $item['school_kit_id'],
                'available'     => $line['available'],
                'max'           => $line['max'],
                'problem'       => $valid ? null : 'unavailable',
            ];
            $lines[] = $line;
        }
        foreach ($lines as &$line) {
            if (!$line['valid']) {
                continue;
            }
            if ($needed[$line['key']] > $line['available']) {
                $line['public']['problem'] = 'stock';
            } elseif ($line['max'] !== null && $needed[$line['key']] > $line['max']) {
                $line['public']['problem'] = 'max';
            }
        }
        unset($line);
        return $lines;
    }
}
