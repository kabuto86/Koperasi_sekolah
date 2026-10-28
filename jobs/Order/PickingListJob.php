<?php
// Senarai ambil ikut darjah & kelas (PELAN 18.9 vendor/orders/picking-list) + jumlah keseluruhan setiap produk.
// Lalai status 'paid,unpaid_cash,preparing'. Kuantiti = baki belum diambil (qty − collected − cancelled).
class PickingListJob
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
        list($where, $args) = VendorOrders::filters($this->vendorId, $this->cooperative['id'],
            array_diff_key($this->params, ['q' => 1]), 'paid,unpaid_cash,preparing');
        $orders = Database::fetchAll("SELECT o.* FROM orders o WHERE $where ORDER BY o.id LIMIT " . self::MAX_ORDERS, $args);
        $items = CustomerOrders::itemsFor($this->vendorId, array_column($orders, 'id'));

        $groups = [];
        $totals = [];
        $counted = [];
        foreach ($orders as $order) {
            foreach (CustomerOrders::groups($items[(int) $order['id']] ?? []) as $child) {
                $lines = [];
                foreach ($child['items'] as $item) {
                    $qty = VendorOrders::remaining($item);
                    if (!VendorOrders::itemMatches($item, $this->params) || $qty <= 0) {
                        continue;
                    }
                    $lines[] = ['product_name' => $item['product_name'], 'variant_label' => $item['variant_label'], 'qty' => $qty];
                    $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
                    if (!isset($totals[$key])) {
                        $totals[$key] = ['product_name' => $item['product_name'], 'variant_label' => $item['variant_label'],
                            'sku' => $item['sku'], 'qty' => 0];
                    }
                    $totals[$key]['qty'] += $qty;
                }
                if (!$lines) {
                    continue;
                }
                $groupKey = $child['grade_level_code'] . '|' . $child['class_name'];
                if (!isset($groups[$groupKey])) {
                    $groups[$groupKey] = ['grade_level_code' => $child['grade_level_code'], 'class_name' => $child['class_name'], 'children' => []];
                }
                $counted[(int) $order['id']] = true;
                $groups[$groupKey]['children'][] = ['child_name' => $child['child_name'], 'order_no' => $order['order_no'],
                    'short_code' => $order['short_code'], 'status' => $order['status'], 'items' => $lines];
            }
        }
        return [
            'school_name'  => $this->cooperative['school_name'],
            'generated_at' => IsoDate::format(date('Y-m-d H:i:s')),
            'order_count'  => count($counted),
            'groups'       => self::sortGroups($groups),
            'totals'       => self::sortTotals($totals),
        ];
    }

    // Ikut susunan darjah (grade_levels.sort_order) kemudian nama kelas; anak ikut nama
    public static function sortGroups(array $groups)
    {
        $order = VendorOrders::gradeOrder();
        $groups = array_values($groups);
        usort($groups, function ($a, $b) use ($order) {
            return [$order[$a['grade_level_code']] ?? 99, $a['class_name']] <=> [$order[$b['grade_level_code']] ?? 99, $b['class_name']];
        });
        foreach ($groups as &$group) {
            usort($group['children'], function ($a, $b) {
                return strcmp($a['child_name'], $b['child_name']);
            });
        }
        return $groups;
    }

    private static function sortTotals(array $totals)
    {
        $totals = array_values($totals);
        usort($totals, function ($a, $b) {
            return [$a['product_name'], (string) $a['variant_label']] <=> [$b['product_name'], (string) $b['variant_label']];
        });
        return $totals;
    }
}
