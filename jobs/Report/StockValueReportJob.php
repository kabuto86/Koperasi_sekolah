<?php
// Laporan nilai stok (type stock) — baki SEMASA, bukan ikut tarikh. Nilai kos = baki × harga kos; nilai jualan = baki × (harga + beza varian).
// Produk bervarian: baris varian sahaja; produk tanpa varian: baris variant_id 0.
class StockValueReportJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $result = Database::fetchAll(
            "SELECT p.name, v.label, COALESCE(v.sku, p.sku) AS sku, s.qty_on_hand, s.qty_reserved, p.cost_price,
                    p.price + COALESCE(v.price_delta, 0) AS price
               FROM stock_levels s
               JOIN products p ON p.id = s.product_id AND p.vendor_id = s.vendor_id AND p.cooperative_id = s.cooperative_id
               LEFT JOIN product_variants v ON v.id = s.variant_id AND v.vendor_id = s.vendor_id AND v.product_id = s.product_id
              WHERE s.vendor_id = ? AND s.cooperative_id = ?
                AND ((p.has_variants = 0 AND s.variant_id = 0) OR (p.has_variants = 1 AND v.id IS NOT NULL))",
            [$this->vendorId, $this->cooperativeId]
        );
        $rows = array_map(function ($row) {
            $onHand = (int) $row['qty_on_hand'];
            return [
                'product'      => $row['name'] . ($row['label'] ? ' — ' . $row['label'] : ''),
                'sku'          => (string) $row['sku'],
                'on_hand'      => $onHand,
                'reserved'     => (int) $row['qty_reserved'],
                'available'    => $onHand - (int) $row['qty_reserved'],
                'cost_value'   => $onHand * (int) $row['cost_price'],
                'retail_value' => $onHand * (int) $row['price'],
            ];
        }, $result);
        usort($rows, function ($a, $b) {
            return $b['retail_value'] <=> $a['retail_value'] ?: strcmp($a['product'], $b['product']);
        });
        return [
            'columns' => [['product', 'text'], ['sku', 'text'], ['on_hand', 'int'], ['reserved', 'int'], ['available', 'int'],
                ['cost_value', 'money'], ['retail_value', 'money']],
            'rows'    => $rows,
            'totals'  => ReportRange::sum($rows, ['on_hand', 'reserved', 'available', 'cost_value', 'retail_value']),
            'chart'   => null,
        ];
    }
}
