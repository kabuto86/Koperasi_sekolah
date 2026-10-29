<?php
// Jualan ikut darjah / kelas dari salinan dalam order_items (grade_label, class_name, child_id) — tidak berubah bila anak naik darjah.
// Unit & jualan bersih (selepas batal & pulang). Murid = anak berbeza; purata = jualan ÷ murid. Disusun ikut grade_levels.sort_order.
class ChildSales
{
    public static function rows($vendorId, $cooperativeId, $from, $to, $byClass)
    {
        $net = ReportRange::NET_QTY;
        $group = $byClass ? 'oi.grade_label, oi.class_name' : 'oi.grade_label';
        list($paidSql, $paidArgs) = ReportRange::between('o.paid_at', $from, $to);
        $result = Database::fetchAll(
            "SELECT oi.grade_label AS grade, " . ($byClass ? 'oi.class_name' : 'NULL') . " AS class_name,
                    COUNT(DISTINCT oi.child_id) AS students, COUNT(DISTINCT o.id) AS orders, SUM($net) AS qty, SUM($net * oi.unit_price) AS sales,
                    MAX(g.sort_order) AS sort_order
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
               LEFT JOIN grade_levels g ON g.code = oi.grade_label
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL AND oi.grade_label IS NOT NULL$paidSql
              GROUP BY $group
             HAVING qty > 0
              ORDER BY sort_order, " . ($byClass ? 'class_name' : 'grade'),
            array_merge([(int) $vendorId, (int) $cooperativeId], $paidArgs)
        );
        return array_map(function ($row) use ($byClass) {
            $data = ['grade' => $row['grade']];
            if ($byClass) {
                $data['class_name'] = (string) $row['class_name'];
            }
            $students = (int) $row['students'];
            return $data + [
                'students' => $students,
                'orders'   => (int) $row['orders'],
                'qty'      => (int) $row['qty'],
                'sales'    => (int) $row['sales'],
                'average'  => $students ? (int) round((int) $row['sales'] / $students) : 0,
            ];
        }, $result);
    }

    public static function report(array $rows, $byClass)
    {
        $totals = ReportRange::sum($rows, ['students', 'orders', 'qty', 'sales']);
        $totals['average'] = $totals['students'] ? (int) round($totals['sales'] / $totals['students']) : 0;
        return [
            'columns' => array_merge([['grade', 'grade']], $byClass ? [['class_name', 'text']] : [],
                [['students', 'int'], ['orders', 'int'], ['qty', 'int'], ['sales', 'money'], ['average', 'money']]),
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => $byClass ? null : array_map(function ($row) {
                return ['label' => $row['grade'], 'value' => $row['sales'], 'format' => 'grade'];
            }, $rows),
        ];
    }
}
