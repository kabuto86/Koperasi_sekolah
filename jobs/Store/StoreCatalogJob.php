<?php
// Katalog store (PELAN 18.12 store/catalog): produk aktif koperasi slug, tapis q / kategori / darjah, + kategori aktif.
class StoreCatalogJob
{
    const PER_PAGE = 24;

    private $cooperative;
    private $input;

    public function __construct(array $cooperative, array $input)
    {
        $this->cooperative = $cooperative;
        $this->input = $input;
    }

    public function run()
    {
        $vendorId = (int) $this->cooperative['vendor_id'];
        $cooperativeId = (int) $this->cooperative['id'];
        list($page, $perPage, $offset) = Pagination::from(array_merge(['per_page' => self::PER_PAGE], $this->input));

        $where = "p.vendor_id = ? AND p.cooperative_id = ? AND p.is_active = 1 AND (p.category_id IS NULL OR c.is_active = 1)";
        $args = [Lang::code(), $vendorId, $cooperativeId];
        $categoryId = (int) ($this->input['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $args[] = $categoryId;
        }
        // Produk tanpa darjah = untuk semua darjah
        $grade = trim((string) ($this->input['grade'] ?? ''));
        if ($grade !== '') {
            $where .= " AND (NOT EXISTS (SELECT 1 FROM product_grade_levels g WHERE g.vendor_id = p.vendor_id AND g.product_id = p.id)
                         OR EXISTS (SELECT 1 FROM product_grade_levels g WHERE g.vendor_id = p.vendor_id AND g.product_id = p.id AND g.grade_level_code = ?))";
            $args[] = $grade;
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (p.name LIKE ? OR pt.name LIKE ? OR p.sku LIKE ?)';
            $like = Pagination::like($q);
            array_push($args, $like, $like, $like);
        }

        $from = "FROM products p
                 LEFT JOIN categories c ON c.vendor_id = p.vendor_id AND c.id = p.category_id
                 LEFT JOIN product_translations pt ON pt.vendor_id = p.vendor_id AND pt.product_id = p.id AND pt.language_code = ?
                WHERE $where";
        $total = Database::fetchValue("SELECT COUNT(*) $from", $args);
        $rows = Database::fetchAll(
            "SELECT p.id, p.category_id, p.name, p.description, p.sku, p.price, p.unit, p.image_path, p.max_per_order, p.has_variants,
                    c.name AS category_name, pt.name AS tr_name, pt.description AS tr_description
             $from ORDER BY p.sort_order, p.name LIMIT $perPage OFFSET $offset",
            $args
        );

        $products = array_map(['StorePricing', 'shapeProduct'], $rows);
        $ids = array_column($products, 'id');
        $pricing = new StorePricing($this->cooperative);
        $variants = $pricing->variants($ids);
        $grades = ProductCatalog::gradeLevels($vendorId, $ids);
        $items = array_map(function ($product) use ($pricing, $variants, $grades) {
            return $pricing->card($product, $variants[$product['id']] ?? [], $grades[$product['id']] ?? []);
        }, $products);

        $data = Pagination::result($items, $total, $page, $perPage);
        $data['categories'] = Database::fetchAll(
            "SELECT id, name FROM categories WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 ORDER BY sort_order, name",
            [$vendorId, $cooperativeId]
        );
        foreach ($data['categories'] as &$category) {
            $category['id'] = (int) $category['id'];
        }
        return $data;
    }
}
