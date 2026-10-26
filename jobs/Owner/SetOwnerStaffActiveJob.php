<?php
// Aktif / nyahaktif kakitangan owner. Akaun sendiri tidak boleh dinyahaktifkan.
class SetOwnerStaffActiveJob
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
        $id = (int) ($this->input['id'] ?? 0);
        $isActive = ($this->input['is_active'] ?? false) === true;
        $person = ListOwnerStaffJob::find($id);
        if ($person === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($id === $this->actorId) {
            throw new ValidationException(t('staff.cannot_disable_self'));
        }
        Database::execute("UPDATE platform_users SET is_active = ? WHERE id = ?", [$isActive ? 1 : 0, $id]);
        (new WriteAuditLogJob('owner', $this->actorId, null, $isActive ? 'staff.activate' : 'staff.deactivate', 'platform_user', $id, $person['email']))->run();
        return ListOwnerStaffJob::find($id);
    }
}
