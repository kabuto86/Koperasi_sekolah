<?php
// Buat pesanan dari troli (PELAN 6, 16 perkara 2–6, 12; 18.12 store/checkout/place). SATU transaksi DB:
//   kunci troli → kunci baris stok (FOR UPDATE, tertib tetap) → kira semula harga/promosi/cukai → semak baki →
//   kunci slot & promosi → jana no. pesanan & kod pendek → simpan pesanan + snapshot item → tahan stok →
//   kosongkan troli → masukkan e-mel ke queue. Gagal di mana-mana = tiada apa-apa disimpan.
// customer_id dari sesi; koperasi dari slug (StoreSchool). Harga dari pelayar diabaikan (expected_total hanya untuk
// mengesan perubahan harga).
class CheckoutJob
{
    const PICKUP_TYPES = ['student', 'parent', 'proxy'];

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
        $customer = (new LoadCustomerSessionJob($this->customerId))->run();
        if ($customer === null) {
            throw new ValidationException(t('store.login_required'), [], 401, ['auth_required' => true]);
        }
        if ($customer['email_verified_at'] === null) {
            throw new ValidationException(t('store.checkout.verify_first'));
        }
        $this->validateInput();

        Database::beginTransaction();
        try {
            $result = $this->place($customer);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        if ($this->input['payment_method'] === 'online') {
            $result = $this->startPayment($result);
        }
        return $result;
    }

    // B8: bil gateway dicipta SELEPAS pesanan disimpan (di luar transaksi). Gateway gagal → pesanan kekal pending_payment,
    // payment = null + payment_error; ibu bapa boleh tekan "Bayar" semula dari halaman pesanan
    private function startPayment(array $result)
    {
        try {
            $start = (new StartOnlinePaymentJob($this->customerId, $this->cooperative, $result['order_id']))->run();
            $result['payment'] = $start['payment'];
        } catch (ValidationException $error) {
            $result['payment_error'] = $error->getMessage();
        }
        return $result;
    }

    // Semakan bentuk input (sebelum transaksi)
    private function validateInput()
    {
        $errors = [];
        $type = (string) ($this->input['pickup_by_type'] ?? '');
        if (!in_array($type, self::PICKUP_TYPES, true)) {
            $errors['pickup_by_type'] = t('error.required');
        }
        $name = trim((string) ($this->input['pickup_by_name'] ?? ''));
        if ($type === 'proxy' && $name === '') {
            $errors['pickup_by_name'] = t('error.required');
        } elseif (mb_strlen($name, 'UTF-8') > 150) {
            $errors['pickup_by_name'] = t('error.max_length', ['max' => 150]);
        }
        if (!in_array($this->input['payment_method'] ?? '', ['cash', 'online'], true)) {
            $errors['payment_method'] = t('store.checkout.choose_method');
        }
        if ($errors) {
            throw new ValidationException(t('error.check_form'), $errors);
        }
    }

    private function place(array $customer)
    {
        $coop = $this->cooperative;
        $cart = Carts::find($customer['id'], $coop, true);
        if ($cart === null) {
            throw new ValidationException(t('store.cart.empty'));
        }
        $available = $this->lockStock($cart);

        $built = CheckoutQuote::build($customer, $coop, $this->input['coupon_code'] ?? '');
        $quote = $built['data'];
        if (!$quote['groups']) {
            throw new ValidationException(t('store.cart.empty'));
        }
        if ($quote['has_problem']) {
            throw new ValidationException(t('store.checkout.fix_cart'));
        }
        $this->assertStock($built['lines'], $available);
        if ($quote['orders_blocked']) {
            throw new ValidationException(t('store.closed.blocked', [
                'title' => $quote['orders_blocked']['title'], 'date' => date('d/m/Y', strtotime($quote['orders_blocked']['reopens_on'])),
            ]));
        }
        if ($quote['coupon_error'] !== null) {
            throw new ValidationException($quote['coupon_error'], ['coupon_code' => $quote['coupon_error']]);
        }
        $method = $this->input['payment_method'];
        $errors = [];
        if (!in_array($method, $quote['payment_methods'], true)) {
            $errors['payment_method'] = t('store.checkout.method_unavailable');
        }
        $code = (string) ($this->input['pickup_code'] ?? '');
        if ($quote['requires_code'] && !preg_match('/^[0-9]{4}$/', $code)) {
            $errors['pickup_code'] = t('handover.code_format');
        }
        $slot = $this->lockSlot($quote, $errors);
        if ($errors) {
            throw new ValidationException(t('error.check_form'), $errors);
        }
        if (isset($this->input['expected_total']) && $this->input['expected_total'] !== null
            && (int) $this->input['expected_total'] !== $quote['total']) {
            throw new ValidationException(t('store.checkout.total_changed', ['total' => Money::format($quote['total'])]));
        }
        $this->usePromotion($built['promotion']);

        $order = $this->insertOrder($customer, $quote, $built['promotion'], $slot, $quote['requires_code'] ? $code : null);
        $this->insertItems($order, $built['lines']);
        CustomerOrders::logStatus(['vendor_id' => $coop['vendor_id'], 'id' => $order['id'], 'status' => null], $order['status'], 'customer', $customer['id']);
        $this->reserveStock($order, $built['lines']);
        if ($slot !== null) {
            Database::execute("UPDATE pickup_slots SET booked = booked + 1 WHERE id = ? AND vendor_id = ?", [$slot['id'], (int) $coop['vendor_id']]);
        }
        Database::execute("DELETE FROM cart_items WHERE cart_id = ?", [(int) $cart['id']]);
        Carts::touch($cart['id']);
        OrderEmails::queueNewOrder($coop, $order, $customer);

        return [
            'order_id'   => $order['id'],
            'order_no'   => $order['order_no'],
            'short_code' => $order['short_code'],
            'payment'    => null, // online: { reference, gateway_name, amount, mode, redirect_url } — diisi startPayment()
        ];
    }

