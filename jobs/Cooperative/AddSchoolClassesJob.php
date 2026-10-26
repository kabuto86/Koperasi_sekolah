<?php
// Tambah beberapa kelas sekali gus untuk satu darjah: { grade_level_code, names: ['Bestari', 'Cemerlang'] }.
// Darjah mesti sesuai dengan jenis sekolah; nama pendua (dalam input atau sedia ada, tanpa beza huruf besar) ditolak.
class AddSchoolClassesJob
{
    private $vendorId;
    private $cooperative;
    private $input;

    // $cooperative = baris dari VendorAuth::requireCooperative()
    public function __construct($vendorId, array $cooperative, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->input = $input;
    }

    public function run()
    {
        $grade = (string) ($this->input['grade_level_code'] ?? '');
        $names = self::cleanNames($this->input['names'] ?? []);

        $v = new Validator($this->input);
        $v->required('grade_level_code')->inList('grade_level_code', (new ListCooperativeGradesJob($this->cooperative['school_type']))->run());
        if (!$names) {
            $v->addError('names', t('error.required'));
        }
        foreach ($names as $name) {
            if (mb_strlen($name, 'UTF-8') > 50) {
                $v->addError('names', t('error.max_length', ['max' => 50]));
            }
        }
        ValidationException::throwIfFails($v);

        $existing = Database::fetchAll(
            "SELECT class_name FROM school_classes WHERE vendor_id = ? AND cooperative_id = ? AND grade_level_code = ?",
            [$this->vendorId, (int) $this->cooperative['id'], $grade]
        );
        $taken = array_map([self::class, 'key'], array_column($existing, 'class_name'));
        $duplicates = array_values(array_filter($names, function ($name) use ($taken) {
            return in_array(self::key($name), $taken, true);
        }));
        if ($duplicates) {
            $message = t('class.duplicate', ['names' => implode(', ', $duplicates)]);
            throw new ValidationException($message, ['names' => $message]);
        }

        $sort = count($existing);
        foreach ($names as $name) {
            $sort++;
            Database::insert(
                "INSERT INTO school_classes (vendor_id, cooperative_id, grade_level_code, class_name, sort_order) VALUES (?, ?, ?, ?, ?)",
                [$this->vendorId, (int) $this->cooperative['id'], $grade, $name, $sort]
            );
        }
        return ['added' => count($names)];
    }

    // Buang kosong & pendua dalam input yang sama
    private static function cleanNames($names)
    {
        $result = [];
        foreach (is_array($names) ? $names : [] as $name) {
            $name = trim((string) (is_scalar($name) ? $name : ''));
            if ($name !== '' && !in_array(self::key($name), array_map([self::class, 'key'], $result), true)) {
                $result[] = $name;
            }
        }
        return $result;
    }

    public static function key($name)
    {
        return mb_strtolower(trim((string) $name), 'UTF-8');
    }
}
