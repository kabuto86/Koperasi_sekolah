<?php
// Senarai ahli koperasi (vendor/membership/members): tapis q (nama / no. ahli / telefon / kelas), status, member_type.
// Ringkasan: ahli aktif & jumlah syer semua ahli.
class ListMembersJob
{
    private $vendorId;
    private $cooperativeId;
    private $params;

    public function __construct($vendorId, $cooperativeId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->params = $params;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = 'm.vendor_id = ? AND m.cooperative_id = ?';
        $args = [$this->vendorId, $this->cooperativeId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (m.full_name LIKE ? OR m.member_no LIKE ? OR m.phone LIKE ? OR m.class_name LIKE ?)';
            array_push($args, Pagination::like($q), Pagination::like($q), Pagination::like($q), Pagination::like($q));
        }
        $status = (string) ($this->params['status'] ?? '');
        if (in_array($status, Membership::STATUSES, true)) {
            $where .= ' AND m.status = ?';
            $args[] = $status;
        }
        $type = (string) ($this->params['member_type'] ?? '');
        if (in_array($type, Membership::TYPES, true)) {
            $where .= ' AND m.member_type = ?';
            $args[] = $type;
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM membership_members m WHERE $where", $args);
        $rows = Database::fetchAll(
            "SELECT m.*, (SELECT " . Membership::BALANCE_SQL . " FROM membership_share_transactions t
                           WHERE t.vendor_id = m.vendor_id AND t.member_id = m.id) AS share_balance
               FROM membership_members m WHERE $where
              ORDER BY m.member_no LIMIT $perPage OFFSET $offset",
            $args
        );
        $result = Pagination::result(array_map([Membership::class, 'shape'], $rows), $total, $page, $perPage);
        $result['summary'] = [
            'active_members' => (int) Database::fetchValue(
                "SELECT COUNT(*) FROM membership_members WHERE vendor_id = ? AND cooperative_id = ? AND status = 'active'",
                [$this->vendorId, $this->cooperativeId]
            ),
            'total_shares'   => (int) Database::fetchValue(
                "SELECT " . Membership::BALANCE_SQL . " FROM membership_share_transactions t WHERE t.vendor_id = ? AND t.cooperative_id = ?",
                [$this->vendorId, $this->cooperativeId]
            ),
        ];
        return $result;
    }
}
