<?php
// Batal pertukaran pelan yang dijadualkan (vendor/subscription/cancel-change).
class CancelPlanChangeJob
{
    private $vendorId;
    private $actorId;

    public function __construct($vendorId, $actorId)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
    }

    public function run()
    {
        $subscription = Subscriptions::current($this->vendorId);
        if ($subscription !== null && $subscription['pending_plan_id'] !== null) {
            Database::execute(
                "UPDATE subscriptions SET pending_plan_id = NULL, pending_billing_cycle = NULL WHERE id = ? AND vendor_id = ?",
                [(int) $subscription['id'], $this->vendorId]
            );
            (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'subscription_change', 'subscription', (int) $subscription['id'],
                '↺'))->run();
        }
        return (new GetVendorSubscriptionJob($this->vendorId))->run();
    }
}
