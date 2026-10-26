<?php
// Sahkan kod 2FA (6 digit TOTP atau kod pemulihan XXXX-XXXX) untuk owner atau kakitangan vendor.
// $setupSecret diberi → pengguna sedang MENYEDIAKAN 2FA: kod disemak dengan rahsia baharu, rahsia disimpan
// disulitkan dan kod pemulihan baharu dipulangkan (dipapar sekali).
// Cubaan gagal dikira seperti log masuk (5 gagal → dikunci 15 minit).
class VerifyTotpJob
{
    const TABLES = ['owner' => 'platform_users', 'vendor_user' => 'vendor_users'];

    private $userType;
    private $userId;
    private $code;
    private $setupSecret;

    public function __construct($userType, $userId, $code, $setupSecret = null)
    {
        $this->userType = $userType;
        $this->userId = (int) $userId;
        $this->code = trim((string) $code);
        $this->setupSecret = $setupSecret;
    }

    // [recovery_codes => array|null]
    public function run()
    {
        $table = self::TABLES[$this->userType];
        $lockType = $this->userType === 'owner' ? 'owner' : 'vendor';
        $identifier = 'totp:' . $this->userType . ':' . $this->userId;
        if (LoginLockout::isLocked($lockType, $identifier)) {
            LoginLockout::record($lockType, $identifier, false);
            throw new ValidationException(t('auth.locked'), [], 429);
        }

        $user = Database::fetchOne("SELECT totp_secret, totp_last_step FROM $table WHERE id = ? AND is_active = 1", [$this->userId]);
        if ($user === null) {
            throw new ValidationException(t('auth.session_expired'), [], 401, ['auth_required' => true]);
        }

        $recoveryCodes = null;
        $valid = false;
        if ($this->setupSecret !== null) {
            $step = Totp::verify($this->setupSecret, $this->code);
            if ($step !== null) {
                Database::execute(
                    "UPDATE $table SET totp_secret = ?, totp_last_step = ? WHERE id = ?",
                    [Crypto::encrypt($this->setupSecret), $step, $this->userId]
                );
                $recoveryCodes = RecoveryCodes::generate($this->userType, $this->userId);
                $valid = true;
            }
        } elseif ($user['totp_secret'] !== null) {
            $valid = $this->verifyExisting($table, $user);
        }

        LoginLockout::record($lockType, $identifier, $valid);
        if (!$valid) {
            throw new ValidationException(t('auth.otp_wrong'), ['code' => t('auth.otp_wrong')]);
        }
        LoginLockout::markUser($table, $this->userId, true);
        return ['recovery_codes' => $recoveryCodes];
    }

    private function verifyExisting($table, $user)
    {
        $secret = Crypto::decrypt($user['totp_secret']);
        if ($secret !== null) {
            $step = Totp::verify($secret, $this->code, $user['totp_last_step']);
            if ($step !== null) {
                Database::execute("UPDATE $table SET totp_last_step = ? WHERE id = ?", [$step, $this->userId]);
                return true;
            }
        }
        if (RecoveryCodes::looksLikeRecoveryCode($this->code)) {
            return RecoveryCodes::consume($this->userType, $this->userId, $this->code);
        }
        return false;
    }
}
