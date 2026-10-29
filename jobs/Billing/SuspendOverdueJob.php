<?php
// Tunggakan invois (cron/suspend-overdue.php) — PELAN 14: invois `issued` melepasi due_at → vendor past_due;
// melepasi due_at + grace_days (tetapan platform) → suspended: store "ditutup sementara", hanya vendor_admin boleh log masuk
// untuk membayar. Data kekal. Pulih automatik selepas dibayar (VendorBillingStatus::restore).
class SuspendOverdueJob
{
    public function run()
    {
        $grace = max(0, (int) PlatformSettings::get('grace_days', '7'));
        $rows = Database::fetchAll(
            "SELECT v.id, v.status, MIN(i.due_at) AS oldest_due
               FROM vendors v JOIN invoices i ON i.vendor_id = v.id AND i.status = 'issued' AND i.due_at < NOW()
              WHERE v.status IN ('trial', 'active', 'past_due')
              GROUP BY v.id, v.status"
        );
        $result = ['past_due' => 0, 'suspended' => 0];
        foreach ($rows as $row) {
            $target = strtotime($row['oldest_due'] . ' +' . $grace . ' day') < time() ? 'suspended' : 'past_due';
            if ($target !== $row['status'] && $this->apply((int) $row['id'], $target)) {
                $result[$target]++;
            }
        }
        return $result;
    }

    private function apply($vendorId, $target)
    {
        Database::beginTransaction();
        try {
            $vendor = Subscriptions::lockVendor($vendorId);
            $overdue = VendorBillingStatus::oldestOverdue($vendorId);
            if ($overdue === null || !in_array($vendor['status'], ['trial', 'active', 'past_due'], true) || $vendor['status'] === $target) {
                Database::commit();
                return false;
            }
            $subscription = Subscriptions::current($vendorId, true);
            Database::execute("UPDATE vendors SET status = ? WHERE id = ?", [$target, $vendorId]);
            if ($subscription !== null) {
                Database::execute("UPDATE subscriptions SET status = ? WHERE id = ?", [$target, (int) $subscription['id']]);
            }
            $action = $target === 'suspended' ? 'vendor.auto_suspend' : 'vendor.auto_past_due';
            (new WriteAuditLogJob('system', null, $vendorId, $action, 'invoice', (int) $overdue['id'], $overdue['invoice_no']))->run();
            if ($target === 'suspended') {
                Database::insert("INSERT INTO vendor_notes (vendor_id, body, created_by) VALUES (?, ?, NULL)",
                    [$vendorId, Invoices::text('billing.auto_suspend_note', ['no' => $overdue['invoice_no']])]);
            }
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return true;
    }
}
