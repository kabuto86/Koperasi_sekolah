<?php
// Laporan jualan bulanan (type monthly) — satu baris setiap bulan 'YYYY-MM'
class MonthlySalesReportJob
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
        $rows = PeriodSales::rows($this->vendorId, $this->cooperativeId, $this->from, $this->to, '%Y-%m', 'month');
        return [
            'columns' => array_merge([['month', 'month']], PeriodSales::COLUMNS),
            'rows'    => $rows,
            'totals'  => ReportRange::sum($rows, PeriodSales::TOTAL_KEYS),
            'chart'   => array_map(function ($row) {
                return ['label' => $row['month'], 'value' => $row['net'], 'format' => 'month'];
            }, $rows),
        ];
    }
}
