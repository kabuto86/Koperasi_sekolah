<?php
// Pembantu pesanan panel vendor (PELAN 18.9 — OrderSummary / OrderDetail). Setiap pertanyaan menapis vendor_id (sesi)
// + cooperative_id (disahkan VendorAuth::requireCooperative) — pesanan koperasi / vendor lain → tidak dijumpai (404).
class VendorOrders
{
    // Peralihan dari panel (PELAN 18.9). collected / partially_collected HANYA melalui serah barang (HandoverOrderJob)
    const STATUS_FLOW = [
        'pending_payment' => ['cancelled'],
        'paid'            => ['preparing'],
        'unpaid_cash'     => ['preparing', 'cancelled'],
        'preparing'       => ['ready'],
        'ready'           => ['preparing'],
    ];

    // Pesanan yang boleh dicari di skrin serah
    const ACTIVE_STATUSES = ['paid', 'unpaid_cash', 'preparing', 'ready', 'partially_collected'];

    const READY_STATUSES = ['ready', 'partially_collected'];

    // 5 kod salah → dikunci (PELAN 7); dibuka semula oleh pengurus (UnlockPickupCodeJob)
    const MAX_CODE_ATTEMPTS = 5;

    public static function find($vendorId, $cooperativeId, $orderId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM orders WHERE id = ? AND vendor_id = ? AND cooperative_id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $orderId, (int) $vendorId, (int) $cooperativeId]
        );
    }

    public static function require($vendorId, $cooperativeId, $orderId, $forUpdate = false)
    {
        $order = self::find($vendorId, $cooperativeId, $orderId, $forUpdate);
        if ($order === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return $order;
    }

    public static function isLocked(array $order)
    {
        return (int) $order['pickup_attempts'] >= self::MAX_CODE_ATTEMPTS;
    }

    // Baki belum diambil = qty − collected − cancelled (sama seperti mockItemRemaining)
    public static function remaining(array $item)
    {
        return max(0, (int) $item['qty'] - (int) $item['collected_qty'] - (int) $item['cancelled_qty']);
    }

    // Tapisan senarai / picking list / label → [where, args]. Alias: o = orders. Darjah/kelas = snapshot order_items
    public static function filters($vendorId, $cooperativeId, array $params, $defaultStatus = '')
    {
        $where = "o.vendor_id = ? AND o.cooperative_id = ? AND o.channel = 'online'";
        $args = [(int) $vendorId, (int) $cooperativeId];

        $statuses = self::statusList($params['status'] ?? $defaultStatus);
        if ($statuses) {
            $where .= ' AND o.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
            $args = array_merge($args, $statuses);
        }
        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $like = Pagination::like($q);
            $where .= " AND (o.order_no LIKE ? OR o.short_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR o.pickup_qr_token = ?
                         OR EXISTS (SELECT 1 FROM order_items qi WHERE qi.vendor_id = o.vendor_id AND qi.order_id = o.id AND qi.child_name LIKE ?))";
            $args = array_merge($args, [$like, $like, $like, $like, $q, $like]);
        }
        list($childWhere, $childArgs) = self::childFilter($params, 'fi');
        if ($childWhere !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM order_items fi WHERE fi.vendor_id = o.vendor_id AND fi.order_id = o.id$childWhere)";
            $args = array_merge($args, $childArgs);
        }
        $method = (string) ($params['payment_method'] ?? '');
        if (in_array($method, ['cash', 'online'], true)) {
            $where .= ' AND o.payment_method = ?';
            $args[] = $method;
        }
        $slotId = (int) ($params['pickup_slot_id'] ?? 0);
        if ($slotId > 0) {
            $where .= ' AND o.pickup_slot_id = ?';
            $args[] = $slotId;
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            $date = (string) ($params[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where .= " AND DATE(o.created_at) $operator ?";
                $args[] = $date;
            }
        }
        return [$where, $args];
    }

    // " AND x.grade_label = ? AND x.class_name = ?" untuk alias item $alias
    public static function childFilter(array $params, $alias)
    {
        $where = '';
        $args = [];
        foreach (['grade' => 'grade_label', 'class_name' => 'class_name'] as $key => $column) {
            $value = trim((string) ($params[$key] ?? ''));
            if ($value !== '') {
                $where .= " AND $alias.$column = ?";
                $args[] = $value;
            }
        }
        return [$where, $args];
    }

    // Item sepadan tapisan darjah/kelas (untuk picking list & label)
    public static function itemMatches(array $item, array $params)
    {
        $grade = trim((string) ($params['grade'] ?? ''));
        $class = trim((string) ($params['class_name'] ?? ''));
        return ($grade === '' || $item['grade_label'] === $grade) && ($class === '' || $item['class_name'] === $class);
    }

    // [kod darjah => susunan] untuk menyusun picking list & label
    public static function gradeOrder()
    {
        return array_flip(array_column(Database::fetchAll("SELECT code FROM grade_levels ORDER BY sort_order"), 'code'));
    }

    private static function statusList($value)
    {
        $all = ['pending_payment', 'paid', 'unpaid_cash', 'preparing', 'ready', 'partially_collected', 'collected', 'cancelled', 'expired', 'refunded'];
        $list = array_filter(array_map('trim', explode(',', (string) $value)));
        return array_values(array_intersect($list, $all));
    }

    // OrderSummary (PELAN 18.9)
    public static function summary(array $order, array $items)
    {
        $data = CustomerOrders::summary($order, $items);
        $slot = $order['pickup_slot_json'] ? json_decode($order['pickup_slot_json'], true) : null;
        return [
            'id'             => $data['id'],
            'order_no'       => $data['order_no'],
            'short_code'     => $data['short_code'],
            'channel'        => $order['channel'],
            'status'         => $data['status'],
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
            'total'          => $data['total'],
            'customer_name'  => (string) $order['customer_name'],
            'customer_phone' => (string) $order['customer_phone'],
            'children'       => $data['children'],
            'item_count'     => $data['item_count'],
            'pickup_slot'    => is_array($slot) ? $slot : null,
            'created_at'     => $data['created_at'],
            'expires_at'     => $data['expires_at'],
        ];
    }

    // OrderDetail (PELAN 18.9) — kod 4 digit & token QR tidak pernah dipulangkan
    public static function detail(array $order)
    {
        $vendorId = (int) $order['vendor_id'];
        $items = CustomerOrders::items($order);
        $data = self::summary($order, $items);
        $data += [
            'subtotal'        => (int) $order['subtotal'],
            'discount'        => (int) $order['discount'],
            'tax_rate'        => 0 + $order['tax_rate'],
            'tax'             => (int) $order['tax'],
            'refunded_amount' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE vendor_id = ? AND order_id = ? AND status = 'completed'",
                [$vendorId, (int) $order['id']]
            ),
            'customer_email'  => (string) $order['customer_email'],
            'pickup_by_type'  => $order['pickup_by_type'],
            'pickup_by_name'  => (string) $order['pickup_by_name'],
            'requires_code'   => $order['pickup_code_hash'] !== null,
            'pickup_locked'   => $order['pickup_code_hash'] !== null && self::isLocked($order),
            'paid_at'         => IsoDate::format($order['paid_at']),
            'collected_at'    => IsoDate::format($order['collected_at']),
        ];
        $data['groups'] = array_map(function ($group) {
            $group['items'] = array_map(function ($item) {
                return [
                    'id'            => (int) $item['id'],
                    'product_name'  => $item['product_name'],
                    'variant_label' => $item['variant_label'],
                    'sku'           => $item['sku'],
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
        }, CustomerOrders::groups($items));
        $data['logs'] = self::logs($order);
        $data['pickups'] = self::pickups($order, $items);
        return $data;
    }

    // Log status + nama pelaku (ibu bapa = nama snapshot, kakitangan = vendor_users vendor ini, sistem = teks)
    private static function logs(array $order)
    {
        $rows = Database::fetchAll(
            "SELECT l.from_status, l.to_status, l.actor_type, l.note, l.created_at, vu.full_name AS user_name
               FROM order_status_logs l
               LEFT JOIN vendor_users vu ON vu.id = l.actor_id AND vu.vendor_id = l.vendor_id AND l.actor_type = 'vendor_user'
              WHERE l.vendor_id = ? AND l.order_id = ? ORDER BY l.id",
            [(int) $order['vendor_id'], (int) $order['id']]
        );
        return array_map(function ($row) use ($order) {
            $names = ['customer' => (string) $order['customer_name'], 'system' => t('order.actor_system'), 'vendor_user' => (string) $row['user_name']];
            return [
                'from_status' => $row['from_status'],
                'to_status'   => $row['to_status'],
                'actor'       => $row['actor_type'],
                'actor_name'  => $names[$row['actor_type']],
                'note'        => (string) $row['note'],
                'created_at'  => IsoDate::format($row['created_at']),
            ];
        }, $rows);
    }

    private static function pickups(array $order, array $items)
    {
        $vendorId = (int) $order['vendor_id'];
        $pickups = Database::fetchAll(
            "SELECT p.id, p.method, p.picked_by_type, p.picked_by_name, p.created_at, vu.full_name AS user_name
               FROM order_pickups p LEFT JOIN vendor_users vu ON vu.id = p.vendor_user_id AND vu.vendor_id = p.vendor_id
              WHERE p.vendor_id = ? AND p.order_id = ? ORDER BY p.id",
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
            $result[] = [
                'method'         => $pickup['method'],
                'picked_by_type' => $pickup['picked_by_type'],
                'picked_by_name' => (string) $pickup['picked_by_name'],
                'user_name'      => (string) $pickup['user_name'],
                'created_at'     => IsoDate::format($pickup['created_at']),
                'items'          => $lines,
            ];
        }
        return $result;
    }

    // Tetapan serah barang koperasi: { methods: ['A',…], code_required_above: sen }
    public static function pickupSettings($vendorId, $cooperativeId)
    {
        $settings = CooperativeSettings::all($vendorId, $cooperativeId);
        $methods = array_values(array_intersect(['A', 'B', 'C', 'D', 'E'],
            explode(',', (string) ($settings['pickup_methods'] ?? CooperativeSettings::DEFAULTS['pickup_methods']))));
        return ['methods' => $methods, 'code_required_above' => (int) ($settings['pickup_code_required_above'] ?? 0)];
    }
}
