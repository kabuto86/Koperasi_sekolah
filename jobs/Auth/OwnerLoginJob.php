<?php
// Log masuk owner langkah 1: e-mel + kata laluan. Langkah 2 (TOTP) wajib — lihat VerifyTotpJob.
// Pulangkan [user_id, setup_secret] — setup_secret ada jika 2FA belum disediakan (log masuk pertama / selepas reset 2FA).
class OwnerLoginJob
{
    private $email;
    private $password;

    public function __construct(array $input)
    {
        $this->email = strtolower(trim((string) ($input['email'] ?? '')));
        $this->password = (string) ($input['password'] ?? '');
    }

    public function run()
    {
        if ($this->email === '' || $this->password === '') {
            throw new ValidationException(t('auth.invalid_credentials'));
        }
        if (LoginLockout::isLocked('owner', $this->email)) {
            LoginLockout::record('owner', $this->email, false);
            throw new ValidationException(t('auth.locked'), [], 429);
        }

        $user = Database::fetchOne(
            "SELECT id, password_hash, totp_secret FROM platform_users WHERE email = ? AND is_active = 1",
            [$this->email]
        );
        // Semak kata laluan walaupun akaun tiada (masa sama) — mesej sama (PELAN 11)
        $valid = Password::verify($this->password, $user['password_hash'] ?? null) && $user !== null;
        LoginLockout::record('owner', $this->email, $valid);
        if (!$valid) {
            if ($user !== null) {
                LoginLockout::markUser('platform_users', $user['id'], false);
            }
            throw new ValidationException(t('auth.invalid_credentials'));
        }

        if (Password::needsRehash($user['password_hash'])) {
            Database::execute("UPDATE platform_users SET password_hash = ? WHERE id = ?", [Password::hash($this->password), $user['id']]);
        }

        return [
            'user_id'      => (int) $user['id'],
            'setup_secret' => $user['totp_secret'] === null ? Totp::generateSecret() : null,
        ];
    }
}
