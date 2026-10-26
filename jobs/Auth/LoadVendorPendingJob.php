<?php
// vendor_id kakitangan yang sedang menunggu 2FA (dibaca dari DB, bukan dari input).
class LoadVendorPendingJob
{
    private $userId;

    public function __construct($userId)
    {
        $this->userId = (int) $userId;
    }

    public function run()
    {
        return (int) Database::fetchValue("SELECT vendor_id FROM vendor_users WHERE id = ? AND is_active = 1", [$this->userId]);
    }
}
