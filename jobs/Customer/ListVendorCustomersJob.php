<?php
// Ibu bapa koperasi (PELAN 18.9 vendor/customers/list) — HANYA ibu bapa yang ada anak aktif di koperasi ini, dan hanya anak itu.
// Akaun ibu bapa global (tiada vendor_id): skop melalui children.vendor_id + cooperative_id. Nota = vendor sesi sahaja.
// Tapis q (nama, telefon, e-mel, nama anak), grade, class_name. data.child_count = jumlah anak semua baris ditapis.
class ListVendorCustomersJob
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
        list($childWhere, $childArgs) = $this->childWhere();
        $where = "c.deleted_at IS NULL AND EXISTS (SELECT 1 FROM children k WHERE k.customer_id = c.id AND $childWhere)";
        $args = $childArgs;
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $like = Pagination::like($q);
            $where .= " AND (c.full_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?
                         OR EXISTS (SELECT 1 FROM children k WHERE k.customer_id = c.id AND $childWhere AND k.full_name LIKE ?))";
            $args = array_merge($args, [$like, $like, $like], $childArgs, [$like]);
        }

        $total = Database::fetchValue("SELECT COUNT(*) FROM customers c WHERE $where", $args);
        $childCount = Database::fetchValue(
            "SELECT COUNT(*) FROM children k WHERE $childWhere AND k.customer_id IN (SELECT c.id FROM customers c WHERE $where)",
            array_merge($childArgs, $args)
        );
        $rows = Database::fetchAll(
            "SELECT c.id, c.full_name, c.phone, c.email, c.relationship,
                    (SELECT n.note FROM vendor_customer_notes n WHERE n.vendor_id = ? AND n.customer_id = c.id) AS note,
                    (SELECT COUNT(*) FROM orders o WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.customer_id = c.id
                        AND o.status NOT IN ('cancelled', 'expired')) AS order_count,
                    (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.customer_id = c.id
                        AND o.status NOT IN ('cancelled', 'expired')) AS total_spent,
                    (SELECT MAX(o.created_at) FROM orders o WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.customer_id = c.id
                        AND o.status NOT IN ('cancelled', 'expired')) AS last_order_at
               FROM customers c WHERE $where ORDER BY c.full_name, c.id LIMIT $perPage OFFSET $offset",
            array_merge([$this->vendorId], $this->orderScope(), $this->orderScope(), $this->orderScope(), $args)
        );
        $children = $this->children(array_column($rows, 'id'), $childWhere, $childArgs);

        $items = array_map(function ($row) use ($children) {
            return [
                'id'            => (int) $row['id'],
                'full_name'     => $row['full_name'],
                'phone'         => (string) $row['phone'],
                'email'         => (string) $row['email'],
                'relationship'  => $row['relationship'],
                'children'      => $children[(int) $row['id']] ?? [],
                'order_count'   => (int) $row['order_count'],
                'total_spent'   => (int) $row['total_spent'],
                'last_order_at' => IsoDate::format($row['last_order_at']),
                'note'          => (string) $row['note'],
            ];
        }, $rows);
        $result = Pagination::result($items, $total, $page, $perPage);
        $result['child_count'] = (int) $childCount;
        return $result;
    }

    // Anak aktif koperasi ini (+ tapisan darjah/kelas). Alias k
    private function childWhere()
    {
        $where = 'k.vendor_id = ? AND k.cooperative_id = ? AND k.is_active = 1';
        $args = [$this->vendorId, $this->cooperativeId];
        foreach (['grade' => 'grade_level_code', 'class_name' => 'class_name'] as $key => $column) {
            $value = trim((string) ($this->params[$key] ?? ''));
            if ($value !== '') {
                $where .= " AND k.$column = ?";
                $args[] = $value;
            }
        }
        return [$where, $args];
    }

    // Argumen satu subkueri pesanan: vendor + koperasi
    private function orderScope()
    {
        return [$this->vendorId, $this->cooperativeId];
    }

    private function children(array $customerIds, $childWhere, array $childArgs)
    {
        if (!$customerIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($customerIds), '?'));
        $rows = Database::fetchAll(
            "SELECT k.id, k.customer_id, k.full_name, k.grade_level_code, k.class_name FROM children k
              JOIN grade_levels g ON g.code = k.grade_level_code
              WHERE $childWhere AND k.customer_id IN ($marks) ORDER BY g.sort_order, k.full_name",
            array_merge($childArgs, array_map('intval', $customerIds))
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['customer_id']][] = [
                'id'               => (int) $row['id'],
                'full_name'        => $row['full_name'],
                'grade_level_code' => $row['grade_level_code'],
                'class_name'       => (string) $row['class_name'],
            ];
        }
        return $result;
    }
}