    // Kunci baris stok semua produk+varian dalam troli (tertib tetap → tiada deadlock antara dua checkout).
    // Pulangkan [kunci => baki boleh dijual] yang dibaca SELEPAS kunci — sumber kebenaran untuk stok terakhir
    private function lockStock(array $cart)
    {
        $rows = Database::fetchAll(
            "SELECT product_id, variant_id FROM cart_items WHERE cart_id = ? ORDER BY product_id, variant_id FOR UPDATE",
            [(int) $cart['id']]
        );
        $available = [];
        foreach ($rows as $row) {
            $key = (int) $row['product_id'] . '#' . (int) $row['variant_id'];
            if (!isset($available[$key])) {
                $level = StockLedger::lock($this->cooperative['vendor_id'], $this->cooperative['id'], $row['product_id'], $row['variant_id']);
                $available[$key] = (int) $level['qty_on_hand'] - (int) $level['qty_reserved'];
            }
        }
        return $available;
    }

    private function assertStock(array $lines, array $available)
    {
        $needed = [];
        foreach ($lines as $line) {
            $needed[$line['key']] = ($needed[$line['key']] ?? 0) + (int) $line['public']['qty'];
            if ($needed[$line['key']] > ($available[$line['key']] ?? 0)) {
                $name = $line['public']['name'] . ($line['public']['variant_label'] ? ' — ' . $line['public']['variant_label'] : '');
                throw new ValidationException(t('pos.error.stock', ['name' => $name, 'n' => max(0, $available[$line['key']] ?? 0)]));
            }
        }
    }

