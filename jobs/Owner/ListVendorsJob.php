<?php
// Senarai vendor untuk owner (PELAN 18.7 VendorSummary). Tapis: q, status, plan_id.
class ListVendorsJob
{
    const SELECT = "SELECT v.*, s.plan_id, p.name AS plan_name, s.billing_cycle, s.current_period_end,
                           (SELECT COUNT(*) FROM cooperatives c WHERE c.vendor_id = v.id AND c.status <> 'closed') AS cooperative_count
                      FROM vendors v
                      LEFT JOIN subscriptions s ON s.id = (SELECT MAX(s2.id) FROM subscriptions s2 WHERE s2.vendor_id = v.id)
                      LEFT JOIN plans p ON p.id = s.plan_id";

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
            $where[] = 'v.status = ?';
            $params[] = $status;
        }
        $planId = (int) ($this->input['plan_id'] ?? 0);
        if ($planId > 0) {
            $where[] = 's.plan_id = ?';
            $params[] = $planId;
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(v.code LIKE ? OR v.company_name LIKE ? OR v.contact_name LIKE ? OR v.email LIKE ? OR v.phone LIKE ?)';
            $params = array_merge($params, array_fill(0, 5, Pagination::like($q)));
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = Database::fetchValue(
            "SELECT COUNT(*) FROM vendors v LEFT JOIN subscriptions s ON s.id = (SELECT MAX(s2.id) FROM subscriptions s2 WHERE s2.vendor_id = v.id)" . $whereSql,
            $params
        );
        $rows = Database::fetchAll(self::SELECT . $whereSql . " ORDER BY v.id LIMIT $perPage OFFSET $offset", $params);
        return Pagination::result(array_map([self::class, 'shape'], $rows), $total, $page, $perPage);
    }

    public static function find($id)
    {
        $row = Database::fetchOne(self::SELECT . " WHERE v.id = ?", [(int) $id]);
        return $row === null ? null : self::shape($row);
    }

    public static function shape($row)
    {
        return [
            'id'                 => (int) $row['id'],
            'code'               => $row['code'],
            'company_name'       => $row['company_name'],
            'contact_name'       => $row['contact_name'],
            'email'              => $row['email'],
            'phone'              => $row['phone'],
            'status'             => $row['status'],
            'trial_ends_at'      => IsoDate::format($row['trial_ends_at']),
            'plan_id'            => $row['plan_id'] === null ? null : (int) $row['plan_id'],
            'plan_name'          => (string) $row['plan_name'],
            'billing_cycle'      => $row['billing_cycle'],
            'cooperative_count'  => (int) $row['cooperative_count'],
            'current_period_end' => $row['current_period_end'],
            'created_at'         => IsoDate::format($row['created_at']),
        ];
    }
}
