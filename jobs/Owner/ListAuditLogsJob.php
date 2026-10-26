<?php
// Log audit seluruh platform (PELAN 18.7 AuditLog) — terkini dahulu. Tapis: q, actor_type, date_from, date_to.
class ListAuditLogsJob
{
    const ACTOR_TYPES = ['owner', 'vendor_user', 'customer', 'system'];

    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function run()
    {
        $v = new Validator($this->input);
        $v->date('date_from')->date('date_to');
        ValidationException::throwIfFails($v);

        list($page, $perPage, $offset) = Pagination::from($this->input);
        $where = [];
        $params = [];
        $actorType = (string) ($this->input['actor_type'] ?? '');
        if (in_array($actorType, self::ACTOR_TYPES, true)) {
            $where[] = 'a.actor_type = ?';
            $params[] = $actorType;
        }
        if (!empty($this->input['date_from'])) {
            $where[] = 'a.created_at >= ?';
            $params[] = $this->input['date_from'] . ' 00:00:00';
        }
        if (!empty($this->input['date_to'])) {
            $where[] = 'a.created_at <= ?';
            $params[] = $this->input['date_to'] . ' 23:59:59';
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(a.action LIKE ? OR a.entity LIKE ? OR a.detail_json LIKE ? OR a.ip_address LIKE ? OR v.code LIKE ?
                         OR pu.full_name LIKE ? OR vu.full_name LIKE ? OR cu.full_name LIKE ?)';
            $params = array_merge($params, array_fill(0, 8, Pagination::like($q)));
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = " FROM audit_logs a
                  LEFT JOIN vendors v ON v.id = a.vendor_id
                  LEFT JOIN platform_users pu ON a.actor_type = 'owner' AND pu.id = a.actor_id
                  LEFT JOIN vendor_users vu ON a.actor_type = 'vendor_user' AND vu.id = a.actor_id
                  LEFT JOIN customers cu ON a.actor_type = 'customer' AND cu.id = a.actor_id";

        $total = Database::fetchValue("SELECT COUNT(*)" . $from . $whereSql, $params);
        $rows = Database::fetchAll(
            "SELECT a.*, v.code AS vendor_code, COALESCE(pu.full_name, vu.full_name, cu.full_name) AS actor_name"
            . $from . $whereSql . " ORDER BY a.id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $items = array_map(function ($row) {
            $detail = json_decode((string) $row['detail_json'], true);
            return [
                'id'          => (int) $row['id'],
                'actor_type'  => $row['actor_type'],
                'actor_name'  => $row['actor_type'] === 'system' ? 'system' : (string) $row['actor_name'],
                'vendor_code' => $row['vendor_code'],
                'action'      => $row['action'],
                'entity'      => $row['entity'],
                'entity_id'   => $row['entity_id'] === null ? null : (int) $row['entity_id'],
                'detail'      => is_array($detail) ? (string) ($detail['detail'] ?? '') : '',
                'ip_address'  => $row['ip_address'],
                'created_at'  => IsoDate::format($row['created_at']),
            ];
        }, $rows);
        return Pagination::result($items, $total, $page, $perPage);
    }
}
