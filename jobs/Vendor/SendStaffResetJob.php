<?php
// Pentadbir menghantar pautan tetapkan kata laluan (1 jam) kepada kakitangan vendor sesi.
class SendStaffResetJob
{
    const TTL_SECONDS = 3600;

    private $vendorId;
    private $actorId;
    private $userId;

    public function __construct($vendorId, $actorId, $userId)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->userId = (int) $userId;
    }

    public function run()
    {
        $user = Database::fetchOne(
            "SELECT id, full_name, email, language FROM vendor_users WHERE id = ? AND vendor_id = ? AND is_active = 1",
            [$this->userId, $this->vendorId]
        );
        if ($user === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $token = AuthToken::issue('vendor', $user['id'], 'reset_password', self::TTL_SECONDS, $this->vendorId);
        (new QueueEmailJob($this->vendorId, $user['email'], 'reset_password', $user['language'], [
            'name' => $user['full_name'],
            'link' => View::absoluteUrl('vendor/reset-password.php?token=' . $token),
        ]))->run();
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'staff_send_reset', 'vendor_user', $user['id'], $user['email']))->run();
        return ['email' => $user['email']];
    }
}
