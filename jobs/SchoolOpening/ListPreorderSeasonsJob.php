<?php
// Senarai musim pre-order + darjah koperasi (vendor/school-opening/seasons).
class ListPreorderSeasonsJob
{
    private $vendorId;
    private $cooperative;

    // $cooperative = baris VendorAuth::requireCooperative (milik vendor sesi)
    public function __construct($vendorId, array $cooperative)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        return [
            'items'  => PreorderSeasons::all($this->vendorId, $this->cooperative['id']),
            'grades' => (new ListCooperativeGradesJob($this->cooperative['school_type']))->run(),
        ];
    }
}
