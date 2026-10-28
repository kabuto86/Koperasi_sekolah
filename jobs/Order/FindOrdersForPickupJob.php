<?php
// Carian skrin serah barang (PELAN 7, 18.9 vendor/handover/search) — ikut cara yang diaktifkan koperasi:
//   C/D  token QR (e-mel / slip bercetak) — padanan tepat
//   B    no. pesanan pendek (A123)       — padanan tepat
//   A    nama murid / ibu bapa / kelas    — pesanan aktif sahaja, `ready` dahulu
// Setiap hasil membawa `found_by` (A/B/C) yang dihantar semula semasa serah untuk direkod dalam order_pickups.
class FindOrdersForPickupJob
{
    const LIMIT = 20;

    private $vendorId;
    private $cooperativeId;
    private $params;

    public function __construct($vendorId, $cooperativeId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->params = $params;
    }

    public function run()
    {
        $settings = VendorOrders::pickupSettings($this->vendorId, $this->cooperativeId);
        $methods = $settings['methods'];
        $q = trim((string) ($this->params['q'] ?? ''));
        $grade = trim((string) ($this->params['grade'] ?? ''));
        $class = trim((string) ($this->params['class_name'] ?? ''));
        if ($q === '' && $grade === '' && $class === '') {
            return ['items' => [], 'settings' => $settings];
        }

        $orders = [];
        $foundBy = 'A';
        if ($q !== '' && (in_array('C', $methods, true) || in_array('D', $methods, true)) && preg_match('/^[a-f0-9]{32}$/i', $q)) {
            $orders = $this->exact('pickup_qr_token', strtolower($q));
            $foundBy = 'C';
        }
        if (!$orders && $q !== '' && in_array('B', $methods, true) && preg_match('/^[A-Za-z]{1,2}\d{3,4}$/', $q)) {
            $orders = $this->exact('short_code', strtoupper($q));
            $foundBy = 'B';
        }
        if (!$orders && in_array('A', $methods, true)) {
            $orders = $this->byName($q, $grade, $class);
            $foundBy = 'A';
        }
        $items = array_map(function ($order) use ($foundBy) {
            $detail = VendorOrders::detail($order);
            $detail['found_by'] = $foundBy;
            return $detail;
        }, $orders);
        return ['items' => $items, 'settings' => $settings];
    }

    private function exact($column, $value)
    {
        return Database::fetchAll(
            "SELECT * FROM orders WHERE vendor_id = ? AND cooperative_id = ? AND channel = 'online' AND $column = ? LIMIT 1",
            [$this->vendorId, $this->cooperativeId, $value]
        );
    }

    // Nama murid / ibu bapa / no. pesanan + tapisan darjah & kelas, pesanan aktif sahaja
    private function byName($q, $grade, $class)
    {
        $marks = implode(',', array_fill(0, count(VendorOrders::ACTIVE_STATUSES), '?'));
        $where = "o.vendor_id = ? AND o.cooperative_id = ? AND o.channel = 'online' AND o.status IN ($marks)";
        $args = array_merge([$this->vendorId, $this->cooperativeId], VendorOrders::ACTIVE_STATUSES);
        list($childWhere, $childArgs) = VendorOrders::childFilter(['grade' => $grade, 'class_name' => $class], 'i');
        if ($q !== '') {
            $like = Pagination::like($q);
            $where .= " AND (o.customer_name LIKE ? OR o.order_no LIKE ? OR EXISTS (SELECT 1 FROM order_items i
                          WHERE i.vendor_id = o.vendor_id AND i.order_id = o.id AND i.child_name LIKE ?$childWhere))";
            $args = array_merge($args, [$like, $like, $like], $childArgs);
        }
        if ($childWhere !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM order_items i WHERE i.vendor_id = o.vendor_id AND i.order_id = o.id$childWhere)";
            $args = array_merge($args, $childArgs);
        }
        return Database::fetchAll(
            "SELECT o.* FROM orders o WHERE $where
              ORDER BY FIELD(o.status, 'ready', 'partially_collected', 'preparing', 'paid', 'unpaid_cash'), o.created_at, o.id
              LIMIT " . self::LIMIT,
            $args
        );
    }
}
