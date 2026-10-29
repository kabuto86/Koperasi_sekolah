<?php
// Laporan jualan harian (type daily) — satu baris setiap hari yang ada jualan atau refund
class DailySalesReportJob
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
        $rows = PeriodSales::rows($this->vendorId, $this->cooperativeId, $this->from, $this->to, '%Y-%m-%d', 'date');
        return [
            'columns' => array_merge([['date', 'date']], PeriodSales::COLUMNS),
            'rows'    => $rows,
            'totals'  => ReportRange::sum($rows, PeriodSales::TOTAL_KEYS),
            'chart'   => array_map(function ($row) {
                return ['label' => substr($row['date'], 8, 2) . '/' . substr($row['date'], 5, 2), 'value' => $row['net']];
            }, $rows),
        ];
    }
}
