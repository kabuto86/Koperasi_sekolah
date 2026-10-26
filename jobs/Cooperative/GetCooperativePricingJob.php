<?php
// Anggaran harga koperasi seterusnya (amaran borang koperasi baharu — PELAN 14, 18.8).
// Koperasi ke-n dicaj ikut plan_coop_tiers (CalculateCoopPriceJob); had pelan max_cooperatives.
class GetCooperativePricingJob
{
    private $vendorId;

    public function __construct($vendorId)
    {
        $this->vendorId = (int) $vendorId;
    }

    public function run()
    {
        $plan = PlanLimit::plan($this->vendorId);
        if ($plan === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $number = PlanLimit::cooperativeCount($this->vendorId) + 1;
        return [
            'cooperative_number' => $number,
            'plan_name'          => $plan['plan_name'],
            'billing_cycle'      => $plan['billing_cycle'],
            'price_monthly'      => self::priceFor($plan['plan_id'], $number, 'monthly'),
            'price_yearly'       => self::priceFor($plan['plan_id'], $number, 'yearly'),
            'current_period_end' => $plan['current_period_end'],
            'max_cooperatives'   => $plan['max_cooperatives'],
            'limit_reached'      => $plan['max_cooperatives'] !== null && $number > $plan['max_cooperatives'],
        ];
    }

    // Harga koperasi ke-$number sahaja (koperasi pertama termasuk dalam pelan = 0)
    public static function priceFor($planId, $number, $cycle)
    {
        $result = (new CalculateCoopPriceJob($planId, $number, $cycle))->run();
        $last = end($result['lines']);
        return (int) $last['price'];
    }
}
