<?php
// Langganan vendor sesi (PELAN 18.10 vendor/subscription/get): pelan, kitaran, penggunaan berbanding had, koperasi + template
// + modul, anggaran pembaharuan (RenewalLines — sama dengan invois pembaharuan sebenar), pertukaran dijadualkan, invois belum dibayar.
// Tiada langganan hidup → { plan: null, status: 'none' } (vendor boleh melanggan semula — SubscribeJob).
class GetVendorSubscriptionJob
{
    private $vendorId;

    public function __construct($vendorId)
    {
        $this->vendorId = (int) $vendorId;
    }

    public function run()
    {
        $subscription = Subscriptions::current($this->vendorId);
        $unpaid = Database::fetchOne(
            "SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS amount FROM invoices WHERE vendor_id = ? AND status = 'issued'",
            [$this->vendorId]
        );
        $base = ['unpaid_count' => (int) $unpaid['n'], 'unpaid_total' => (int) $unpaid['amount']];
        if ($subscription === null) {
            return $base + ['plan' => null, 'status' => 'none', 'usage' => Subscriptions::usage($this->vendorId)];
        }

        $renewal = RenewalLines::build($subscription, true);
        $rate = Subscriptions::sstRate();
        $tax = Subscriptions::tax($renewal['subtotal'], $rate);
        $planRow = Subscriptions::plan($subscription['plan_id']);
        return $base + [
            'plan'                 => Subscriptions::planShape($planRow),
            'billing_cycle'        => $subscription['billing_cycle'],
            'status'               => $subscription['status'],
            'started_at'           => $subscription['started_at'],
            'current_period_start' => $subscription['current_period_start'],
            'current_period_end'   => $subscription['current_period_end'],
            'days_left'            => max(0, BillingPeriod::days(BillingPeriod::today(), $subscription['current_period_end']) - 1),
            'usage'                => Subscriptions::usage($this->vendorId),
            'cooperatives'         => $this->cooperatives($subscription),
            'renewal'              => [
                'lines'    => RenewalLines::publicLines($renewal),
                'subtotal' => $renewal['subtotal'],
                'tax'      => $tax,
                'total'    => $renewal['subtotal'] + $tax,
                'sst_rate' => $rate,
            ],
            'pending_change'       => $this->pendingChange($subscription),
        ];
    }

    // Koperasi semasa (harga kitaran semasa, tanpa pertukaran dijadualkan)
    private function cooperatives(array $subscription)
    {
        $current = RenewalLines::build($subscription, false);
        $prices = [];
        $templates = [];
        $modules = [];
        foreach ($current['lines'] as $line) {
            if ($line['item_type'] === 'cooperative' && !$line['cancelling']) {
                $prices[$line['cooperative_id']] = $line['price'];
            } elseif ($line['item_type'] === 'template' && !$line['cancelling']) {
                $templates[$line['cooperative_id']] = $line['price'];
            }
        }
        $rows = Database::fetchAll(
            "SELECT m.code, cm.cooperative_id, cm.started_at, cm.cancel_at_period_end, m.price_monthly, m.price_yearly
               FROM cooperative_modules cm JOIN modules m ON m.id = cm.module_id
              WHERE cm.vendor_id = ? AND cm.status = 'active' AND m.is_active = 1 AND (cm.ends_at IS NULL OR cm.ends_at >= CURDATE())
              ORDER BY m.sort_order",
            [$this->vendorId]
        );
        foreach ($rows as $row) {
            $modules[(int) $row['cooperative_id']][] = [
                'code'                 => $row['code'],
                'price'                => Subscriptions::cyclePrice($row, $subscription['billing_cycle']),
                'started_at'           => $row['started_at'],
                'cancel_at_period_end' => (bool) $row['cancel_at_period_end'],
            ];
        }
        $coops = Database::fetchAll(
            "SELECT c.id, c.school_name, t.name AS template_name FROM cooperatives c JOIN templates t ON t.id = c.template_id
              WHERE c.vendor_id = ? AND c.status <> 'closed' ORDER BY c.id",
            [$this->vendorId]
        );
        $result = [];
        foreach ($coops as $index => $coop) {
            $id = (int) $coop['id'];
            $result[] = [
                'id'             => $id,
                'school_name'    => $coop['school_name'],
                'number'         => $index + 1,
                'included'       => $index === 0,
                'price'          => $prices[$id] ?? 0,
                'template_name'  => $coop['template_name'],
                'template_price' => $templates[$id] ?? 0,
                'modules'        => $modules[$id] ?? [],
            ];
        }
        return $result;
    }

    private function pendingChange(array $subscription)
    {
        if ($subscription['pending_plan_id'] === null) {
            return null;
        }
        $plan = Subscriptions::plan($subscription['pending_plan_id']);
        return [
            'plan_id'       => (int) $plan['id'],
            'plan_name'     => $plan['name'],
            'billing_cycle' => $subscription['pending_billing_cycle'] ?: $subscription['billing_cycle'],
            'effective_at'  => BillingPeriod::addDays($subscription['current_period_end'], 1),
        ];
    }
}
