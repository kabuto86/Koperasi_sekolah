<?php
// Pembantu anak ibu bapa (PELAN 5.4, 18.12). customer_id SENTIASA dari sesi; setiap pertanyaan menapis customer_id.
class Children
{
    const MAX_PER_CUSTOMER = 20;

    // Anak aktif milik ibu bapa sesi (+ slug & jenis sekolah), atau null.
    // $cooperative (pilihan) = anak mesti di koperasi slug ini (vendor_id dari baris koperasi, bukan input)
    public static function find($customerId, $childId, $cooperative = null, $forUpdate = false)
    {
        $sql = "SELECT ch.*, c.slug AS school_slug, c.school_name, c.school_type
                  FROM children ch JOIN cooperatives c ON c.id = ch.cooperative_id AND c.vendor_id = ch.vendor_id
                 WHERE ch.customer_id = ? AND ch.id = ? AND ch.is_active = 1";
        $args = [(int) $customerId, (int) $childId];
        if ($cooperative !== null) {
            $sql .= ' AND ch.vendor_id = ? AND ch.cooperative_id = ?';
            array_push($args, (int) $cooperative['vendor_id'], (int) $cooperative['id']);
        }
        return Database::fetchOne($sql . ($forUpdate ? ' FOR UPDATE' : ''), $args);
    }

    // Semua anak aktif ibu bapa (semua sekolah), atau di satu koperasi sahaja
    public static function all($customerId, $cooperative = null)
    {
        $sql = "SELECT ch.*, c.slug AS school_slug, c.school_name, c.school_type
                  FROM children ch JOIN cooperatives c ON c.id = ch.cooperative_id AND c.vendor_id = ch.vendor_id
                 WHERE ch.customer_id = ? AND ch.is_active = 1";
        $args = [(int) $customerId];
        if ($cooperative !== null) {
            $sql .= ' AND ch.vendor_id = ? AND ch.cooperative_id = ?';
            array_push($args, (int) $cooperative['vendor_id'], (int) $cooperative['id']);
        }
        return Database::fetchAll($sql . ' ORDER BY ch.id', $args);
    }

    public static function publicShape(array $row)
    {
        return [
            'id'               => (int) $row['id'],
            'full_name'        => $row['full_name'],
            'school_slug'      => $row['school_slug'],
            'school_name'      => $row['school_name'],
            'grade_level_code' => $row['grade_level_code'],
            'class_name'       => (string) $row['class_name'],
            'school_year'      => (int) $row['school_year'],
            'promote_to'       => self::nextGrade($row),
        ];
    }

    // Cadangan naik darjah: musim pre-order (Okt–Dis) tahun yang sama, atau tahun persekolahan sudah lepas.
    // Darjah terakhir sekolah (D6 / T6) → tiada cadangan
    public static function nextGrade(array $row)
    {
        $year = (int) date('Y');
        $schoolYear = (int) $row['school_year'];
        $due = $schoolYear < $year || ($schoolYear === $year && (int) date('n') >= 10);
        $grades = (new ListCooperativeGradesJob($row['school_type']))->run();
        $index = array_search($row['grade_level_code'], $grades, true);
        if (!$due || $index === false || $index === count($grades) - 1) {
            return null;
        }
        return ['grade_level_code' => $grades[$index + 1], 'school_year' => $schoolYear + 1];
    }

    // Kelas sekolah yang sepadan (tanpa beza huruf besar/kecil) → [school_class_id, class_name rasmi], jika tiada → [null, nama ditaip]
    public static function matchClass(array $cooperative, $grade, $className)
    {
        $className = trim((string) $className);
        $row = Database::fetchOne(
            "SELECT id, class_name FROM school_classes
              WHERE vendor_id = ? AND cooperative_id = ? AND grade_level_code = ? AND LOWER(class_name) = LOWER(?)",
            [(int) $cooperative['vendor_id'], (int) $cooperative['id'], (string) $grade, $className]
        );
        return $row ? [(int) $row['id'], $row['class_name']] : [null, $className];
    }

    // Buang baris troli anak ini (anak dibuang / bertukar sekolah). $exceptCooperativeId = troli koperasi yang dikekalkan
    public static function removeFromCarts($customerId, $childId, $exceptCooperativeId = 0)
    {
        Database::execute(
            "DELETE ci FROM cart_items ci JOIN carts ca ON ca.id = ci.cart_id
              WHERE ca.customer_id = ? AND ci.child_id = ? AND ca.cooperative_id <> ?",
            [(int) $customerId, (int) $childId, (int) $exceptCooperativeId]
        );
    }
}
