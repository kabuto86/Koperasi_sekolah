<?php
// Butiran vendor untuk owner (PELAN 18.7 VendorDetail): profil, langganan, koperasi, invois, nota dalaman.
// Owner hanya membaca — tiada "log masuk sebagai vendor" (PELAN 5.1).
class GetVendorJob
{
    private $vendorId;

    public function __construct($vendorId)
    {
        $this->vendorId = (int) $vendorId;
    }

    public function run()
    {
        $vendor = Database::fetchOne("SELECT * FROM vendors WHERE id = ?", [$this->vendorId]);
        if ($vendor === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $subscription = $this->subscription();
        return [
            'id'              => (int) $vendor['id'],
            'code'            => $vendor['code'],
            'company_name'    => $vendor['company_name'],
            'registration_no' => $vendor['registration_no'],
            'contact_name'    => $vendor['contact_name'],
            'phone'           => $vendor['phone'],
            'email'           => $vendor['email'],
            'address'         => $vendor['address'],
            'status'          => $vendor['status'],
            'trial_ends_at'   => IsoDate::format($vendor['trial_ends_at']),
            'created_at'      => IsoDate::format($vendor['created_at']),
            'plan_id'         => $subscription ? $subscription['plan_id'] : null,
            'plan_name'       => $subscription ? $subscription['plan_name'] : '',
            'billing_cycle'   => $subscription ? $subscription['billing_cycle'] : null,
            'subscription'    => $subscription,
            'cooperatives'    => (new ListAllCooperativesJob(['vendor_id' => $this->vendorId, 'per_page' => 100]))->run()['items'],
            'invoices'        => $this->invoices(),
            'notes'           => $this->notes(),
        ];
    }

    // SubscriptionSummary (PELAN 18.7) — langganan terkini vendor
    private function subscription()
    {
        $row = Database::fetchOne(
            "SELECT s.*, v.code AS vendor_code, v.company_name AS vendor_name, p.name AS plan_name,
                    (SELECT COUNT(*) FROM cooperatives c WHERE c.vendor_id = s.vendor_id AND c.status <> 'closed') AS cooperative_count
               FROM subscriptions s JOIN vendors v ON v.id = s.vendor_id JOIN plans p ON p.id = s.plan_id
              WHERE s.vendor_id = ? ORDER BY s.id DESC LIMIT 1",
            [$this->vendorId]
        );
        if ($row === null) {
            return null;
        }
        return [
            'id'                   => (int) $row['id'],
            'vendor_id'            => (int) $row['vendor_id'],
            'vendor_code'          => $row['vendor_code'],
            'vendor_name'          => $row['vendor_name'],
            'plan_id'              => (int) $row['plan_id'],
            'plan_name'            => $row['plan_name'],
            'billing_cycle'        => $row['billing_cycle'],
            'status'               => $row['status'],
            'started_at'           => $row['started_at'],
            'current_period_start' => $row['current_period_start'],
            'current_period_end'   => $row['current_period_end'],
            'cooperative_count'    => (int) $row['cooperative_count'],
        ];
    }

    // InvoiceSummary (PELAN 18.7) — terkini dahulu
    private function invoices()
    {
        $rows = Database::fetchAll(
            "SELECT i.*, v.code AS vendor_code, v.company_name AS vendor_name
               FROM invoices i JOIN vendors v ON v.id = i.vendor_id
              WHERE i.vendor_id = ? ORDER BY i.id DESC LIMIT 50",
            [$this->vendorId]
        );
        return array_map(function ($row) {
            return [
                'id'           => (int) $row['id'],
                'invoice_no'   => $row['invoice_no'],
                'vendor_id'    => (int) $row['vendor_id'],
                'vendor_code'  => $row['vendor_code'],
                'vendor_name'  => $row['vendor_name'],
                'period_start' => $row['period_start'],
                'period_end'   => $row['period_end'],
                'subtotal'     => (int) $row['subtotal'],
                'tax'          => (int) $row['tax'],
                'total'        => (int) $row['total'],
                'status'       => $row['status'],
                'issued_at'    => IsoDate::format($row['issued_at']),
                'due_at'       => IsoDate::format($row['due_at']),
                'paid_at'      => IsoDate::format($row['paid_at']),
            ];
        }, $rows);
    }

    private function notes()
    {
        $rows = Database::fetchAll(
            "SELECT n.id, n.body, n.created_at, u.full_name
               FROM vendor_notes n LEFT JOIN platform_users u ON u.id = n.created_by
              WHERE n.vendor_id = ? ORDER BY n.id DESC",
            [$this->vendorId]
        );
        return array_map(function ($row) {
            return [
                'id'         => (int) $row['id'],
                'body'       => $row['body'],
                'created_by' => (string) $row['full_name'],
                'created_at' => IsoDate::format($row['created_at']),
            ];
        }, $rows);
    }
}
