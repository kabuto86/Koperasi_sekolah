<?php
// Ringkasan syif / laporan Z (vendor/pos/shift-summary) — mana-mana syif koperasi ini (vendor sesi).
class ShiftReportJob
{
    private $vendorId;
    private $cooperativeId;
    private $shiftId;

    public function __construct($vendorId, $cooperativeId, $shiftId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->shiftId = (int) $shiftId;
    }

    public function run()
    {
        return PosShifts::summary(PosShifts::require($this->vendorId, $this->cooperativeId, $this->shiftId));
    }
}
