<?php
// Tukar nama kelas { id, class_name } — kelas dicari dalam koperasi vendor sesi; nama pendua dalam darjah sama ditolak.
class RenameSchoolClassJob
{
    private $vendorId;
    private $cooperativeId;
    private $input;

    public function __construct($vendorId, $cooperativeId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->input = $input;
    }

    public function run()
    {
        $class = ListSchoolClassesJob::find($this->vendorId, $this->cooperativeId, $this->input['id'] ?? 0);
        if ($class === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('class_name')->maxLength('class_name', 50);
        $name = trim((string) ($this->input['class_name'] ?? ''));
        $clash = Database::fetchValue(
            "SELECT 1 FROM school_classes WHERE vendor_id = ? AND cooperative_id = ? AND grade_level_code = ? AND class_name = ? AND id <> ?",
            [$this->vendorId, $this->cooperativeId, $class['grade_level_code'], $name, $class['id']]
        );
        if ($name !== '' && $clash) {
            $v->addError('class_name', t('class.duplicate', ['names' => $name]));
        }
        ValidationException::throwIfFails($v);

        Database::execute(
            "UPDATE school_classes SET class_name = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [$name, $this->vendorId, $this->cooperativeId, $class['id']]
        );
        // Salinan nama kelas pada anak (paparan store & label beg) ikut nama baharu
        Database::execute(
            "UPDATE children SET class_name = ? WHERE vendor_id = ? AND cooperative_id = ? AND school_class_id = ?",
            [$name, $this->vendorId, $this->cooperativeId, $class['id']]
        );
        return ListSchoolClassesJob::find($this->vendorId, $this->cooperativeId, $class['id']);
    }
}
