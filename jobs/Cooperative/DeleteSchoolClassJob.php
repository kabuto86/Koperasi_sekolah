<?php
// Padam kelas { id }. Anak yang memilih kelas ini kekal (school_class_id → NULL oleh FK, nama kelas salinan kekal).
class DeleteSchoolClassJob
{
    private $vendorId;
    private $cooperativeId;
    private $classId;

    public function __construct($vendorId, $cooperativeId, $classId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->classId = (int) $classId;
    }

    public function run()
    {
        $deleted = Database::execute(
            "DELETE FROM school_classes WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [$this->vendorId, $this->cooperativeId, $this->classId]
        );
        if ($deleted === 0) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return ['id' => $this->classId];
    }
}
