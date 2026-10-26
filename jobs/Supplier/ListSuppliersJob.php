<?php
// Pembekal vendor sesi — dikongsi semua koperasi (PELAN 18.8). Tapis q, status. open_po_count = PO dipesan/separa.
class ListSuppliersJob
{
    private $vendorId;
    private $params;

    public function __construct($vendorId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->params = $params;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = 's.vendor_id = ?';
        $args = [$this->vendorId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (s.name LIKE ? OR s.contact_name LIKE ? OR s.phone LIKE ? OR s.email LIKE ?)';
            $args = array_merge($args, array_fill(0, 4, Pagination::like($q)));
        }
        $status = (string) ($this->params['status'] ?? '');
        if ($status === 'active' || $status === 'inactive') {
            $where .= ' AND s.is_active = ?';
            $args[] = $status === 'active' ? 1 : 0;
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM suppliers s WHERE $where", $args);
        $rows = Database::fetchAll(
            "SELECT s.*, " . self::openPoSql() . " AS open_po_count
               FROM suppliers s WHERE $where ORDER BY s.is_active DESC, s.name LIMIT $perPage OFFSET $offset",
            $args
        );
        return Pagination::result(array_map([self::class, 'shape'], $rows), $total, $page, $perPage);
    }

    public static function find($vendorId, $id)
    {
        $row = Database::fetchOne(
            "SELECT s.*, " . self::openPoSql() . " AS open_po_count FROM suppliers s WHERE s.vendor_id = ? AND s.id = ?",
            [(int) $vendorId, (int) $id]
        );
        return $row === null ? null : self::shape($row);
    }

    private static function openPoSql()
    {
        return "(SELECT COUNT(*) FROM purchase_orders po WHERE po.vendor_id = s.vendor_id AND po.supplier_id = s.id
                    AND po.status IN ('ordered', 'partial'))";
    }

    public static function shape($row)
    {
        return [
            'id'            => (int) $row['id'],
            'name'          => $row['name'],
            'contact_name'  => (string) $row['contact_name'],
            'phone'         => (string) $row['phone'],
            'email'         => (string) $row['email'],
            'address'       => (string) $row['address'],
            'is_active'     => (bool) $row['is_active'],
            'open_po_count' => (int) $row['open_po_count'],
        ];
    }
}
