<?php
// Tukar pelan / kitaran (PELAN 18.10 vendor/subscription/change): dijadualkan pada tarikh pembaharuan (pending_plan_id) —
// tiada prorata. Turun taraf ditolak jika penggunaan semasa melebihi had pelan baharu (PlanLimit). Pelan & kitaran sama = batal jadual.
// Tiada langganan hidup → SubscribeJob (mula serta-merta + invois).
class ChangePlanJob
{
    private $vendorId;
    private $actorId;
    private $input;

    public function __construct($vendorId, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $v = new Validator($this->input);
        $v->required('plan_id')->integer('plan_id', 1)->required('billing_cycle')->inList('billing_cycle', BillingPeriod::CYCLES);
        ValidationException::throwIfFails($v);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ? AND is_active = 1", [(int) $this->input['plan_id']]);
        if ($plan === null) {
            throw new ValidationException(t('error.check_form'), ['plan_id' => t('error.invalid')]);
        }
        $cycle = $this->input['billing_cycle'];

        Database::beginTransaction();
        try {
            Subscriptions::lockVendor($this->vendorId);
            $subscription = Subscriptions::current($this->vendorId, true);
            if ($subscription === null) {
                Database::commit();
                (new SubscribeJob($this->vendorId, $this->actorId, (int) $plan['id'], $cycle))->run();
                return (new GetVendorSubscriptionJob($this->vendorId))->run();
            }
            $same = (int) $plan['id'] === (int) $subscription['plan_id'] && $cycle === $subscription['billing_cycle'];
            if (!$same && !Subscriptions::fits($plan, Subscriptions::usage($this->vendorId))) {
                throw new ValidationException(t('billing.downgrade_blocked', ['plan' => $plan['name']]));
            }
            Database::execute(
                "UPDATE subscriptions SET pending_plan_id = ?, pending_billing_cycle = ? WHERE id = ? AND vendor_id = ?",
                [$same ? null : (int) $plan['id'], $same ? null : $cycle, (int) $subscription['id'], $this->vendorId]
            );
            if (!$same) {
                (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'subscription_change', 'subscription', (int) $subscription['id'],
                    $plan['name'] . ' · ' . t('billing.cycle.' . $cycle)))->run();
            }
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return (new GetVendorSubscriptionJob($this->vendorId))->run();
    }
}
