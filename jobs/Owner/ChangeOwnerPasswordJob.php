<?php
// Tukar kata laluan pengguna owner semasa (kata laluan semasa wajib, baharu ≥ 10 aksara).
class ChangeOwnerPasswordJob
{
    const PASSWORD_MIN = 10;

    private $userId;
    private $input;

    public function __construct($userId, array $input)
    {
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $current = (string) ($this->input['current_password'] ?? '');
        $new = (string) ($this->input['new_password'] ?? '');
        $hash = Database::fetchValue("SELECT password_hash FROM platform_users WHERE id = ?", [$this->userId]);

        if (!Password::verify($current, $hash)) {
            throw new ValidationException(t('profile.current_password_wrong'), ['current_password' => t('profile.current_password_wrong')]);
        }
        if (mb_strlen($new) < self::PASSWORD_MIN) {
            throw new ValidationException(t('profile.password_too_short'), ['new_password' => t('profile.password_too_short')]);
        }
        Database::execute("UPDATE platform_users SET password_hash = ? WHERE id = ?", [Password::hash($new), $this->userId]);
        (new WriteAuditLogJob('owner', $this->userId, null, 'profile.change_password', 'platform_user', $this->userId))->run();
        return ['changed' => true];
    }
}
