<?php
// Analisis item kit (type kit_items): untuk setiap item kit tahun dipilih — berapa kali dibeli bersama kit (pasangan pesanan + anak),
// kadar "uncheck" (ibu bapa membuang item dari kit) dan purata kuantiti. Membantu vendor menilai item pilihan / wajib.
class KitItemsReportJob
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
        $net = ReportRange::NET_QTY;
        $items = Database::fetchAll(
            "SELECT k.id AS kit_id, k.grade_level_code, ki.product_id, ki.is_mandatory, p.name AS product_name, gl.sort_order, ki.sort_order AS item_order
               FROM school_kit_items ki
               JOIN school_kits k ON k.id = ki.school_kit_id AND k.vendor_id = ki.vendor_id
               JOIN products p ON p.id = ki.product_id AND p.vendor_id = ki.vendor_id
               JOIN grade_levels gl ON gl.code = k.grade_level_code
              WHERE k.vendor_id = ? AND k.cooperative_id = ? AND k.school_year = ?
              ORDER BY gl.sort_order, ki.sort_order, ki.id",
            [$this->vendorId, $this->cooperativeId, $this->year]
        );
        $purchases = $this->countBy(
            "SELECT oi.school_kit_id AS kit_id, COUNT(DISTINCT oi.order_id, COALESCE(oi.child_id, 0)) AS total",
            "GROUP BY oi.school_kit_id"
        );
        $included = Database::fetchAll(
            "SELECT oi.school_kit_id AS kit_id, oi.product_id, COUNT(DISTINCT oi.order_id, COALESCE(oi.child_id, 0)) AS times, SUM($net) AS qty
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
               JOIN school_kits k ON k.id = oi.school_kit_id AND k.vendor_id = oi.vendor_id AND k.cooperative_id = o.cooperative_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL AND oi.source = 'kit' AND k.school_year = ?
              GROUP BY oi.school_kit_id, oi.product_id",
            [$this->vendorId, $this->cooperativeId, $this->year]
        );
        $byItem = [];
        foreach ($included as $row) {
            $byItem[$row['kit_id'] . '#' . $row['product_id']] = ['times' => (int) $row['times'], 'qty' => (int) $row['qty']];
        }

        $rows = [];
        foreach ($items as $item) {
            $total = $purchases[$item['kit_id']] ?? 0;
            $stats = $byItem[$item['kit_id'] . '#' . $item['product_id']] ?? ['times' => 0, 'qty' => 0];
            $rows[] = [
                'grade'        => $item['grade_level_code'],
                'product'      => $item['product_name'],
                'mandatory'    => (int) $item['is_mandatory'] === 1 ? t('kit.mandatory') : t('advreport.optional'),
                'purchases'    => $total,
                'included'     => $stats['times'],
                'uncheck_rate' => $total ? ReportRange::percent($total - $stats['times'], $total) : 0,
                'avg_qty'      => $stats['times'] ? round($stats['qty'] / $stats['times'], 1) : 0,
            ];
        }
        usort($rows, function ($a, $b) {
            return $b['uncheck_rate'] <=> $a['uncheck_rate'];
        });
        $totals = ReportRange::sum($rows, ['included']);
        return [
            'columns' => [['grade', 'grade'], ['product', 'text'], ['mandatory', 'text'], ['purchases', 'int'], ['included', 'int'],
                ['uncheck_rate', 'percent'], ['avg_qty', 'int']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => $row['product'], 'value' => $row['uncheck_rate']];
            }, array_slice(array_values(array_filter($rows, function ($row) {
                return $row['uncheck_rate'] > 0;
            })), 0, 8)),
        ];
    }

    // [kit_id => jumlah] pembelian kit tahun ini (pesanan dibayar)
    private function countBy($select, $group)
    {
        $rows = Database::fetchAll(
            "$select
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
               JOIN school_kits k ON k.id = oi.school_kit_id AND k.vendor_id = oi.vendor_id AND k.cooperative_id = o.cooperative_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL AND oi.source = 'kit' AND k.school_year = ?
              $group",
            [$this->vendorId, $this->cooperativeId, $this->year]
        );
        return array_map('intval', array_column($rows, 'total', 'kit_id'));
    }
}
