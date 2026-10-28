<?php
// Senarai promosi koperasi (vendor/promotions/list — PELAN 18.10). Tapis q (nama / kod), state (dikira pelayan).
class ListPromotionsJob
{
    const STATES = ['active', 'scheduled', 'expired', 'inactive', 'used_up'];

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
        $where = 'vendor_id = ? AND cooperative_id = ?';
        $args = [$this->vendorId, $this->cooperativeId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (name LIKE ? OR code LIKE ?)';
            $args[] = Pagination::like($q);
            $args[] = Pagination::like($q);
        }
        $state = (string) ($this->params['state'] ?? '');
        if (in_array($state, self::STATES, true)) {
            $where .= ' AND ' . Promotions::STATE_SQL . ' = ?';
            $args[] = $state;
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM promotions WHERE $where", $args);
        $rows = Database::fetchAll(
            "SELECT *, " . Promotions::STATE_SQL . " AS state FROM promotions WHERE $where
              ORDER BY starts_on DESC, id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        return Pagination::result(array_map([Promotions::class, 'shape'], $rows), $total, $page, $perPage);
    }
}
