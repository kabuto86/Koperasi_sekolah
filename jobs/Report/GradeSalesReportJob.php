<?php
// Laporan jualan ikut darjah (type grade). Lihat ChildSales
class GradeSalesReportJob
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
        $rows = ChildSales::rows($this->vendorId, $this->cooperativeId, $this->from, $this->to, false);
        return ChildSales::report($rows, false);
    }
}
