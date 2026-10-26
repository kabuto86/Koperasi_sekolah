<?php
// Senarai kakitangan vendor sesi (PELAN 18.10) + had pelan kakitangan aktif.
class ListVendorStaffJob
{
    private $vendorId;
    private $actorId;
    private $params;

    public function __construct($vendorId, $actorId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->params = $params;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = ['u.vendor_id = ?'];
        $args = [$this->vendorId];

        $role = (string) ($this->params['role'] ?? '');
        if (in_array($role, VendorAuth::ALL_ROLES, true)) {
            $where[] = 'u.role = ?';
            $args[] = $role;
        }
        $status = (string) ($this->params['status'] ?? '');
        if ($status === 'active' || $status === 'inactive') {
            $where[] = 'u.is_active = ?';
            $args[] = $status === 'active' ? 1 : 0;
        }
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = Pagination::like($q);
            array_push($args, $like, $like, $like);
        }

        $sqlWhere = implode(' AND ', $where);
        $total = Database::fetchValue("SELECT COUNT(*) FROM vendor_users u WHERE $sqlWhere", $args);
        $ids = Database::fetchAll(
            "SELECT u.id FROM vendor_users u WHERE $sqlWhere ORDER BY u.is_active DESC, u.full_name LIMIT $perPage OFFSET $offset",
            $args
        );
        $items = [];
        foreach ($ids as $row) {
            $items[] = self::find($this->vendorId, $row['id'], $this->actorId);
        }

        $result = Pagination::result($items, $total, $page, $perPage);
        $result['limit'] = self::limit($this->vendorId);
        return $result;
    }

    // Satu kakitangan dalam vendor ini (null jika tiada / milik vendor lain)
    public static function find($vendorId, $userId, $actorId)
    {
        $row = Database::fetchOne(
            "SELECT id, full_name, email, phone, role, is_active, pin_hash IS NOT NULL AS pin_set,
                    totp_secret IS NOT NULL AS totp_enabled, last_login_at
               FROM vendor_users WHERE id = ? AND vendor_id = ?",
            [(int) $userId, (int) $vendorId]
        );
        if ($row === null) {
            return null;
        }
        $coops = [];
        if ($row['role'] !== 'vendor_admin') {
            $coops = Database::fetchAll(
                "SELECT c.id, c.school_name FROM vendor_user_cooperatives vuc
                   JOIN cooperatives c ON c.id = vuc.cooperative_id AND c.vendor_id = vuc.vendor_id
                  WHERE vuc.vendor_id = ? AND vuc.vendor_user_id = ? ORDER BY c.school_name",
                [(int) $vendorId, (int) $userId]
            );
        }
        return [
            'id'                => (int) $row['id'],
            'full_name'         => $row['full_name'],
            'email'             => $row['email'],
            'phone'             => (string) $row['phone'],
            'role'              => $row['role'],
            'cooperative_ids'   => array_map('intval', array_column($coops, 'id')),
            'cooperative_names' => array_column($coops, 'school_name'),
            'is_active'         => (bool) $row['is_active'],
            'pin_set'           => (bool) $row['pin_set'],
            'totp_enabled'      => (bool) $row['totp_enabled'],
            'last_login_at'     => IsoDate::format($row['last_login_at']),
            'is_self'           => (int) $row['id'] === (int) $actorId,
        ];
    }

    // [max (null = tanpa had), used] — kakitangan aktif berbanding had pelan langganan semasa
    public static function limit($vendorId)
    {
        $max = Database::fetchOne(
            "SELECT p.max_staff FROM subscriptions s JOIN plans p ON p.id = s.plan_id
              WHERE s.vendor_id = ? AND s.status <> 'cancelled' ORDER BY s.id DESC LIMIT 1",
            [(int) $vendorId]
        );
        $used = Database::fetchValue("SELECT COUNT(*) FROM vendor_users WHERE vendor_id = ? AND is_active = 1", [(int) $vendorId]);
        return [
            'max'  => $max === null || $max['max_staff'] === null ? null : (int) $max['max_staff'],
            'used' => (int) $used,
        ];
    }
}
