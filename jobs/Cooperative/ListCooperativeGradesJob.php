<?php
// Darjah/tingkatan ikut jenis sekolah (PELAN 18.8): SK/SJKC/SJKT → PRA–D6 · SMK/SMKA/SBP → T1–T6 · lain-lain → semua.
class ListCooperativeGradesJob
{
    const PRIMARY_TYPES = ['SK', 'SJKC', 'SJKT'];
    const SECONDARY_TYPES = ['SMK', 'SMKA', 'SBP'];

    private $schoolType;

    public function __construct($schoolType)
    {
        $this->schoolType = (string) $schoolType;
    }

    // ['PRA', 'D1', …] ikut sort_order
    public function run()
    {
        if (in_array($this->schoolType, self::PRIMARY_TYPES, true)) {
            $where = "WHERE level_type IN ('preschool', 'primary')";
        } elseif (in_array($this->schoolType, self::SECONDARY_TYPES, true)) {
            $where = "WHERE level_type = 'secondary'";
        } else {
            $where = '';
        }
        $rows = Database::fetchAll("SELECT code FROM grade_levels $where ORDER BY sort_order");
        return array_column($rows, 'code');
    }
}
