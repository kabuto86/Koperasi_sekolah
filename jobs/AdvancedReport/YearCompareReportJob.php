<?php
// Perbandingan tahun (type year_compare): 12 baris bulan — pesanan & jualan bersih tahun ini vs tahun lepas (PeriodSales:
// pesanan dibayar ikut bulan bayaran, refund ikut bulan refund).
class YearCompareReportJob
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
        $current = $this->byMonth($this->year);
        $previous = $this->byMonth($this->year - 1);
        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $key = sprintf('%02d', $month);
            $now = $current[$key] ?? ['orders' => 0, 'net' => 0];
            $before = $previous[$key] ?? ['orders' => 0, 'net' => 0];
            $rows[] = [
                'month'        => $this->year . '-' . $key,
                'orders_prev'  => $before['orders'],
                'net_prev'     => $before['net'],
                'orders_cur'   => $now['orders'],
                'net_cur'      => $now['net'],
                'change'       => RunAdvancedReportJob::change($now['net'], $before['net']),
            ];
        }
        $totals = ReportRange::sum($rows, ['orders_prev', 'net_prev', 'orders_cur', 'net_cur']);
        $totals['change'] = RunAdvancedReportJob::change($totals['net_cur'], $totals['net_prev']);
        return [
            'columns' => [['month', 'month'], ['orders_prev', 'int'], ['net_prev', 'money'], ['orders_cur', 'int'], ['net_cur', 'money'], ['change', 'percent']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => $row['month'], 'value' => $row['net_cur'], 'format' => 'month'];
            }, $rows),
        ];
    }

    // ['MM' => [orders, net]]
    private function byMonth($year)
    {
        list($from, $to) = RunAdvancedReportJob::yearRange($year);
        $result = [];
        foreach (PeriodSales::rows($this->vendorId, $this->cooperativeId, $from, $to, '%Y-%m', 'month') as $row) {
            $result[substr($row['month'], 5, 2)] = ['orders' => $row['orders'], 'net' => $row['net']];
        }
        return $result;
    }
}
