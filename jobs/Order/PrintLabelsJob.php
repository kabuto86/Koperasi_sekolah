<?php
// Label beg (PELAN 18.9 vendor/orders/labels): satu label setiap anak setiap pesanan, untuk dicetak dan dilekat pada beg.
// Lalai status 'paid,unpaid_cash,preparing,ready'; order_id = satu pesanan sahaja (dari butiran pesanan).
// qr_token dipulangkan HANYA di sini (untuk dicetak) — sama dengan QR dalam e-mel / slip ibu bapa (cara C/D).
class PrintLabelsJob
{
    const MAX_ORDERS = 2000;

    private $vendorId;
    private $cooperative;
    private $params;

    public function __construct($vendorId, array $cooperative, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->params = $params;
    }

    public function run()
    {
        $orderId = (int) ($this->params['order_id'] ?? 0);
        if ($orderId > 0) {
            $order = VendorOrders::find($this->vendorId, $this->cooperative['id'], $orderId);
            $orders = $order !== null && $order['channel'] === 'online' ? [$order] : [];
        } else {
            list($where, $args) = VendorOrders::filters($this->vendorId, $this->cooperative['id'],
                array_diff_key($this->params, ['q' => 1]), 'paid,unpaid_cash,preparing,ready');
            $orders = Database::fetchAll("SELECT o.* FROM orders o WHERE $where ORDER BY o.id LIMIT " . self::MAX_ORDERS, $args);
        }
        $items = CustomerOrders::itemsFor($this->vendorId, array_column($orders, 'id'));

        $labels = [];
        foreach ($orders as $order) {
            $summary = VendorOrders::summary($order, $items[(int) $order['id']] ?? []);
            foreach (CustomerOrders::groups($items[(int) $order['id']] ?? []) as $child) {
                $childItems = array_filter($child['items'], function ($item) use ($orderId) {
                    return $orderId > 0 || VendorOrders::itemMatches($item, $this->params);
                });
                if (!$childItems) {
                    continue;
                }
                $labels[] = [
                    'order_id'         => (int) $order['id'],
                    'order_no'         => $order['order_no'],
                    'short_code'       => $order['short_code'],
                    'child_name'       => $child['child_name'],
                    'grade_level_code' => $child['grade_level_code'],
                    'class_name'       => $child['class_name'],
                    'item_count'       => array_sum(array_column($childItems, 'qty')),
                    'payment_status'   => $summary['payment_status'],
                    'pickup_slot'      => $summary['pickup_slot'],
                    'qr_token'         => $order['pickup_qr_token'],
                ];
            }
        }
        $grades = VendorOrders::gradeOrder();
        usort($labels, function ($a, $b) use ($grades) {
            return [$grades[$a['grade_level_code']] ?? 99, $a['class_name'], $a['child_name'], $a['order_id']]
                <=> [$grades[$b['grade_level_code']] ?? 99, $b['class_name'], $b['child_name'], $b['order_id']];
        });
        return ['school_name' => $this->cooperative['school_name'], 'labels' => $labels];
    }
}
