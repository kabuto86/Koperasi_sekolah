<?php
// Pelan aktif untuk modal "Tukar pelan" (PELAN 18.10 vendor/subscription/plans).
class ListVendorPlansJob
{
    private $vendorId;

    public function __construct($vendorId)
    {
        $this->vendorId = (int) $vendorId;
    }

    public function run()
    {
        $subscription = Subscriptions::current($this->vendorId);
        $plans = Database::fetchAll("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order, id");
        return [
            'current_plan_id' => $subscription ? (int) $subscription['plan_id'] : null,
            'billing_cycle'   => $subscription ? $subscription['billing_cycle'] : 'monthly',
            'plans'           => array_map([Subscriptions::class, 'planShape'], $plans),
        ];
    }
}
