<?php
// Senarai jualan murah koperasi (vendor/clearance/list — PELAN 18.13). Tapis q (nama) & state (dikira pelayan).
class ListClearanceSalesJob
{
    const STATES = ['active', 'scheduled', 'ended', 'inactive'];

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
        $where = 's.vendor_id = ? AND s.cooperative_id = ?';
        $args = [$this->vendorId, $this->cooperativeId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND s.name LIKE ?';
            $args[] = Pagination::like($q);
        }
        $state = (string) ($this->params['state'] ?? '');
        if (in_array($state, self::STATES, true)) {
            $where .= ' AND ' . ClearanceSales::STATE_SQL . ' = ?';
            $args[] = $state;
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM clearance_sales s WHERE $where", $args);
        $rows = Database::fetchAll(
            "SELECT s.*, " . ClearanceSales::STATE_SQL . " AS state,
                    (SELECT COUNT(*) FROM clearance_items i WHERE i.vendor_id = s.vendor_id AND i.clearance_sale_id = s.id) AS item_count
               FROM clearance_sales s WHERE $where
              ORDER BY s.starts_at DESC, s.id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        return Pagination::result(array_map([ClearanceSales::class, 'shape'], $rows), $total, $page, $perPage);
    }
}
