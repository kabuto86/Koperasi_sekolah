<?php
// Analisis kit (type kit_summary): setiap kit tahun persekolahan dipilih — bilangan item, pembelian kit (pasangan pesanan + anak
// yang mempunyai item kit itu, pesanan dibayar), jualan bersih item kit, purata setiap pembelian, dan pembelian kit darjah sama
// tahun lepas untuk perbandingan.
class KitSummaryReportJob
{
    private $vendorId;
    private $cooperativeId;
    private $year;

    public function __construct($vendorId, $cooperativeId, $year)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->year = (int) $year;
    }

    public function run()
    {
        $kits = Database::fetchAll(
            "SELECT k.id, k.grade_level_code, k.name, k.is_published, gl.sort_order,
                    (SELECT COUNT(*) FROM school_kit_items ki WHERE ki.school_kit_id = k.id AND ki.vendor_id = k.vendor_id) AS item_count
               FROM school_kits k JOIN grade_levels gl ON gl.code = k.grade_level_code
              WHERE k.vendor_id = ? AND k.cooperative_id = ? AND k.school_year = ?
              ORDER BY gl.sort_order",
            [$this->vendorId, $this->cooperativeId, $this->year]
        );
        $current = $this->purchases($this->year);
        $previous = $this->purchases($this->year - 1);
        $rows = [];
        foreach ($kits as $kit) {
            $stats = $current[$kit['grade_level_code']] ?? ['purchases' => 0, 'sales' => 0];
            $rows[] = [
                'grade'          => $kit['grade_level_code'],
                'kit'            => (string) $kit['name'] !== '' ? $kit['name'] : '—',
                'items'          => (int) $kit['item_count'],
                'purchases_prev' => $previous[$kit['grade_level_code']]['purchases'] ?? 0,
                'purchases_cur'  => $stats['purchases'],
                'sales'          => $stats['sales'],
                'avg_purchase'   => $stats['purchases'] ? (int) round($stats['sales'] / $stats['purchases']) : 0,
            ];
        }
        $totals = ReportRange::sum($rows, ['items', 'purchases_prev', 'purchases_cur', 'sales']);
        $totals['avg_purchase'] = $totals['purchases_cur'] ? (int) round($totals['sales'] / $totals['purchases_cur']) : 0;
        return [
            'columns' => [['grade', 'grade'], ['kit', 'text'], ['items', 'int'], ['purchases_prev', 'int'], ['purchases_cur', 'int'], ['sales', 'money'],
                ['avg_purchase', 'money']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => $row['grade'], 'value' => $row['sales'], 'format' => 'grade'];
            }, $rows),
        ];
    }

    // [kod darjah => [purchases, sales]] untuk kit tahun $year (pesanan dibayar sahaja)
    private function purchases($year)
    {
        $net = ReportRange::NET_QTY;
        $rows = Database::fetchAll(
            "SELECT k.grade_level_code, COUNT(DISTINCT oi.order_id, COALESCE(oi.child_id, 0)) AS purchases, SUM($net * oi.unit_price) AS sales
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
               JOIN school_kits k ON k.id = oi.school_kit_id AND k.vendor_id = oi.vendor_id AND k.cooperative_id = o.cooperative_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL AND oi.source = 'kit' AND k.school_year = ?
              GROUP BY k.grade_level_code",
            [$this->vendorId, $this->cooperativeId, $year]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['grade_level_code']] = ['purchases' => (int) $row['purchases'], 'sales' => (int) $row['sales']];
        }
        return $result;
    }
}
