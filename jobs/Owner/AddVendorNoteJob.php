<?php
// Nota dalaman owner tentang vendor (tidak dilihat vendor).
class AddVendorNoteJob
{
    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $vendorId = (int) ($this->input['id'] ?? 0);
        if (!Database::fetchValue("SELECT 1 FROM vendors WHERE id = ?", [$vendorId])) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('body')->maxLength('body', 2000);
        ValidationException::throwIfFails($v);

        $id = Database::insert(
            "INSERT INTO vendor_notes (vendor_id, body, created_by) VALUES (?, ?, ?)",
            [$vendorId, trim($this->input['body']), $this->actorId]
        );
        (new WriteAuditLogJob('owner', $this->actorId, $vendorId, 'vendor.note', 'vendor', $vendorId, ''))->run();
        $row = Database::fetchOne(
            "SELECT n.id, n.body, n.created_at, u.full_name FROM vendor_notes n LEFT JOIN platform_users u ON u.id = n.created_by WHERE n.id = ?",
            [$id]
        );
        return ['id' => $id, 'vendor_id' => $vendorId, 'body' => $row['body'], 'created_by' => (string) $row['full_name'],
            'created_at' => IsoDate::format($row['created_at'])];
    }
}
