<?php
// Senarai produk koperasi (PELAN 18.8): tapis q (nama/SKU/barcode), category_id, grade ('' | 'all' = tanpa darjah | kod),
// status (active/inactive), low_only. Koperasi sudah disahkan oleh pemanggil.
class ListProductsJob
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
        list($where, $args) = $this->filters();

        $total = Database::fetchValue(
            "SELECT COUNT(*) FROM products p LEFT JOIN (" . ProductCatalog::stockSql() . ") st ON st.product_id = p.id
              WHERE p.vendor_id = ? AND p.cooperative_id = ? $where",
            array_merge([$this->vendorId, $this->cooperativeId, $this->vendorId, $this->cooperativeId], $args)
        );
        $items = ProductCatalog::summaries(
            $this->vendorId, $this->cooperativeId, $where, $args,
            "ORDER BY p.sort_order, p.name LIMIT $perPage OFFSET $offset"
        );
        return Pagination::result($items, $total, $page, $perPage);
    }

    private function filters()
    {
        $where = '';
        $args = [];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
            $like = Pagination::like($q);
            array_push($args, $like, $like, $like);
        }
        $categoryId = (int) ($this->params['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $args[] = $categoryId;
        }
        $grade = (string) ($this->params['grade'] ?? '');
        if ($grade === 'all') {
            $where .= ' AND NOT EXISTS (SELECT 1 FROM product_grade_levels pg WHERE pg.vendor_id = p.vendor_id AND pg.product_id = p.id)';
        } elseif ($grade !== '') {
            $where .= ' AND EXISTS (SELECT 1 FROM product_grade_levels pg WHERE pg.vendor_id = p.vendor_id AND pg.product_id = p.id AND pg.grade_level_code = ?)';
            $args[] = $grade;
        }
        $status = (string) ($this->params['status'] ?? '');
        if ($status === 'active' || $status === 'inactive') {
            $where .= ' AND p.is_active = ?';
            $args[] = $status === 'active' ? 1 : 0;
        }
        if (in_array($this->params['low_only'] ?? '', [true, 1, '1', 'true'], true)) {
            $where .= ' AND st.is_low = 1';
        }
        return [$where, $args];
    }
}
