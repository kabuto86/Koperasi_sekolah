<?php
// Naik darjah { id, class_name? } — hanya bila Children::nextGrade() mencadangkan (Okt–Dis atau tahun sudah lepas).
// Kelas dipadankan semula dengan school_classes darjah baharu; nama kelas lama dikekalkan jika tidak diberi.
class PromoteChildJob
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
        $childId = (int) ($this->input['id'] ?? 0);
        $className = trim((string) ($this->input['class_name'] ?? ''));
        if (mb_strlen($className, 'UTF-8') > 50) {
            throw new ValidationException(t('error.check_form'), ['class_name' => t('error.max_length', ['max' => 50])]);
        }

        Database::beginTransaction();
        $child = Children::find($this->customerId, $childId, null, true);
        if ($child === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $next = Children::nextGrade($child);
        if ($next === null) {
            throw new ValidationException(t('store.children.promote_unavailable'));
        }
        $cooperative = ['id' => $child['cooperative_id'], 'vendor_id' => $child['vendor_id']];
        list($classId, $className) = Children::matchClass($cooperative, $next['grade_level_code'], $className !== '' ? $className : $child['class_name']);
        Database::execute(
            "UPDATE children SET grade_level_code = ?, school_year = ?, school_class_id = ?, class_name = ? WHERE customer_id = ? AND id = ?",
            [$next['grade_level_code'], $next['school_year'], $classId, $className, $this->customerId, $childId]
        );
        Database::commit();
        return Children::publicShape(Children::find($this->customerId, $childId));
    }
}
