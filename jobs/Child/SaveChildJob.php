<?php
// Tambah / sunting anak { id?, full_name, school_slug, grade_level_code, class_name, school_year } (PELAN 5.4, 18.12).
// Sekolah dari slug (koperasi aktif); cooperative_id & vendor_id diambil dari baris koperasi — tidak pernah dari input.
class SaveChildJob
{
    private $customerId;
    private $input;

    public function __construct($customerId, array $input)
    {
        $this->customerId = (int) $customerId;
        $this->input = $input;
    }

    public function run()
    {
        // Teks dipangkas dahulu (nama berisi ruang sahaja = kosong); nilai bukan teks dianggap kosong
        $input = $this->input;
        foreach (['full_name', 'school_slug', 'grade_level_code', 'class_name', 'school_year'] as $field) {
            $input[$field] = is_scalar($input[$field] ?? null) ? trim((string) $input[$field]) : '';
        }
        $v = new Validator($input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('school_slug')
          ->required('grade_level_code')
          ->required('class_name')->maxLength('class_name', 50)
          ->required('school_year');

        $cooperative = StoreSchool::findBySlug($input['school_slug']);
        if ($input['school_slug'] !== '' && $cooperative === null) {
            $v->addError('school_slug', t('store.school_not_found'));
        }
        $grade = $input['grade_level_code'];
        if ($cooperative !== null && $grade !== '' && !in_array($grade, StoreSchool::grades($cooperative), true)) {
            $v->addError('grade_level_code', t('error.invalid'));
        }
        // Tahun persekolahan: tahun ini atau tahun depan (pre-order)
        $year = (int) date('Y');
        $schoolYear = $input['school_year'];
        if ($schoolYear !== '' && !in_array($schoolYear, [(string) $year, (string) ($year + 1)], true)) {
            $v->addError('school_year', t('error.invalid'));
        }
        ValidationException::throwIfFails($v);

        $childId = (int) ($input['id'] ?? 0);
        list($classId, $className) = Children::matchClass($cooperative, $grade, $input['class_name']);
        $values = [(int) $cooperative['id'], (int) $cooperative['vendor_id'], $input['full_name'], $grade, $classId, $className, (int) $schoolYear];

        Database::beginTransaction();
        if ($childId > 0) {
            if (Children::find($this->customerId, $childId, null, true) === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            Database::execute(
                "UPDATE children SET cooperative_id = ?, vendor_id = ?, full_name = ?, grade_level_code = ?, school_class_id = ?, class_name = ?, school_year = ?
                  WHERE customer_id = ? AND id = ? AND is_active = 1",
                array_merge($values, [$this->customerId, $childId])
            );
            // Bertukar sekolah → baris troli sekolah lama dibuang
            Children::removeFromCarts($this->customerId, $childId, (int) $cooperative['id']);
        } else {
            // Kunci baris ibu bapa supaya had bilangan anak tidak dilangkau oleh permintaan serentak
            Database::fetchValue("SELECT id FROM customers WHERE id = ? FOR UPDATE", [$this->customerId]);
            $count = (int) Database::fetchValue("SELECT COUNT(*) FROM children WHERE customer_id = ? AND is_active = 1", [$this->customerId]);
            if ($count >= Children::MAX_PER_CUSTOMER) {
                throw new ValidationException(t('store.children.limit', ['n' => Children::MAX_PER_CUSTOMER]));
            }
            $childId = Database::insert(
                "INSERT INTO children (cooperative_id, vendor_id, full_name, grade_level_code, school_class_id, class_name, school_year, customer_id, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                array_merge($values, [$this->customerId])
            );
        }
        Database::commit();
        return Children::publicShape(Children::find($this->customerId, $childId));
    }
}
