<?php
// Senarai langganan semua vendor untuk owner (PELAN 18.7 SubscriptionSummary). Tapis: q, status, billing_cycle, plan_id.
class ListSubscriptionsJob
{
    const STATUSES = ['trial', 'active', 'past_due', 'suspended', 'cancelled'];

    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $where = [];
        $params = [];
        $status = (string) ($this->input['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 's.status = ?';
            $params[] = $status;
        }
        $cycle = (string) ($this->input['billing_cycle'] ?? '');
        if (in_array($cycle, BillingPeriod::CYCLES, true)) {
            $where[] = 's.billing_cycle = ?';
            $params[] = $cycle;
        }
        if ((int) ($this->input['plan_id'] ?? 0) > 0) {
            $where[] = 's.plan_id = ?';
            $params[] = (int) $this->input['plan_id'];
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(v.code LIKE ? OR v.company_name LIKE ? OR p.name LIKE ?)';
            $params = array_merge($params, array_fill(0, 3, Pagination::like($q)));
        }
        $from = " FROM subscriptions s JOIN vendors v ON v.id = s.vendor_id JOIN plans p ON p.id = s.plan_id"
              . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
        $total = Database::fetchValue("SELECT COUNT(*)" . $from, $params);
        $rows = Database::fetchAll(
            "SELECT s.*, v.code AS vendor_code, v.company_name AS vendor_name, p.name AS plan_name,
                    (SELECT COUNT(*) FROM cooperatives c WHERE c.vendor_id = s.vendor_id AND c.status <> 'closed') AS cooperative_count"
            . $from . " ORDER BY s.id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        return Pagination::result(array_map([self::class, 'shape'], $rows), $total, $page, $perPage);
    }

    public static function shape(array $row)
    {
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
}
