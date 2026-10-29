<?php
// Laporan untung kasar ikut kategori (type profit): sama seperti ProductSalesReportJob tetapi dikumpul ikut kategori produk
// semasa (produk tanpa kategori → "Tiada kategori"). Untung kasar = jualan − kos barang.
class ProfitReportJob
{
    private $vendorId;
    private $cooperativeId;
    private $from;
    private $to;

    public function __construct($vendorId, $cooperativeId, $from, $to)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->from = $from;
        $this->to = $to;
    }

    public function run()
    {
        $net = ReportRange::NET_QTY;
        list($paidSql, $paidArgs) = ReportRange::between('o.paid_at', $this->from, $this->to);
        $result = Database::fetchAll(
            "SELECT c.name AS category, SUM($net) AS qty, SUM($net * oi.unit_price) AS sales, SUM($net * p.cost_price) AS cost
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
               JOIN products p ON p.id = oi.product_id AND p.vendor_id = oi.vendor_id AND p.cooperative_id = o.cooperative_id
               LEFT JOIN categories c ON c.id = p.category_id AND c.vendor_id = p.vendor_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL$paidSql
              GROUP BY p.category_id, c.name
             HAVING qty > 0",
            array_merge([$this->vendorId, $this->cooperativeId], $paidArgs)
        );
        $rows = array_map(function ($row) {
            $sales = (int) $row['sales'];
            $profit = $sales - (int) $row['cost'];
            return [
                'category' => $row['category'] === null ? t('report.no_category') : $row['category'],
                'qty'      => (int) $row['qty'],
                'sales'    => $sales,
                'cost'     => (int) $row['cost'],
                'profit'   => $profit,
                'margin'   => ReportRange::percent($profit, $sales),
            ];
        }, $result);
        usort($rows, function ($a, $b) {
            return $b['profit'] <=> $a['profit'] ?: strcmp($a['category'], $b['category']);
        });
        $totals = ReportRange::sum($rows, ['qty', 'sales', 'cost', 'profit']);
        $totals['margin'] = ReportRange::percent($totals['profit'], $totals['sales']);
        return [
            'columns' => [['category', 'text'], ['qty', 'int'], ['sales', 'money'], ['cost', 'money'], ['profit', 'money'], ['margin', 'percent']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => $row['category'], 'value' => $row['profit']];
            }, $rows),
        ];
    }
}
