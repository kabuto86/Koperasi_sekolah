<?php
// vendor/reports/run (PELAN 18.10 "Laporan"): pilih job laporan ikut type, sahkan julat tarikh (kecuali stock),
// bungkus jawapan { type, school_name, date_from, date_to, generated_at, columns, rows, totals, chart }.
// Koperasi sudah disahkan milik vendor sesi (VendorAuth::requireCooperative); setiap job menapis vendor_id + cooperative_id.
class RunReportJob
{
    const TYPES = [
        'daily'   => 'DailySalesReportJob',
        'monthly' => 'MonthlySalesReportJob',
        'payment' => 'PaymentMethodReportJob',
        'product' => 'ProductSalesReportJob',
        'profit'  => 'ProfitReportJob',
        'stock'   => 'StockValueReportJob',
        'grade'   => 'GradeSalesReportJob',
        'class'   => 'ClassSalesReportJob',
    ];

    private $vendorId;
    private $cooperative;
    private $params;

    public function __construct($vendorId, array $cooperative, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->params = $params;
    }

    public function run()
    {
        $type = (string) ($this->params['type'] ?? '');
        if (!isset(self::TYPES[$type])) {
            throw new ValidationException(t('error.invalid'), ['type' => t('error.invalid')]);
        }
        $class = self::TYPES[$type];
        $cooperativeId = (int) $this->cooperative['id'];
        if ($type === 'stock') {
            $from = null;
            $to = null;
            $report = (new $class($this->vendorId, $cooperativeId))->run();
        } else {
            list($from, $to) = ReportRange::dates($this->params);
            $report = (new $class($this->vendorId, $cooperativeId, $from, $to))->run();
        }
        return [
            'type'         => $type,
            'school_name'  => $this->cooperative['school_name'],
            'date_from'    => $from,
            'date_to'      => $to,
            'generated_at' => IsoDate::format(date('Y-m-d H:i:s')),
            'columns'      => ReportRange::columns($report['columns']),
            'rows'         => $report['rows'],
            'totals'       => (object) $report['totals'],
            'chart'        => $report['chart'],
        ];
    }
}
