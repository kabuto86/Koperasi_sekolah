<?php
// Laporan jualan ikut produk (type product): unit bersih (selepas batal & pulang) × harga unit pesanan;
// kos = unit bersih × harga kos produk SEMASA (tiada salinan kos dalam pesanan). Margin = untung ÷ jualan.
class ProductSalesReportJob
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
            "SELECT MAX(oi.product_name) AS product_name, MAX(oi.variant_label) AS variant_label, MAX(oi.sku) AS sku,
                    SUM($net) AS qty, SUM($net * oi.unit_price) AS sales, SUM($net * p.cost_price) AS cost
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
               JOIN products p ON p.id = oi.product_id AND p.vendor_id = oi.vendor_id AND p.cooperative_id = o.cooperative_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL$paidSql
              GROUP BY oi.product_id, oi.variant_id
             HAVING qty > 0
              ORDER BY sales DESC, product_name",
            array_merge([$this->vendorId, $this->cooperativeId], $paidArgs)
        );
        $rows = array_map(function ($row) {
            $sales = (int) $row['sales'];
            $profit = $sales - (int) $row['cost'];
            return [
                'product' => $row['product_name'] . ($row['variant_label'] ? ' — ' . $row['variant_label'] : ''),
                'sku'     => (string) $row['sku'],
                'qty'     => (int) $row['qty'],
                'sales'   => $sales,
                'cost'    => (int) $row['cost'],
                'profit'  => $profit,
                'margin'  => ReportRange::percent($profit, $sales),
            ];
        }, $result);
        $totals = ReportRange::sum($rows, ['qty', 'sales', 'cost', 'profit']);
        $totals['margin'] = ReportRange::percent($totals['profit'], $totals['sales']);
        return [
            'columns' => [['product', 'text'], ['sku', 'text'], ['qty', 'int'], ['sales', 'money'], ['cost', 'money'], ['profit', 'money'], ['margin', 'percent']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => $row['sku'] !== '' ? $row['sku'] : $row['product'], 'value' => $row['sales']];
            }, array_slice($rows, 0, 8)),
        ];
    }
}
