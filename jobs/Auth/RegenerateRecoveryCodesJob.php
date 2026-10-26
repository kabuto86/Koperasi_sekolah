<?php
// Jana semula kod pemulihan — perlu kod TOTP semasa (bukan kod pemulihan).
class RegenerateRecoveryCodesJob
{
    const TABLES = ['owner' => 'platform_users', 'vendor_user' => 'vendor_users'];

    private $userType;
    private $userId;
    private $code;

    public function __construct($userType, $userId, $code)
    {
        $this->userType = $userType;
        $this->userId = (int) $userId;
        $this->code = (string) $code;
    }

    public function run()
    {
        $table = self::TABLES[$this->userType];
        $user = Database::fetchOne("SELECT totp_secret, totp_last_step FROM $table WHERE id = ?", [$this->userId]);
        $secret = $user && $user['totp_secret'] !== null ? Crypto::decrypt($user['totp_secret']) : null;
        $step = $secret === null ? null : Totp::verify($secret, $this->code, $user['totp_last_step']);
        if ($step === null) {
            throw new ValidationException(t('auth.otp_wrong'), ['code' => t('auth.otp_wrong')]);
        }
        Database::execute("UPDATE $table SET totp_last_step = ? WHERE id = ?", [$step, $this->userId]);
        return ['recovery_codes' => RecoveryCodes::generate($this->userType, $this->userId)];
    }
}
