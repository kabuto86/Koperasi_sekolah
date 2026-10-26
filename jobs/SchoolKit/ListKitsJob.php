<?php
// Senarai kit koperasi untuk satu tahun persekolahan { school_year } (PELAN 18.9).
// → { school_year, grades, previous_year_count, kits: [KitSummary] }
class ListKitsJob
{
    private $vendorId;
    private $cooperativeId;
    private $schoolType;
    private $input;

    public function __construct($vendorId, $cooperativeId, $schoolType, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->schoolType = (string) $schoolType;
        $this->input = $input;
    }

    public function run()
    {
        $year = (int) ($this->input['school_year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y') + 1;
        }
        $kits = Database::fetchAll(
            "SELECT k.* FROM school_kits k JOIN grade_levels g ON g.code = k.grade_level_code
              WHERE k.vendor_id = ? AND k.cooperative_id = ? AND k.school_year = ? ORDER BY g.sort_order",
            [$this->vendorId, $this->cooperativeId, $year]
        );
        $previous = Database::fetchValue(
            "SELECT COUNT(*) FROM school_kits WHERE vendor_id = ? AND cooperative_id = ? AND school_year = ?",
            [$this->vendorId, $this->cooperativeId, $year - 1]
        );
        return [
            'school_year'         => $year,
            'grades'              => (new ListCooperativeGradesJob($this->schoolType))->run(),
            'previous_year_count' => (int) $previous,
            'kits'                => SchoolKits::summaries($this->vendorId, $kits),
        ];
    }
}
