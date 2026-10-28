<?php
// Senarai pesanan store koperasi (PELAN 18.9 vendor/orders/list). Jualan POS (channel pos) tidak disenaraikan.
// Tapis q, status (senarai koma), grade, class_name, payment_method, date_from, date_to, pickup_slot_id.
// data.status_counts = kiraan setiap status untuk semua pesanan online koperasi (tanpa tapisan)
class ListVendorOrdersJob
{
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
        list($page, $perPage, $offset) = Pagination::from($this->params);
        list($where, $args) = VendorOrders::filters($this->vendorId, $this->cooperativeId, $this->params);

        $total = Database::fetchValue("SELECT COUNT(*) FROM orders o WHERE $where", $args);
        $orders = Database::fetchAll(
            "SELECT o.* FROM orders o WHERE $where ORDER BY o.created_at DESC, o.id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        $items = CustomerOrders::itemsFor($this->vendorId, array_column($orders, 'id'));
        $rows = array_map(function ($order) use ($items) {
            return VendorOrders::summary($order, $items[(int) $order['id']] ?? []);
        }, $orders);

        $result = Pagination::result($rows, $total, $page, $perPage);
        $counts = Database::fetchAll(
            "SELECT status, COUNT(*) AS total FROM orders WHERE vendor_id = ? AND cooperative_id = ? AND channel = 'online' GROUP BY status",
            [$this->vendorId, $this->cooperativeId]
        );
        $result['status_counts'] = (object) array_map('intval', array_column($counts, 'total', 'status'));
        return $result;
    }
}