    // Modul school_opening: slot mesti dari senarai yang sesuai; dikunci & disemak semula (booked < capacity)
    private function lockSlot(array $quote, array &$errors)
    {
        if (!$quote['slot_required']) {
            return null;
        }
        $slotId = (int) ($this->input['pickup_slot_id'] ?? 0);
        if (!in_array($slotId, array_column($quote['pickup_slots'], 'id'), true)) {
            $errors['pickup_slot_id'] = t('store.slot.choose');
            return null;
        }
        $row = Database::fetchOne(
            "SELECT id, slot_date, time_start, time_end, grade_level_code, capacity, booked FROM pickup_slots
              WHERE id = ? AND vendor_id = ? AND cooperative_id = ? FOR UPDATE",
            [$slotId, (int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']]
        );
        if ($row === null || (int) $row['booked'] >= (int) $row['capacity']) {
            $errors['pickup_slot_id'] = t('store.slot.full');
            return null;
        }
        return CheckoutQuote::slotShape($row);
    }

    // Kunci promosi & semak had guna sekali lagi (dua pesanan serentak untuk guna terakhir)
    private function usePromotion($promotion)
    {
        if ($promotion === null) {
            return;
        }
        $row = Database::fetchOne(
            "SELECT used_count, max_uses FROM promotions WHERE id = ? AND vendor_id = ? AND cooperative_id = ? FOR UPDATE",
            [(int) $promotion['id'], (int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']]
        );
        if ($row === null || ($row['max_uses'] !== null && (int) $row['used_count'] >= (int) $row['max_uses'])) {
            throw new ValidationException(t('pos.coupon_invalid'), ['coupon_code' => t('pos.coupon_invalid')]);
        }
        Database::execute("UPDATE promotions SET used_count = used_count + 1 WHERE id = ?", [(int) $promotion['id']]);
    }

    private function insertOrder(array $customer, array $quote, $promotion, $slot, $pickupCode)
    {
        $coop = $this->cooperative;
        $numbers = OrderNumbers::next($coop);
        $isCash = $this->input['payment_method'] === 'cash';
        $type = $this->input['pickup_by_type'];
        $names = [
            'student' => implode(', ', array_column($quote['groups'], 'child_name')),
            'parent'  => $customer['full_name'],
            'proxy'   => trim((string) ($this->input['pickup_by_name'] ?? '')),
        ];
        $slotCopy = $slot === null ? null : json_encode([
            'slot_date' => $slot['slot_date'], 'time_start' => $slot['time_start'], 'time_end' => $slot['time_end'],
            'grade_level_code' => $slot['grade_level_code'],
        ]);
        $order = [
            'vendor_id'          => (int) $coop['vendor_id'],
            'cooperative_id'     => (int) $coop['id'],
            'customer_id'        => (int) $customer['id'],
            'order_no'           => $numbers['order_no'],
            'short_code'         => $numbers['short_code'],
            'channel'            => 'online',
            'payment_method'     => $this->input['payment_method'],
            'status'             => $isCash ? 'unpaid_cash' : 'pending_payment',
            'subtotal'           => $quote['subtotal'],
            'discount'           => $quote['discount'],
            'promotion_discount' => $quote['discount'],
            'tax_rate'           => $quote['tax_rate'],
            'tax'                => $quote['tax'],
            'total'              => $quote['total'],
            'promotion_id'       => $promotion ? (int) $promotion['id'] : null,
            'pickup_by_type'     => $type,
            'pickup_by_name'     => mb_substr($names[$type], 0, 150, 'UTF-8'),
            'pickup_slot_id'     => $slot ? $slot['id'] : null,
            'pickup_slot_json'   => $slotCopy,
            'pickup_code_hash'   => $pickupCode === null ? null : Password::hash($pickupCode),
            'pickup_qr_token'    => $numbers['qr_token'],
            'customer_name'      => $customer['full_name'],
            'customer_phone'     => $customer['phone'],
            'customer_email'     => $customer['email'],
            'expires_at'         => $isCash ? date('Y-m-d 23:59:59', strtotime('+' . (int) $quote['cash_expiry_days'] . ' days'))
                                                : date('Y-m-d H:i:s', time() + 60 * (int) Config::get('online_payment_expiry_minutes', 60)),
        ];
        $columns = array_keys($order);
        $order['id'] = Database::insert(
            "INSERT INTO orders (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")",
            array_values($order)
        );
        return $order;
    }

    // Snapshot nama produk (nama vendor), varian, SKU, harga & anak/darjah/kelas (PELAN 16 perkara 6)
    private function insertItems(array $order, array $lines)
    {
        foreach ($lines as $line) {
            $product = $line['product'];
            $variant = $line['variant'];
            $child = $line['child'];
            $qty = (int) $line['public']['qty'];
            $price = (int) $line['price']['price'];
            Database::insert(
                "INSERT INTO order_items (vendor_id, order_id, product_id, variant_id, product_name, variant_label, sku, unit_price, qty, line_total,
                                          source, school_kit_id, clearance_item_id, child_id, child_name, grade_label, class_name)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $order['vendor_id'], $order['id'], $product['id'], $variant ? $variant['id'] : 0,
                    mb_substr($product['base_name'], 0, 150, 'UTF-8'), $variant ? $variant['label'] : null,
                    ($variant && $variant['sku'] !== '') ? $variant['sku'] : ($product['sku'] !== '' ? $product['sku'] : null),
                    $price, $qty, $price * $qty, $line['item']['source'],
                    $line['item']['school_kit_id'], $line['price']['clearance_item_id'],
                    (int) $child['id'], $child['full_name'], $child['grade_level_code'], (string) $child['class_name'],
                ]
            );
        }
    }

    // Tahan stok (qty_reserved) — satu pergerakan setiap produk+varian, tertib tetap
    private function reserveStock(array $order, array $lines)
    {
        $totals = [];
        foreach ($lines as $line) {
            $totals[$line['key']] = ($totals[$line['key']] ?? 0) + (int) $line['public']['qty'];
        }
        CustomerOrders::sortStockKeys($totals);
        foreach ($totals as $key => $qty) {
            list($productId, $variantId) = explode('#', $key);
            StockLedger::reserve($order['vendor_id'], $order['cooperative_id'], $productId, $variantId, $qty, $order['id'], $order['order_no']);
        }
    }
}
