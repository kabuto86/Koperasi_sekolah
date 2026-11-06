<?php
// Tukar kata laluan kakitangan vendor semasa (kata laluan semasa wajib, baharu ≥ 10 aksara).
// Pautan reset / jemputan lama kakitangan ini yang belum diguna dibatalkan.
class ChangeVendorPasswordJob
{
    const PASSWORD_MIN = 10;

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
        $current = (string) ($this->input['current_password'] ?? '');
        $new = (string) ($this->input['new_password'] ?? '');
        $hash = Database::fetchValue("SELECT password_hash FROM vendor_users WHERE id = ? AND vendor_id = ?", [$this->userId, $this->vendorId]);

        if ($hash === null || !Password::verify($current, $hash)) {
            throw new ValidationException(t('profile.current_password_wrong'), ['current_password' => t('profile.current_password_wrong')]);
        }
        if (mb_strlen($new) < self::PASSWORD_MIN) {
            throw new ValidationException(t('profile.password_too_short'), ['new_password' => t('profile.password_too_short')]);
        }
        Database::execute("UPDATE vendor_users SET password_hash = ? WHERE id = ? AND vendor_id = ?", [Password::hash($new), $this->userId, $this->vendorId]);
        Database::execute(
            "UPDATE vendor_user_tokens SET used_at = NOW() WHERE vendor_id = ? AND vendor_user_id = ? AND used_at IS NULL",
            [$this->vendorId, $this->userId]
        );
        (new WriteActivityLogJob($this->vendorId, null, $this->userId, 'profile_password', 'vendor_user', $this->userId))->run();
        return ['changed' => true];
    }
}
