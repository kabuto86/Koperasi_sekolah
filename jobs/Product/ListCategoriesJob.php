<?php
// Kategori koperasi + bilangan produk (PELAN 18.8). Koperasi sudah disahkan oleh pemanggil.
class ListCategoriesJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT c.id, c.name, c.sort_order, c.is_active,
                    (SELECT COUNT(*) FROM products p WHERE p.vendor_id = c.vendor_id AND p.category_id = c.id) AS product_count
               FROM categories c
              WHERE c.vendor_id = ? AND c.cooperative_id = ?
              ORDER BY c.sort_order, c.name",
            [$this->vendorId, $this->cooperativeId]
        );
        return array_map([self::class, 'shape'], $rows);
    }

    public static function find($vendorId, $cooperativeId, $id)
    {
        $row = Database::fetchOne(
            "SELECT c.id, c.name, c.sort_order, c.is_active,
                    (SELECT COUNT(*) FROM products p WHERE p.vendor_id = c.vendor_id AND p.category_id = c.id) AS product_count
               FROM categories c WHERE c.vendor_id = ? AND c.cooperative_id = ? AND c.id = ?",
            [(int) $vendorId, (int) $cooperativeId, (int) $id]
        );
        return $row === null ? null : self::shape($row);
    }

    public static function shape($row)
    {
        return [
            'id'            => (int) $row['id'],
            'name'          => $row['name'],
            'sort_order'    => (int) $row['sort_order'],
            'is_active'     => (bool) $row['is_active'],
            'product_count' => (int) $row['product_count'],
        ];
    }
}
