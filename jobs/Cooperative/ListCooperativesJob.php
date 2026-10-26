<?php
// Senarai koperasi vendor sesi (pemilih koperasi panel — PELAN 18.4, ringkasan 18.8). Tapisan q (nama/kod/slug), status.
// $allowedIds = null untuk pentadbir (semua); senarai id untuk pengurus/juruwang (hanya yang diberi).
// B2: bacaan sahaja. Simpan / sunting koperasi → B4.
class ListCooperativesJob
{
    private $vendorId;
    private $allowedIds;
    private $params;

    public function __construct($vendorId, $allowedIds, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->allowedIds = $allowedIds;
        $this->params = $params;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = ['vendor_id = ?'];
        $args = [$this->vendorId];

        if ($this->allowedIds !== null) {
            if (!$this->allowedIds) {
                return Pagination::result([], 0, $page, $perPage);
            }
            $where[] = 'id IN (' . implode(',', array_fill(0, count($this->allowedIds), '?')) . ')';
            $args = array_merge($args, array_map('intval', $this->allowedIds));
        }
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(school_name LIKE ? OR school_code LIKE ? OR slug LIKE ?)';
            $like = Pagination::like($q);
            array_push($args, $like, $like, $like);
        }
        $status = (string) ($this->params['status'] ?? '');
        if (in_array($status, ['active', 'suspended', 'closed'], true)) {
            $where[] = 'status = ?';
            $args[] = $status;
        }

        $sqlWhere = implode(' AND ', $where);
        $total = Database::fetchValue("SELECT COUNT(*) FROM cooperatives WHERE $sqlWhere", $args);
        $rows = Database::fetchAll(
            "SELECT c.id, c.vendor_id, c.slug, c.school_name, c.school_code, c.school_type, c.state, c.district, c.address, c.phone,
                    c.logo_path, c.template_id, c.default_language, c.accept_cash, c.accept_online, c.status, c.created_at,
                    t.name AS template_name,
                    (SELECT COUNT(*) FROM school_classes sc WHERE sc.vendor_id = c.vendor_id AND sc.cooperative_id = c.id) AS class_count,
                    (SELECT COUNT(*) FROM products p WHERE p.vendor_id = c.vendor_id AND p.cooperative_id = c.id) AS product_count
               FROM (SELECT * FROM cooperatives WHERE $sqlWhere ORDER BY school_name LIMIT $perPage OFFSET $offset) c
               JOIN templates t ON t.id = c.template_id
              ORDER BY c.school_name",
            $args
        );
        $items = [];
        foreach ($rows as $row) {
            $item = self::shape($row);
            $item['template_name'] = $row['template_name'];
            $item['class_count'] = (int) $row['class_count'];
            $item['product_count'] = (int) $row['product_count'];
            $item['modules'] = $this->activeModules($row['id']);
            $items[] = $item;
        }
        return Pagination::result($items, $total, $page, $perPage);
    }

    // Kod modul add-on aktif untuk koperasi ini
    private function activeModules($cooperativeId)
    {
        $rows = Database::fetchAll(
            "SELECT m.code FROM cooperative_modules cm JOIN modules m ON m.id = cm.module_id
              WHERE cm.vendor_id = ? AND cm.cooperative_id = ? AND cm.status = 'active' ORDER BY m.sort_order",
            [$this->vendorId, (int) $cooperativeId]
        );
        return array_column($rows, 'code');
    }

    public static function shape($row)
    {
        return [
            'id'               => (int) $row['id'],
            'vendor_id'        => (int) $row['vendor_id'],
            'slug'             => $row['slug'],
            'school_name'      => $row['school_name'],
            'school_code'      => $row['school_code'],
            'school_type'      => $row['school_type'],
            'state'            => $row['state'],
            'district'         => $row['district'],
            'address'          => $row['address'],
            'phone'            => $row['phone'],
            'logo_url'         => $row['logo_path'] ? View::url('uploads/' . $row['logo_path']) : null,
            'template_id'      => (int) $row['template_id'],
            'default_language' => $row['default_language'],
            'accept_cash'      => (bool) $row['accept_cash'],
            'accept_online'    => (bool) $row['accept_online'],
            'status'           => $row['status'],
            'created_at'       => IsoDate::format($row['created_at']),
        ];
    }
}
