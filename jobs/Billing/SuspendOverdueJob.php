<?php
// Tunggakan invois (cron/suspend-overdue.php) — PELAN 14: invois `issued` melepasi due_at → vendor past_due;
// melepasi due_at + grace_days (tetapan platform) → suspended: store "ditutup sementara", hanya vendor_admin boleh log masuk
// untuk membayar. Data kekal. Pulih automatik selepas dibayar (VendorBillingStatus::restore).
// W3: e-mel kepada vendor sekali setiap peralihan — invoice_overdue (past_due, dengan tarikh akan digantung) & vendor_suspended.
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
            $suspendAt = strtotime($row['oldest_due'] . ' +' . $grace . ' day');
            $target = $suspendAt < time() ? 'suspended' : 'past_due';
            if ($target !== $row['status'] && $this->apply((int) $row['id'], $target, $suspendAt)) {
                $result[$target]++;
            }
        }
        return $result;
    }

    private function apply($vendorId, $target, $suspendAt)
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
            $this->queueEmail($vendor, $overdue, $target, $suspendAt);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return true;
    }

    // E-mel ke alamat syarikat vendor (bahasa platform lalai — sama seperti invoice_issued)
    private function queueEmail(array $vendor, array $invoice, $target, $suspendAt)
    {
        $row = Database::fetchOne("SELECT company_name, email FROM vendors WHERE id = ?", [(int) $vendor['id']]);
        if ($row === null || (string) $row['email'] === '') {
            return;
        }
        $vars = [
            'invoice_no'  => $invoice['invoice_no'],
            'vendor_name' => $row['company_name'],
            'total'       => Money::format((int) $invoice['total']),
            'due_date'    => date('d/m/Y', strtotime($invoice['due_at'])),
            'pay_url'     => View::absoluteUrl('vendor/invoices.php') . '?status=issued',
        ];
        if ($target === 'past_due') {
            $vars['suspend_date'] = date('d/m/Y', $suspendAt);
        }
        $key = $target === 'suspended' ? 'vendor_suspended' : 'invoice_overdue';
        (new QueueEmailJob((int) $vendor['id'], $row['email'], $key, Config::get('default_language'), $vars))->run();
    }
}
