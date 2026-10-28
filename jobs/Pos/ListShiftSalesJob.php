<?php
// Jualan POS satu syif (vendor/pos/sales), terbaru dahulu. Tapis q (no. resit / nama pembeli).
class ListShiftSalesJob
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
        $shift = PosShifts::require($this->vendorId, $this->cooperativeId, (int) ($this->params['shift_id'] ?? 0));
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = "vendor_id = ? AND cooperative_id = ? AND channel = 'pos' AND pos_shift_id = ?";
        $args = [$this->vendorId, $this->cooperativeId, (int) $shift['id']];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (order_no LIKE ? OR customer_name LIKE ?)';
            $args[] = Pagination::like($q);
            $args[] = Pagination::like($q);
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM orders WHERE $where", $args);
        $orders = Database::fetchAll("SELECT * FROM orders WHERE $where ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset", $args);
        $items = CustomerOrders::itemsFor($this->vendorId, array_column($orders, 'id'));
        $rows = array_map(function ($order) use ($items) {
            return [
                'order_id'       => (int) $order['id'],
                'order_no'       => $order['order_no'],
                'created_at'     => IsoDate::format($order['created_at']),
                'total'          => (int) $order['total'],
                'payment_method' => $order['payment_method'],
                'status'         => $order['status'],
                'payment_status' => CustomerOrders::paymentStatus($order),
                'customer_name'  => (string) $order['customer_name'],
                'item_count'     => array_sum(array_map('intval', array_column($items[(int) $order['id']] ?? [], 'qty'))),
            ];
        }, $orders);
        return Pagination::result($rows, $total, $page, $perPage);
    }
}
