<?php
// Gantikan semua peringkat satu pelan. Mesti berturutan: mula 2, from = to sebelumnya + 1,
// peringkat terakhir to = null (dan seterusnya). Invois lama tidak berubah (snapshot).
class SavePlanTiersJob
{
    const MAX_TIERS = 20;

    private $actorId;
    private $planId;
    private $tiers;

    public function __construct($actorId, $planId, $tiers)
    {
        $this->actorId = (int) $actorId;
        $this->planId = (int) $planId;
        $this->tiers = is_array($tiers) ? array_values($tiers) : [];
    }

    public function run()
    {
        $plan = ListPlansJob::find($this->planId);
        if ($plan === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $clean = $this->validated();

        Database::beginTransaction();
        Database::execute("DELETE FROM plan_coop_tiers WHERE plan_id = ?", [$this->planId]);
        foreach ($clean as $tier) {
            Database::execute(
                "INSERT INTO plan_coop_tiers (plan_id, from_coop, to_coop, price_monthly, price_yearly) VALUES (?, ?, ?, ?, ?)",
                [$this->planId, $tier['from_coop'], $tier['to_coop'], $tier['price_monthly'], $tier['price_yearly']]
            );
        }
        Database::commit();

        (new WriteAuditLogJob('owner', $this->actorId, null, 'plan.tiers_update', 'plan', $this->planId,
            $plan['code'] . ': ' . count($clean)))->run();
        return ['plan_id' => $this->planId, 'tiers' => GetPlanTiersJob::tiers($this->planId)];
    }

    private function validated()
    {
        $count = count($this->tiers);
        if ($count === 0 || $count > self::MAX_TIERS) {
            throw new ValidationException(t('coop_pricing.invalid_tiers'));
        }
        $clean = [];
        $expectedFrom = 2;
        foreach ($this->tiers as $index => $tier) {
            $from = $this->intOrNull($tier['from_coop'] ?? null);
            $to = $this->intOrNull($tier['to_coop'] ?? null);
            $monthly = $this->intOrNull($tier['price_monthly'] ?? null);
            $yearly = $this->intOrNull($tier['price_yearly'] ?? null);
            $isLast = ($index === $count - 1);
            $badRange = $from !== $expectedFrom || ($isLast ? $to !== null : ($to === null || $to < $from));
            if ($badRange || $monthly === null || $yearly === null || $monthly < 0 || $yearly < 0) {
                throw new ValidationException(t('coop_pricing.invalid_tiers'));
            }
            $clean[] = ['from_coop' => $from, 'to_coop' => $to, 'price_monthly' => $monthly, 'price_yearly' => $yearly];
            $expectedFrom = $isLast ? null : $to + 1;
        }
        return $clean;
    }

    private function intOrNull($value)
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', trim($value))) {
            return (int) $value;
        }
        return null;
    }
}
