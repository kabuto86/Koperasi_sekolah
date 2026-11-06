<?php
// Modul add-on advanced_report (W4): vendor/advanced-report/run — perbandingan tahun (bulanan & produk) dan analisis kit.
// Input: type, year (tahun semasa laporan; dibanding dengan year − 1). Bentuk jawapan sama seperti vendor/reports/run
// { type, school_name, year, compare_year, generated_at, columns, rows, totals, chart } supaya pemapar & CSV dikongsi.
// Koperasi sudah disahkan milik vendor sesi + modul dilanggan; setiap job menapis vendor_id + cooperative_id.
class RunAdvancedReportJob
{
    const TYPES = [
        'year_compare'    => 'YearCompareReportJob',
        'product_compare' => 'ProductCompareReportJob',
        'kit_summary'     => 'KitSummaryReportJob',
        'kit_items'       => 'KitItemsReportJob',
    ];
    const MIN_YEAR = 2020;

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
        $year = filter_var($this->params['year'] ?? null, FILTER_VALIDATE_INT);
        $v = new Validator($this->params);
        if (!isset(self::TYPES[$type])) {
            $v->addError('type', t('error.invalid'));
        }
        if ($year === false || $year < self::MIN_YEAR || $year > (int) date('Y') + 1) {
            $v->addError('year', t('advreport.year_invalid'));
        }
        ValidationException::throwIfFails($v);

        $class = self::TYPES[$type];
        $report = (new $class($this->vendorId, (int) $this->cooperative['id'], $year))->run();
        return [
            'type'         => $type,
            'school_name'  => $this->cooperative['school_name'],
            'year'         => $year,
            'compare_year' => $year - 1,
            'generated_at' => IsoDate::format(date('Y-m-d H:i:s')),
            'columns'      => ReportRange::columns($report['columns']),
            'rows'         => $report['rows'],
            'totals'       => (object) $report['totals'],
            'chart'        => $report['chart'],
        ];
    }

    // [awal tahun, akhir tahun] 'YYYY-MM-DD'
    public static function yearRange($year)
    {
        return [$year . '-01-01', $year . '-12-31'];
    }

    // Perubahan % (null jika tahun lepas 0 — tiada asas perbandingan)
    public static function change($current, $previous)
    {
        return $previous ? round(($current - $previous) / $previous * 100, 1) : null;
    }
}
