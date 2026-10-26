<?php
// Senarai stok koperasi (PELAN 18.8): tapis q (nama/varian/SKU), category_id, low_only. data.low_count = bilangan stok rendah.
class ListInventoryJob
{
    const LOW_SQL = ' AND s.qty_on_hand - s.qty_reserved <= s.min_stock';

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
        list($where, $args) = $this->filters();
        $lowOnly = in_array($this->params['low_only'] ?? '', [true, 1, '1', 'true'], true);
        $listWhere = $lowOnly ? $where . self::LOW_SQL : $where;

        $total = InventoryRows::count($this->vendorId, $this->cooperativeId, $listWhere, $args);
        $items = InventoryRows::fetch($this->vendorId, $this->cooperativeId, $listWhere, $args, "LIMIT $perPage OFFSET $offset");
        $result = Pagination::result($items, $total, $page, $perPage);
        $result['low_count'] = InventoryRows::count($this->vendorId, $this->cooperativeId, $where . self::LOW_SQL, $args);
        return $result;
    }

    private function filters()
    {
        $where = '';
        $args = [];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (p.name LIKE ? OR v.label LIKE ? OR p.sku LIKE ? OR v.sku LIKE ?)';
            $like = Pagination::like($q);
            array_push($args, $like, $like, $like, $like);
        }
        $categoryId = (int) ($this->params['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $args[] = $categoryId;
        }
        return [$where, $args];
    }
}
