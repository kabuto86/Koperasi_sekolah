<?php
// Langganan baharu (bulanan / tahunan) untuk vendor tanpa langganan hidup (tiada / dibatalkan) — PELAN 14.
// Bermula hari ini, aktif serta-merta, invois tempoh penuh `issued` (bayar dalam 7 hari, kemudian tempoh tangguh).
// Item terbuka lama (koperasi tambahan, modul, template) dipindah ke langganan baharu dan dihargakan semula ikut kitaran.
class SubscribeJob
{
    private $vendorId;
    private $actorId;
    private $planId;
    private $cycle;

    public function __construct($vendorId, $actorId, $planId, $cycle)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = $actorId === null ? null : (int) $actorId;
        $this->planId = (int) $planId;
        $this->cycle = in_array($cycle, BillingPeriod::CYCLES, true) ? $cycle : 'monthly';
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $result = $this->subscribe();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return $result;
    }

    private function subscribe()
    {
        Subscriptions::lockVendor($this->vendorId);
        if (Subscriptions::current($this->vendorId, true) !== null) {
            throw new ValidationException(t('billing.already_subscribed'));
        }
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ? AND is_active = 1", [$this->planId]);
        if ($plan === null) {
            throw new ValidationException(t('error.check_form'), ['plan_id' => t('error.invalid')]);
        }
        if (!Subscriptions::fits($plan, Subscriptions::usage($this->vendorId))) {
            throw new ValidationException(t('billing.downgrade_blocked', ['plan' => $plan['name']]));
        }
        $start = BillingPeriod::today();
        $end = BillingPeriod::endFor($start, $this->cycle);
        $id = Database::insert(
            "INSERT INTO subscriptions (vendor_id, plan_id, billing_cycle, status, started_at, current_period_start, current_period_end)
             VALUES (?, ?, ?, 'active', ?, ?, ?)",
            [$this->vendorId, $this->planId, $this->cycle, $start, $start, $end]
        );
        Database::execute("UPDATE subscription_items SET subscription_id = ? WHERE vendor_id = ? AND ended_at IS NULL", [$id, $this->vendorId]);
        Database::execute("UPDATE vendors SET status = 'active' WHERE id = ?", [$this->vendorId]);

        // Harga semula ikut pelan & kitaran baharu (seperti pertukaran pada pembaharuan)
        $subscription = Subscriptions::find($id, true);
        $subscription['plan_id'] = 0;
        $subscription['pending_plan_id'] = $this->planId;
        $subscription['pending_billing_cycle'] = $this->cycle;
        $renewal = RenewalLines::build($subscription, true);
        RenewSubscriptionJob::syncItems($this->vendorId, $id, $renewal, BillingPeriod::addDays($start, -1), $end);
        $invoiceId = Invoices::create($this->vendorId, $start, $end, RenewalLines::invoiceLines($renewal), 'issued');

        (new WriteAuditLogJob($this->actorId === null ? 'system' : 'vendor_user', $this->actorId, $this->vendorId, 'subscription.start',
            'subscription', $id, $plan['name'] . ' · ' . $this->cycle))->run();
        if ($this->actorId !== null) {
            (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'subscription_change', 'subscription', $id,
                $plan['name'] . ' · ' . t('billing.cycle.' . $this->cycle)))->run();
        }
        return ['subscription_id' => $id, 'invoice_id' => $invoiceId];
    }
}
