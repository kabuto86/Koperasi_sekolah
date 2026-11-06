<?php
// Matikan 2FA kakitangan vendor sendiri — perlu kata laluan semasa DAN kod 2FA (TOTP atau kod pemulihan).
// 2FA owner wajib dan tidak boleh dimatikan (PELAN 11) — kelas ini untuk 'vendor_user' sahaja.
class DisableTotpJob
{
    private $vendorId;
    private $userId;
    private $input;

    public function __construct($vendorId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $hash = Database::fetchValue(
            "SELECT password_hash FROM vendor_users WHERE id = ? AND vendor_id = ? AND totp_secret IS NOT NULL",
            [$this->userId, $this->vendorId]
        );
        if ($hash === null) {
            throw new ValidationException(t('twofa.not_enabled'));
        }
        if (!Password::verify((string) ($this->input['password'] ?? ''), $hash)) {
            throw new ValidationException(t('profile.current_password_wrong'), ['password' => t('profile.current_password_wrong')]);
        }
        (new VerifyTotpJob('vendor_user', $this->userId, (string) ($this->input['code'] ?? '')))->run();

        Database::execute("UPDATE vendor_users SET totp_secret = NULL, totp_last_step = NULL WHERE id = ? AND vendor_id = ?", [$this->userId, $this->vendorId]);
        Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = 'vendor_user' AND user_id = ?", [$this->userId]);
        (new WriteActivityLogJob($this->vendorId, null, $this->userId, 'two_factor_disable', 'vendor_user', $this->userId))->run();
        return ['enabled' => false];
    }
}
