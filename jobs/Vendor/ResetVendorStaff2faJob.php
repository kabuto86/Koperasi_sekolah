<?php
// Pentadbir vendor menyet semula 2FA kakitangan sendiri (telefon hilang). Kakitangan log masuk dengan kata laluan sahaja
// selepas ini dan boleh menyediakan 2FA semula di halaman profil. Kakitangan vendor lain → 404.
class ResetVendorStaff2faJob
{
    private $vendorId;
    private $actorId;
    private $targetId;

    public function __construct($vendorId, $actorId, $targetId)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->targetId = (int) $targetId;
    }

    public function run()
    {
        $user = ListVendorStaffJob::find($this->vendorId, $this->targetId, $this->actorId);
        if ($user === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute(
            "UPDATE vendor_users SET totp_secret = NULL, totp_last_step = NULL WHERE id = ? AND vendor_id = ?",
            [$this->targetId, $this->vendorId]
        );
        Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = 'vendor_user' AND user_id = ?", [$this->targetId]);
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'staff_reset_2fa', 'vendor_user', $this->targetId, $user['email']))->run();
        return ListVendorStaffJob::find($this->vendorId, $this->targetId, $this->actorId);
    }
}
