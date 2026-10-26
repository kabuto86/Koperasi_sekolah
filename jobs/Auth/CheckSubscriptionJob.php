<?php
// Status langganan vendor (disemak selepas log masuk dan setiap permintaan — PELAN 11).
// suspended / cancelled → billing_only: hanya halaman langganan & invois boleh dibuka.
class CheckSubscriptionJob
{
    const BILLING_ONLY = ['suspended', 'cancelled'];

    private $vendorId;

    public function __construct($vendorId)
    {
        $this->vendorId = (int) $vendorId;
    }

    public function run()
    {
        $status = Database::fetchValue("SELECT status FROM vendors WHERE id = ?", [$this->vendorId]);
        if ($status === null) {
            return ['status' => null, 'billing_only' => true];
        }
        return ['status' => $status, 'billing_only' => in_array($status, self::BILLING_ONLY, true)];
    }
}
