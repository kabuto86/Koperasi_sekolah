<?php
// Set semula 2FA kakitangan owner (telefon hilang). Mereka menyediakan 2FA semula pada log masuk seterusnya.
class ResetOwnerStaff2faJob
{
    private $actorId;
    private $targetId;

    public function __construct($actorId, $targetId)
    {
        $this->actorId = (int) $actorId;
        $this->targetId = (int) $targetId;
    }

    public function run()
    {
        $person = ListOwnerStaffJob::find($this->targetId);
        if ($person === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute("UPDATE platform_users SET totp_secret = NULL, totp_last_step = NULL WHERE id = ?", [$this->targetId]);
        Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = 'owner' AND user_id = ?", [$this->targetId]);
        (new WriteAuditLogJob('owner', $this->actorId, null, 'staff.reset_2fa', 'platform_user', $this->targetId, $person['email']))->run();
        return ListOwnerStaffJob::find($this->targetId);
    }
}
