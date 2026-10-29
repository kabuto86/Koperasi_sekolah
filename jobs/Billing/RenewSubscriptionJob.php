<?php
// Pusingan tempoh langganan (cron/generate-invoices.php) — PELAN 14. Untuk langganan trial / active / past_due yang
// current_period_end sudah lepas, dalam SATU transaksi:
//   1. baris pembaharuan dikira (RenewalLines — pertukaran pelan dijadualkan digunakan, modul dihentikan tidak dicaj)
//   2. item berhenti ditamatkan (cooperative_modules → expired), item berterusan dihargakan semula jika pelan/kitaran bertukar
//   3. tempoh baharu (pelan & kitaran baharu, trial → active)
//   4. invois tempoh baharu `issued` (draf 7 hari lebih awal dikemas kini; draf yang dibatalkan owner = tiada invois)
// Langganan digantung tidak diperbaharui sehingga tunggakan dibayar. Tempoh yang terlepas (lebih satu kitaran) tidak dicaj.
class RenewSubscriptionJob
{
    private $subscriptionId;

    public function __construct($subscriptionId)
    {
        $this->subscriptionId = (int) $subscriptionId;
    }

    // Pulangkan id invois yang diterbitkan, 0 (diperbaharui tanpa invois baharu) atau null (belum masanya / tidak layak)
    public function run()
    {
        Database::beginTransaction();
        try {
            $result = $this->renew();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return $result;
    }

    private function renew()
    {
        $peek = Subscriptions::find($this->subscriptionId);
        if ($peek === null) {
            return null;
        }
        Subscriptions::lockVendor($peek['vendor_id']);
        $sub = Subscriptions::find($this->subscriptionId, true);
        $today = BillingPeriod::today();
        if (!in_array($sub['status'], Subscriptions::RENEWING_STATUSES, true) || $sub['current_period_end'] >= $today) {
            return null;
        }
        $vendorId = (int) $sub['vendor_id'];
        $renewal = RenewalLines::build($sub, true);
        $cycle = $renewal['billing_cycle'];
        $oldEnd = $sub['current_period_end'];
        $draftStart = BillingPeriod::addDays($oldEnd, 1);
        $newStart = BillingPeriod::endFor($draftStart, $cycle) < $today ? $today : $draftStart;
        $newEnd = BillingPeriod::endFor($newStart, $cycle);

        self::syncItems($vendorId, (int) $sub['id'], $renewal, $oldEnd, $newEnd);
        $status = $sub['status'] === 'trial' ? 'active' : $sub['status'];
        Database::execute(
            "UPDATE subscriptions SET plan_id = ?, billing_cycle = ?, pending_plan_id = NULL, pending_billing_cycle = NULL,
                    current_period_start = ?, current_period_end = ?, status = ? WHERE id = ?",
            [(int) $renewal['plan']['id'], $cycle, $newStart, $newEnd, $status, (int) $sub['id']]
        );
        if ($sub['status'] === 'trial') {
            Database::execute("UPDATE vendors SET status = 'active' WHERE id = ? AND status = 'trial'", [$vendorId]);
        }

        $invoiceId = $this->invoice($vendorId, $renewal, [$draftStart, $newStart], $newStart, $newEnd);
        (new WriteAuditLogJob('system', null, $vendorId, 'subscription.renew', 'subscription', (int) $sub['id'],
            $renewal['plan']['name'] . ' · ' . $newStart . ' – ' . $newEnd))->run();
        return $invoiceId;
    }

    private function invoice($vendorId, array $renewal, array $starts, $newStart, $newEnd)
    {
        $existing = GenerateInvoiceJob::renewalInvoice($vendorId, $starts);
        if ($existing === null) {
            return Invoices::create($vendorId, $newStart, $newEnd, RenewalLines::invoiceLines($renewal), 'issued');
        }
        if ($existing['status'] !== 'draft') {
            return 0;
        }
        Invoices::replaceLines($existing['id'], $vendorId, RenewalLines::invoiceLines($renewal));
        Database::execute("UPDATE invoices SET period_start = ?, period_end = ? WHERE id = ? AND vendor_id = ?",
            [$newStart, $newEnd, (int) $existing['id'], $vendorId]);
        Invoices::issue($existing['id']);
        return (int) $existing['id'];
    }

    // Selaraskan subscription_items & cooperative_modules dengan baris pembaharuan (juga dipakai oleh SubscribeJob)
    public static function syncItems($vendorId, $subscriptionId, array $renewal, $endedOn, $newEnd)
    {
        foreach ($renewal['lines'] as $line) {
            if ($line['item_type'] === 'plan') {
                continue;
            }
            if ($line['item_id'] === null) {
                // Koperasi tambahan tanpa item (data lama) → item baharu supaya pembaharuan seterusnya ada snapshot
                if (!$line['cancelling'] && $line['item_type'] === 'cooperative') {
                    Database::insert(
                        "INSERT INTO subscription_items (vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at)
                         VALUES (?, ?, 'cooperative', ?, NULL, ?, ?)",
                        [$vendorId, $subscriptionId, $line['cooperative_id'], $line['price'], BillingPeriod::addDays($endedOn, 1)]
                    );
                }
                continue;
            }
            if ($line['cancelling']) {
                Database::execute("UPDATE subscription_items SET ended_at = ? WHERE id = ? AND vendor_id = ?", [$endedOn, $line['item_id'], $vendorId]);
                if ($line['item_type'] === 'module') {
                    Database::execute(
                        "UPDATE cooperative_modules cm JOIN subscription_items si ON si.id = ? AND si.ref_id = cm.module_id
                            SET cm.status = 'expired', cm.ends_at = ?, cm.cancel_at_period_end = 0
                          WHERE cm.vendor_id = ? AND cm.cooperative_id = si.cooperative_id",
                        [$line['item_id'], $endedOn, $vendorId]
                    );
                }
                continue;
            }
            Database::execute("UPDATE subscription_items SET unit_price = ?, subscription_id = ? WHERE id = ? AND vendor_id = ?",
                [$line['price'], $subscriptionId, $line['item_id'], $vendorId]);
        }
        // Modul berterusan dilanjutkan; modul ditanda berhenti (tanpa item) tamat
        Database::execute(
            "UPDATE cooperative_modules SET status = 'expired', ends_at = ?, cancel_at_period_end = 0
              WHERE vendor_id = ? AND status = 'active' AND cancel_at_period_end = 1",
            [$endedOn, $vendorId]
        );
        Database::execute("UPDATE cooperative_modules SET ends_at = ? WHERE vendor_id = ? AND status = 'active'", [$newEnd, $vendorId]);
    }
}
