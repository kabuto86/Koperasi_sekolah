<?php
// Pembantu pesanan ibu bapa (PELAN 18.12 — StoreOrderDetail). Setiap pertanyaan menapis customer_id (sesi) +
// vendor_id + cooperative_id (koperasi slug) + channel online — pesanan orang lain / koperasi lain → tidak dijumpai.
class CustomerOrders
{
    // Status yang masih menahan stok (belum diambil / dibatal)
    const HOLDING_STATUSES = ['pending_payment', 'unpaid_cash', 'paid', 'preparing', 'ready', 'partially_collected'];

    const CANCELLABLE = ['pending_payment', 'unpaid_cash'];

    public static function find($customerId, array $cooperative, $orderId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM orders WHERE id = ? AND customer_id = ? AND vendor_id = ? AND cooperative_id = ? AND channel = 'online'"
            . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $orderId, (int) $customerId, (int) $cooperative['vendor_id'], (int) $cooperative['id']]
        );
    }

    // Seperti find tetapi 404 jika tiada
    public static function require($customerId, array $cooperative, $orderId, $forUpdate = false)
    {
        $order = self::find($customerId, $cooperative, $orderId, $forUpdate);
        if ($order === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return $order;
    }

    public static function items(array $order)
    {
        return Database::fetchAll(
            "SELECT * FROM order_items WHERE vendor_id = ? AND order_id = ? ORDER BY id",
            [(int) $order['vendor_id'], (int) $order['id']]
        );
    }

    // [order_id => [item]] untuk senarai
    public static function itemsFor($vendorId, array $orderIds)
    {
        if (!$orderIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($orderIds), '?'));
        $rows = Database::fetchAll(
            "SELECT * FROM order_items WHERE vendor_id = ? AND order_id IN ($marks) ORDER BY id",
            array_merge([(int) $vendorId], array_map('intval', $orderIds))
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['order_id']][] = $row;
        }
        return $result;
    }

    // Status bayaran untuk paparan: paid | pending | unpaid | none
    public static function paymentStatus(array $order)
    {
        if ($order['paid_at'] !== null) {
            return 'paid';
        }
        if ($order['status'] === 'pending_payment') {
            return 'pending';
        }
        $open = ['unpaid_cash', 'preparing', 'ready', 'partially_collected'];
        return $order['payment_method'] === 'cash' && in_array($order['status'], $open, true) ? 'unpaid' : 'none';
    }

    // Kumpulan ikut anak (salinan nama, darjah & kelas semasa pesanan)
    public static function groups(array $items)
    {
        $groups = [];
        foreach ($items as $item) {
            $key = (int) $item['child_id'] . '|' . $item['child_name'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'child_id'         => $item['child_id'] === null ? null : (int) $item['child_id'],
                    'child_name'       => (string) $item['child_name'],
                    'grade_level_code' => (string) $item['grade_label'],
                    'class_name'       => (string) $item['class_name'],
                    'items'            => [],
                ];
            }
            $groups[$key]['items'][] = $item;
        }
        return array_values($groups);
    }

    // Kad "Pesanan saya"
    public static function summary(array $order, array $items)
    {
        $children = array_map(function ($group) {
            unset($group['items']);
            return $group;
        }, self::groups($items));
        return [
            'id'             => (int) $order['id'],
            'order_no'       => $order['order_no'],
            'short_code'     => $order['short_code'],
            'status'         => $order['status'],
            'payment_method' => $order['payment_method'],
            'payment_status' => self::paymentStatus($order),
            'total'          => (int) $order['total'],
            'created_at'     => IsoDate::format($order['created_at']),
            'expires_at'     => IsoDate::format($order['expires_at']),
            'children'       => $children,
            'item_count'     => array_sum(array_column($items, 'qty')),
        ];
    }

    // StoreOrderDetail (PELAN 18.12) — log tanpa nama kakitangan; kod 4 digit tidak pernah dipulangkan
    public static function detail(array $order, array $cooperative)
    {
        $items = self::items($order);
        $data = self::summary($order, $items);
        $vendorId = (int) $order['vendor_id'];
        $promotion = $order['promotion_id'] === null ? null
            : Database::fetchValue("SELECT name FROM promotions WHERE vendor_id = ? AND id = ?", [$vendorId, (int) $order['promotion_id']]);
        $slot = $order['pickup_slot_json'] ? json_decode($order['pickup_slot_json'], true) : null;
        $gatewayRef = $order['payment_method'] === 'online'
            ? Database::fetchValue("SELECT gateway_ref FROM payments WHERE vendor_id = ? AND order_id = ? ORDER BY id DESC LIMIT 1", [$vendorId, (int) $order['id']])
            : null;

        $data += [
            'subtotal'        => (int) $order['subtotal'],
            'discount'        => (int) $order['discount'],
            'promotion_name'  => $promotion,
            'tax'             => (int) $order['tax'],
            'tax_rate'        => 0 + $order['tax_rate'],
            'refunded_amount' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE vendor_id = ? AND order_id = ? AND status = 'completed'",
                [$vendorId, (int) $order['id']]
            ),
            'paid_at'         => IsoDate::format($order['paid_at']),
            'collected_at'    => IsoDate::format($order['collected_at']),
            'pickup_by_type'  => $order['pickup_by_type'],
            'pickup_by_name'  => (string) $order['pickup_by_name'],
            'pickup_slot'     => is_array($slot) ? $slot : null,
            'requires_code'   => $order['pickup_code_hash'] !== null,
            'qr_token'        => $order['pickup_qr_token'],
            'gateway_ref'     => $gatewayRef,
            'can_pay'         => $order['status'] === 'pending_payment',
            'can_cancel'      => in_array($order['status'], self::CANCELLABLE, true),
            'customer_name'   => (string) $order['customer_name'],
            'school'          => [
                'school_name' => $cooperative['school_name'],
                'address'     => (string) $cooperative['address'],
                'phone'       => (string) $cooperative['phone'],
            ],
        ];
        $data['groups'] = array_map(function ($group) {
            $group['items'] = array_map(function ($item) {
                return [
                    'id'            => (int) $item['id'],
                    'product_name'  => $item['product_name'],
                    'variant_label' => $item['variant_label'],
                    'unit_price'    => (int) $item['unit_price'],
                    'qty'           => (int) $item['qty'],
                    'line_total'    => (int) $item['line_total'],
                    'collected_qty' => (int) $item['collected_qty'],
                    'cancelled_qty' => (int) $item['cancelled_qty'],
                    'returned_qty'  => (int) $item['returned_qty'],
                    'source'        => $item['source'],
                ];
            }, $group['items']);
            return $group;
        }, self::groups($items));
        $data['logs'] = array_map(function ($log) {
            return ['to_status' => $log['to_status'], 'created_at' => IsoDate::format($log['created_at'])];
        }, Database::fetchAll(
            "SELECT to_status, created_at FROM order_status_logs WHERE vendor_id = ? AND order_id = ? ORDER BY id",
            [$vendorId, (int) $order['id']]
        ));
        $data['pickups'] = self::pickups($order, $items);
        return $data;
    }

    // Rekod ambil barang (B9 menulis; di sini dibaca untuk ibu bapa)
    private static function pickups(array $order, array $items)
    {
        $vendorId = (int) $order['vendor_id'];
        $pickups = Database::fetchAll(
            "SELECT id, picked_by_type, picked_by_name, created_at FROM order_pickups WHERE vendor_id = ? AND order_id = ? ORDER BY id",
            [$vendorId, (int) $order['id']]
        );
        if (!$pickups) {
            return [];
        }
        $byItem = array_column($items, null, 'id');
        $marks = implode(',', array_fill(0, count($pickups), '?'));
        $rows = Database::fetchAll(
            "SELECT order_pickup_id, order_item_id, qty FROM order_pickup_items WHERE vendor_id = ? AND order_pickup_id IN ($marks) ORDER BY id",
            array_merge([$vendorId], array_map('intval', array_column($pickups, 'id')))
        );
        $result = [];
        foreach ($pickups as $pickup) {
            $lines = [];
            foreach ($rows as $row) {
                $item = $byItem[$row['order_item_id']] ?? null;
                if ((int) $row['order_pickup_id'] === (int) $pickup['id'] && $item !== null) {
                    $lines[] = ['product_name' => $item['product_name'], 'variant_label' => $item['variant_label'],
                        'child_name' => (string) $item['child_name'], 'qty' => (int) $row['qty']];
                }
            }
            $result[] = ['picked_by_type' => $pickup['picked_by_type'], 'picked_by_name' => (string) $pickup['picked_by_name'],
                'created_at' => IsoDate::format($pickup['created_at']), 'items' => $lines];
        }
        return $result;
    }

    // Pesanan dibatal / luput: lepas tahanan stok baki item, tempahan slot & guna promosi. Dalam transaksi, pesanan dikunci
    public static function releaseHolds(array $order)
    {
        $vendorId = (int) $order['vendor_id'];
        $cooperativeId = (int) $order['cooperative_id'];
        $remaining = [];
        foreach (self::items($order) as $item) {
            $qty = (int) $item['qty'] - (int) $item['collected_qty'] - (int) $item['cancelled_qty'];
            $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
            if ($qty > 0) {
                $remaining[$key] = ($remaining[$key] ?? 0) + $qty;
            }
        }
        self::sortStockKeys($remaining);
        foreach ($remaining as $key => $qty) {
            list($productId, $variantId) = explode('#', $key);
            StockLedger::release($vendorId, $cooperativeId, $productId, $variantId, $qty, (int) $order['id'], $order['order_no']);
        }
        if ($order['pickup_slot_id'] !== null) {
            Database::execute(
                "UPDATE pickup_slots SET booked = GREATEST(booked - 1, 0) WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                [(int) $order['pickup_slot_id'], $vendorId, $cooperativeId]
            );
        }
        if ($order['promotion_id'] !== null) {
            Database::execute(
                "UPDATE promotions SET used_count = GREATEST(used_count - 1, 0) WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                [(int) $order['promotion_id'], $vendorId, $cooperativeId]
            );
        }
    }

    // Susun kunci "produk#varian" ikut nombor (tertib kunci baris stok sama seperti CheckoutJob → elak deadlock)
    public static function sortStockKeys(array &$totals)
    {
        uksort($totals, function ($a, $b) {
            $x = array_map('intval', explode('#', $a));
            $y = array_map('intval', explode('#', $b));
            return $x <=> $y;
        });
    }

    public static function logStatus(array $order, $toStatus, $actorType, $actorId, $note = null)
    {
        Database::insert(
            "INSERT INTO order_status_logs (vendor_id, order_id, from_status, to_status, actor_type, actor_id, note) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [(int) $order['vendor_id'], (int) $order['id'], $order['status'] ?? null, $toStatus, $actorType, $actorId,
                $note === null ? null : mb_substr($note, 0, 255, 'UTF-8')]
        );
    }
}
