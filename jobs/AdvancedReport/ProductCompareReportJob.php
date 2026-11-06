<?php
// Produk tahun ini vs tahun lepas (type product_compare): unit bersih & jualan setiap produk/varian bagi kedua-dua tahun,
// perubahan % jualan. Disusun ikut jualan tahun ini.
class ProductCompareReportJob
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
        $from = ($this->year - 1) . '-01-01 00:00:00';
        $to = ($this->year + 1) . '-01-01 00:00:00';
        $result = Database::fetchAll(
            "SELECT MAX(oi.product_name) AS product_name, MAX(oi.variant_label) AS variant_label,
                    SUM(CASE WHEN YEAR(o.paid_at) = ? THEN $net ELSE 0 END) AS qty,
                    SUM(CASE WHEN YEAR(o.paid_at) = ? THEN $net * oi.unit_price ELSE 0 END) AS sales,
                    SUM(CASE WHEN YEAR(o.paid_at) = ? THEN $net ELSE 0 END) AS qty_prev,
                    SUM(CASE WHEN YEAR(o.paid_at) = ? THEN $net * oi.unit_price ELSE 0 END) AS sales_prev
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at >= ? AND o.paid_at < ?
              GROUP BY oi.product_id, oi.variant_id
             HAVING qty > 0 OR qty_prev > 0
              ORDER BY sales DESC, sales_prev DESC, product_name",
            [$this->year, $this->year, $this->year - 1, $this->year - 1, $this->vendorId, $this->cooperativeId, $from, $to]
        );
        $rows = array_map(function ($row) {
            return [
                'product'    => $row['product_name'] . ($row['variant_label'] ? ' — ' . $row['variant_label'] : ''),
                'qty_prev'   => (int) $row['qty_prev'],
                'sales_prev' => (int) $row['sales_prev'],
                'qty_cur'    => (int) $row['qty'],
                'sales_cur'  => (int) $row['sales'],
                'change'     => RunAdvancedReportJob::change((int) $row['sales'], (int) $row['sales_prev']),
            ];
        }, $result);
        $totals = ReportRange::sum($rows, ['qty_prev', 'sales_prev', 'qty_cur', 'sales_cur']);
        $totals['change'] = RunAdvancedReportJob::change($totals['sales_cur'], $totals['sales_prev']);
        return [
            'columns' => [['product', 'text'], ['qty_prev', 'int'], ['sales_prev', 'money'], ['qty_cur', 'int'], ['sales_cur', 'money'], ['change', 'percent']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => $row['product'], 'value' => $row['sales_cur']];
            }, array_slice($rows, 0, 8)),
        ];
    }
}
