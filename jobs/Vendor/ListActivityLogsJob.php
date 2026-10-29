<?php
// Log aktiviti vendor (PELAN 18.10 vendor/activity-logs/list). Tapis: q, user_id, date_from, date_to; skop koperasi semasa.
// Pentadbir: semua log vendor (termasuk peringkat vendor). Pengurus: hanya koperasi yang diberi kepadanya.
class ListActivityLogsJob
{
    private $vendorId;
    private $allowedCooperativeIds;
    private $cooperativeId;
    private $input;

    // $allowedCooperativeIds null = semua (pentadbir). $cooperativeId = skop "koperasi semasa" (sudah disahkan) atau null
    public function __construct($vendorId, $allowedCooperativeIds, $cooperativeId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->allowedCooperativeIds = $allowedCooperativeIds === null ? null : array_map('intval', $allowedCooperativeIds);
        $this->cooperativeId = $cooperativeId === null ? null : (int) $cooperativeId;
        $this->input = $input;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $where = ['a.vendor_id = ?'];
        $params = [$this->vendorId];
        if ($this->allowedCooperativeIds !== null) {
            $ids = $this->allowedCooperativeIds ?: [0];
            $where[] = 'a.cooperative_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params = array_merge($params, $ids);
        }
        if ($this->cooperativeId !== null) {
            $where[] = 'a.cooperative_id = ?';
            $params[] = $this->cooperativeId;
        }
        if ((int) ($this->input['user_id'] ?? 0) > 0) {
            $where[] = 'a.vendor_user_id = ?';
            $params[] = (int) $this->input['user_id'];
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            $date = (string) ($this->input[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where[] = "DATE(a.created_at) $operator ?";
                $params[] = $date;
            }
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(a.action LIKE ? OR a.detail_json LIKE ? OR u.full_name LIKE ?)';
            $params = array_merge($params, array_fill(0, 3, Pagination::like($q)));
        }
        $from = " FROM activity_logs a
                  LEFT JOIN vendor_users u ON u.id = a.vendor_user_id AND u.vendor_id = a.vendor_id
                  LEFT JOIN cooperatives c ON c.id = a.cooperative_id AND c.vendor_id = a.vendor_id
                 WHERE " . implode(' AND ', $where);
        $total = Database::fetchValue("SELECT COUNT(*)" . $from, $params);
        $rows = Database::fetchAll(
            "SELECT a.*, u.full_name AS user_name, c.school_name" . $from . " ORDER BY a.id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $data = Pagination::result(array_map([self::class, 'shape'], $rows), $total, $page, $perPage);
        $data['users'] = array_map(function ($row) {
            return ['id' => (int) $row['id'], 'full_name' => $row['full_name']];
        }, Database::fetchAll("SELECT id, full_name FROM vendor_users WHERE vendor_id = ? ORDER BY full_name", [$this->vendorId]));
        return $data;
    }

    private static function shape(array $row)
    {
        $detail = json_decode((string) $row['detail_json'], true);
        return [
            'id'               => (int) $row['id'],
            'cooperative_id'   => $row['cooperative_id'] === null ? null : (int) $row['cooperative_id'],
            'cooperative_name' => $row['school_name'],
            'user_id'          => $row['vendor_user_id'] === null ? null : (int) $row['vendor_user_id'],
            'user_name'        => (string) $row['user_name'],
            'action'           => $row['action'],
            'entity'           => $row['entity'],
            'entity_id'        => $row['entity_id'] === null ? null : (int) $row['entity_id'],
            'detail'           => is_array($detail) ? (string) ($detail['detail'] ?? '') : '',
            'created_at'       => IsoDate::format($row['created_at']),
        ];
    }
}
