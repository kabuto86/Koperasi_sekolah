<?php
// "Pesanan saya" (PELAN 18.12 store/orders/list): pesanan online ibu bapa sesi di koperasi slug, tapis anak & tahun.
// + pilihan tapisan (anak dalam pesanan — salinan nama, tahun) dan bilangan pesanan sedia diambil.
class ListCustomerOrdersJob
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
        $vendorId = (int) $this->cooperative['vendor_id'];
        $base = "FROM orders o WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.customer_id = ? AND o.channel = 'online'";
        $baseArgs = [$vendorId, (int) $this->cooperative['id'], $this->customerId];

        $where = $base;
        $args = $baseArgs;
        $childId = (int) ($this->input['child_id'] ?? 0);
        if ($childId > 0) {
            $where .= " AND EXISTS (SELECT 1 FROM order_items i WHERE i.vendor_id = o.vendor_id AND i.order_id = o.id AND i.child_id = ?)";
            $args[] = $childId;
        }
        $year = (int) ($this->input['year'] ?? 0);
        if ($year > 0) {
            $where .= " AND o.created_at >= ? AND o.created_at < ?";
            $args[] = $year . '-01-01 00:00:00';
            $args[] = ($year + 1) . '-01-01 00:00:00';
        }

        list($page, $perPage, $offset) = Pagination::from($this->input);
        $total = (int) Database::fetchValue("SELECT COUNT(*) $where", $args);
        $orders = Database::fetchAll("SELECT o.* $where ORDER BY o.created_at DESC, o.id DESC LIMIT $perPage OFFSET $offset", $args);
        $items = CustomerOrders::itemsFor($vendorId, array_column($orders, 'id'));
        $rows = array_map(function ($order) use ($items) {
            return CustomerOrders::summary($order, $items[(int) $order['id']] ?? []);
        }, $orders);

        $data = Pagination::result($rows, $total, $page, $perPage);
        $data['years'] = array_map('intval', array_column(
            Database::fetchAll("SELECT DISTINCT YEAR(o.created_at) AS year $base ORDER BY year DESC", $baseArgs), 'year'));
        $data['children'] = array_map(function ($row) {
            return ['id' => (int) $row['child_id'], 'full_name' => $row['child_name']];
        }, Database::fetchAll(
            "SELECT i.child_id, MAX(i.child_name) AS child_name FROM order_items i JOIN orders o ON o.id = i.order_id AND o.vendor_id = i.vendor_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.customer_id = ? AND o.channel = 'online' AND i.child_id IS NOT NULL
              GROUP BY i.child_id ORDER BY child_name",
            $baseArgs
        ));
        $data['ready_count'] = (int) Database::fetchValue("SELECT COUNT(*) $base AND o.status IN ('ready', 'partially_collected')", $baseArgs);
        return $data;
    }
}
