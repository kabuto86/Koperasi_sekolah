<?php
// Peringkat harga koperasi tambahan satu pelan (PELAN 18.7 PlanTiers).
class GetPlanTiersJob
{
    private $planId;

    public function __construct($planId)
    {
        $this->planId = (int) $planId;
    }

    public function run()
    {
        $plan = ListPlansJob::find($this->planId);
        if ($plan === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return ['plan' => $plan, 'tiers' => self::tiers($this->planId)];
    }

    public static function tiers($planId)
    {
        $rows = Database::fetchAll(
            "SELECT id, from_coop, to_coop, price_monthly, price_yearly FROM plan_coop_tiers WHERE plan_id = ? ORDER BY from_coop",
            [(int) $planId]
        );
        return array_map(function ($row) {
            return [
                'id'            => (int) $row['id'],
                'from_coop'     => (int) $row['from_coop'],
                'to_coop'       => $row['to_coop'] === null ? null : (int) $row['to_coop'],
                'price_monthly' => (int) $row['price_monthly'],
                'price_yearly'  => (int) $row['price_yearly'],
            ];
        }, $rows);
    }
}
