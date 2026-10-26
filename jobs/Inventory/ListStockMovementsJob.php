<?php
// Sejarah pergerakan stok koperasi (PELAN 18.8): tapis q, type, product_id, variant_id, date_from, date_to. Terbaru dahulu.
class ListStockMovementsJob
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
        $from = " FROM stock_movements m
                  JOIN products p ON p.vendor_id = m.vendor_id AND p.id = m.product_id
                  LEFT JOIN product_variants v ON v.vendor_id = m.vendor_id AND v.product_id = m.product_id AND v.id = m.variant_id
                  LEFT JOIN vendor_users u ON u.vendor_id = m.vendor_id AND u.id = m.vendor_user_id
                 WHERE m.vendor_id = ? AND m.cooperative_id = ? $where";
        $args = array_merge([$this->vendorId, $this->cooperativeId], $args);

        $total = Database::fetchValue("SELECT COUNT(*) $from", $args);
        $rows = Database::fetchAll(
            "SELECT m.id, m.cooperative_id, m.product_id, m.variant_id, p.name AS product_name, v.label AS variant_label,
                    COALESCE(v.sku, p.sku) AS sku, m.type, m.qty_change, m.balance_after, m.ref_type, m.ref_id,
                    u.full_name AS user_name, m.note, m.created_at
             $from ORDER BY m.id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        $items = array_map(function ($row) {
            return [
                'id'             => (int) $row['id'],
                'cooperative_id' => (int) $row['cooperative_id'],
                'product_id'     => (int) $row['product_id'],
                'variant_id'     => (int) $row['variant_id'],
                'product_name'   => $row['product_name'],
                'variant_label'  => $row['variant_label'],
                'sku'            => (string) $row['sku'],
                'type'           => $row['type'],
                'qty_change'     => (int) $row['qty_change'],
                'balance_after'  => (int) $row['balance_after'],
                'ref_type'       => $row['ref_type'],
                'ref_id'         => $row['ref_id'] === null ? null : (int) $row['ref_id'],
                'user_name'      => (string) $row['user_name'],
                'note'           => (string) $row['note'],
                'created_at'     => IsoDate::format($row['created_at']),
            ];
        }, $rows);
        return Pagination::result($items, $total, $page, $perPage);
    }

    private function filters()
    {
        $where = '';
        $args = [];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (p.name LIKE ? OR v.label LIKE ? OR p.sku LIKE ? OR v.sku LIKE ? OR m.note LIKE ? OR u.full_name LIKE ?)';
            $args = array_fill(0, 6, Pagination::like($q));
        }
        $type = (string) ($this->params['type'] ?? '');
        if (in_array($type, StockLedger::TYPES, true)) {
            $where .= ' AND m.type = ?';
            $args[] = $type;
        }
        $productId = (int) ($this->params['product_id'] ?? 0);
        if ($productId > 0) {
            $where .= ' AND m.product_id = ?';
            $args[] = $productId;
        }
        $variant = $this->params['variant_id'] ?? '';
        if ($variant !== '' && $variant !== null && preg_match('/^\d+$/', (string) $variant)) {
            $where .= ' AND m.variant_id = ?';
            $args[] = (int) $variant;
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            $date = (string) ($this->params[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where .= " AND DATE(m.created_at) $operator ?";
                $args[] = $date;
            }
        }
        return [$where, $args];
    }
}
