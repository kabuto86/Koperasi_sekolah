<?php
// Cadangan nama kelas (datalist borang anak) — PELAN 18.12 store/classes.
class ListStoreClassesJob
{
    private $cooperative;
    private $grade;

    public function __construct(array $cooperative, $grade)
    {
        $this->cooperative = $cooperative;
        $this->grade = (string) $grade;
    }

    public function run()
    {
        $where = '';
        $args = [(int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']];
        if ($this->grade !== '') {
            $where = ' AND sc.grade_level_code = ?';
            $args[] = $this->grade;
        }
        return Database::fetchAll(
            "SELECT sc.grade_level_code, sc.class_name FROM school_classes sc JOIN grade_levels g ON g.code = sc.grade_level_code
              WHERE sc.vendor_id = ? AND sc.cooperative_id = ? $where
              ORDER BY g.sort_order, sc.sort_order, sc.class_name",
            $args
        );
    }
}
