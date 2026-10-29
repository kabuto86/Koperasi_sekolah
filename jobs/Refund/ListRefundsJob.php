<?php
// Senarai refund koperasi (vendor/refunds/list — PELAN 18.10). Tapis q (no. refund / pesanan / kod / nama / sebab), method,
// status, date_from, date_to. data.total_amount = jumlah refund selesai yang sepadan tapisan.
class ListRefundsJob
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
        $from = "FROM refunds r JOIN orders o ON o.id = r.order_id AND o.vendor_id = r.vendor_id AND o.cooperative_id = r.cooperative_id
                 WHERE $where";
        $summary = Database::fetchOne(
            "SELECT COUNT(*) AS n, COALESCE(SUM(CASE WHEN r.status = 'completed' THEN r.amount END), 0) AS amount $from",
            $args
        );
        $rows = Database::fetchAll(Refunds::SELECT_SQL . " WHERE $where ORDER BY r.created_at DESC, r.id DESC LIMIT $perPage OFFSET $offset", $args);
        $data = Pagination::result(array_map([Refunds::class, 'shape'], $rows), (int) $summary['n'], $page, $perPage);
        $data['total_amount'] = (int) $summary['amount'];
        return $data;
    }

    private function filters()
    {
        $where = 'r.vendor_id = ? AND r.cooperative_id = ?';
        $args = [$this->vendorId, $this->cooperativeId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $like = Pagination::like($q);
            $where .= ' AND (r.refund_no LIKE ? OR o.order_no LIKE ? OR o.short_code LIKE ? OR o.customer_name LIKE ? OR r.reason LIKE ?)';
            $args = array_merge($args, [$like, $like, $like, $like, $like]);
        }
        $method = (string) ($this->params['method'] ?? '');
        if (in_array($method, Refunds::METHODS, true)) {
            $where .= ' AND r.method = ?';
            $args[] = $method;
        }
        $status = (string) ($this->params['status'] ?? '');
        if (in_array($status, ['pending', 'completed', 'failed'], true)) {
            $where .= ' AND r.status = ?';
            $args[] = $status;
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            $date = (string) ($this->params[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where .= " AND DATE(r.created_at) $operator ?";
                $args[] = $date;
            }
        }
        return [$where, $args];
    }
}
