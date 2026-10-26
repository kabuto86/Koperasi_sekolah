<?php
// Kelas koperasi (PELAN 18.8) — disusun ikut darjah kemudian sort_order. Koperasi sudah disahkan oleh pemanggil.
class ListSchoolClassesJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT sc.id, sc.grade_level_code, sc.class_name, sc.sort_order
               FROM school_classes sc JOIN grade_levels g ON g.code = sc.grade_level_code
              WHERE sc.vendor_id = ? AND sc.cooperative_id = ?
              ORDER BY g.sort_order, sc.sort_order, sc.class_name",
            [$this->vendorId, $this->cooperativeId]
        );
        return array_map([self::class, 'shape'], $rows);
    }

    public static function find($vendorId, $cooperativeId, $id)
    {
        $row = Database::fetchOne(
            "SELECT id, grade_level_code, class_name, sort_order FROM school_classes WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [(int) $vendorId, (int) $cooperativeId, (int) $id]
        );
        return $row === null ? null : self::shape($row);
    }

    public static function shape($row)
    {
        return [
            'id'               => (int) $row['id'],
            'grade_level_code' => $row['grade_level_code'],
            'class_name'       => $row['class_name'],
            'sort_order'       => (int) $row['sort_order'],
        ];
    }
}
