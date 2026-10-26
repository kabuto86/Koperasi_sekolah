<?php
// Padam pelan — ditolak jika ada langganan (termasuk tukar pelan yang menunggu) menggunakannya.
class DeletePlanJob
{
    private $actorId;
    private $planId;

    public function __construct($actorId, $planId)
    {
        $this->actorId = (int) $actorId;
        $this->planId = (int) $planId;
    }

    public function run()
    {
        $plan = ListPlansJob::find($this->planId);
        if ($plan === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $inUse = Database::fetchValue(
            "SELECT 1 FROM subscriptions WHERE plan_id = ? OR pending_plan_id = ? LIMIT 1",
            [$this->planId, $this->planId]
        );
        if ($inUse) {
            throw new ValidationException(t('plan.delete_in_use'));
        }
        Database::execute("DELETE FROM plans WHERE id = ?", [$this->planId]);
        (new WriteAuditLogJob('owner', $this->actorId, null, 'plan.delete', 'plan', $this->planId, $plan['code']))->run();
        return ['id' => $this->planId];
    }
}
