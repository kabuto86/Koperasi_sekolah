<?php
// Log masuk kakitangan vendor: e-mel + kata laluan. 2FA hanya jika totp_secret disediakan.
// Vendor digantung / dibatalkan: hanya vendor_admin boleh masuk (halaman bil sahaja — CheckSubscriptionJob).
class VendorLoginJob
{
    private $email;
    private $password;

    public function __construct(array $input)
    {
        $this->email = strtolower(trim((string) ($input['email'] ?? '')));
        $this->password = (string) ($input['password'] ?? '');
    }

    // [user_id, vendor_id, requires_2fa, billing_only]
    public function run()
    {
        if ($this->email === '' || $this->password === '') {
            throw new ValidationException(t('auth.invalid_credentials'));
        }
        if (LoginLockout::isLocked('vendor', $this->email)) {
            LoginLockout::record('vendor', $this->email, false);
            throw new ValidationException(t('auth.locked'), [], 429);
        }

        $user = Database::fetchOne(
            "SELECT id, vendor_id, role, password_hash, totp_secret FROM vendor_users WHERE email = ? AND is_active = 1",
            [$this->email]
        );
        $valid = Password::verify($this->password, $user['password_hash'] ?? null) && $user !== null;
        LoginLockout::record('vendor', $this->email, $valid);
        if (!$valid) {
            if ($user !== null) {
                LoginLockout::markUser('vendor_users', $user['id'], false);
            }
            throw new ValidationException(t('auth.invalid_credentials'));
        }

        $subscription = (new CheckSubscriptionJob($user['vendor_id']))->run();
        if ($subscription['billing_only'] && $user['role'] !== 'vendor_admin') {
            throw new ValidationException(t('auth.vendor_suspended'), [], 403);
        }

        if (Password::needsRehash($user['password_hash'])) {
            Database::execute("UPDATE vendor_users SET password_hash = ? WHERE id = ?", [Password::hash($this->password), $user['id']]);
        }
        $requires2fa = $user['totp_secret'] !== null;
        if (!$requires2fa) {
            LoginLockout::markUser('vendor_users', $user['id'], true);
        }

        return [
            'user_id'      => (int) $user['id'],
            'vendor_id'    => (int) $user['vendor_id'],
            'requires_2fa' => $requires2fa,
            'billing_only' => $subscription['billing_only'],
        ];
    }
}
