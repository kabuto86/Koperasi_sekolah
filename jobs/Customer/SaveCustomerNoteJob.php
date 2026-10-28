<?php
// Nota dalaman vendor tentang ibu bapa (PELAN 18.9 vendor/customers/save-note). Ditolak (404) jika ibu bapa tiada anak
// di koperasi ini. Satu nota setiap vendor setiap ibu bapa — vendor lain tidak pernah melihatnya. Nota kosong = padam.
class SaveCustomerNoteJob
{
    const MAX_LENGTH = 1000;

    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $customerId;
    private $note;

    public function __construct($vendorId, $cooperativeId, $userId, $customerId, $note)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->customerId = (int) $customerId;
        $this->note = trim((string) $note);
    }

    public function run()
    {
        $linked = Database::fetchValue(
            "SELECT 1 FROM children k JOIN customers c ON c.id = k.customer_id AND c.deleted_at IS NULL
              WHERE k.vendor_id = ? AND k.cooperative_id = ? AND k.customer_id = ? LIMIT 1",
            [$this->vendorId, $this->cooperativeId, $this->customerId]
        );
        if ($linked === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if (mb_strlen($this->note, 'UTF-8') > self::MAX_LENGTH) {
            throw new ValidationException(t('error.check_form'), ['note' => t('error.max_length', ['max' => self::MAX_LENGTH])]);
        }
        if ($this->note === '') {
            Database::execute("DELETE FROM vendor_customer_notes WHERE vendor_id = ? AND customer_id = ?", [$this->vendorId, $this->customerId]);
        } else {
            Database::execute(
                "INSERT INTO vendor_customer_notes (vendor_id, customer_id, note, updated_by) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE note = VALUES(note), updated_by = VALUES(updated_by)",
                [$this->vendorId, $this->customerId, $this->note, $this->userId]
            );
        }
        return ['customer_id' => $this->customerId, 'note' => $this->note];
    }
}
