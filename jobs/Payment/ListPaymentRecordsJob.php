<?php
// Rekod bayaran koperasi (PELAN 18.10 vendor/payments/list) dari jadual payments. Tapis q, method, status, date_from, date_to.
// Tarikh = masa disahkan (verified_at) atau masa dicipta. summary dikira atas semua baris yang ditapis.
class ListPaymentRecordsJob
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
        $from = "FROM payments p JOIN orders o ON o.id = p.order_id AND o.vendor_id = p.vendor_id WHERE $where";
        $refunded = "(SELECT COALESCE(SUM(r.amount), 0) FROM refunds r WHERE r.vendor_id = p.vendor_id AND r.payment_id = p.id
                        AND r.status = 'completed')";

        $total = Database::fetchValue("SELECT COUNT(*) $from", $args);
        $rows = Database::fetchAll(
            "SELECT p.*, o.order_no, o.short_code, o.customer_name, $refunded AS refunded_amount,
                    (SELECT vu.full_name FROM vendor_users vu WHERE vu.vendor_id = p.vendor_id AND vu.id = p.received_by) AS received_by_name
             $from ORDER BY COALESCE(p.verified_at, p.created_at) DESC, p.id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        $summary = Database::fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN p.method = 'cash' AND p.status IN ('paid', 'refunded') THEN p.amount END), 0) AS cash_total,
                    COALESCE(SUM(CASE WHEN p.method = 'online' AND p.status IN ('paid', 'refunded') THEN p.amount END), 0) AS online_total,
                    COALESCE(SUM($refunded), 0) AS refunded_total,
                    COALESCE(SUM(p.status = 'pending'), 0) AS pending_count
             $from",
            $args
        );
        $result = Pagination::result(array_map([self::class, 'shape'], $rows), $total, $page, $perPage);
        $result['summary'] = array_map('intval', $summary);
        return $result;
    }

    private function filters()
    {
        $where = 'p.vendor_id = ? AND p.cooperative_id = ?';
        $args = [$this->vendorId, $this->cooperativeId];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (o.order_no LIKE ? OR o.short_code LIKE ? OR o.customer_name LIKE ? OR p.gateway_ref LIKE ?)';
            $args = array_merge($args, array_fill(0, 4, Pagination::like($q)));
        }
        $method = (string) ($this->params['method'] ?? '');
        if (in_array($method, ['cash', 'online'], true)) {
            $where .= ' AND p.method = ?';
            $args[] = $method;
        }
        $status = (string) ($this->params['status'] ?? '');
        if (in_array($status, ['pending', 'paid', 'failed', 'refunded'], true)) {
            $where .= ' AND p.status = ?';
            $args[] = $status;
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            $date = (string) ($this->params[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where .= " AND DATE(COALESCE(p.verified_at, p.created_at)) $operator ?";
                $args[] = $date;
            }
        }
        return [$where, $args];
    }

    // PaymentRecord (PELAN 18.10)
    public static function shape(array $row)
    {
        return [
            'id'              => (int) $row['id'],
            'order_id'        => (int) $row['order_id'],
            'order_no'        => $row['order_no'],
            'short_code'      => $row['short_code'],
            'customer_name'   => $row['customer_name'],
            'method'          => $row['method'],
            'gateway_code'    => $row['gateway_code'],
            'gateway_ref'     => $row['gateway_ref'],
            'amount'          => (int) $row['amount'],
            'refunded_amount' => (int) $row['refunded_amount'],
            'status'          => $row['status'],
            'received_by'     => $row['received_by_name'],
            'paid_at'         => IsoDate::format($row['status'] === 'paid' || $row['status'] === 'refunded' ? $row['verified_at'] : null),
            'created_at'      => IsoDate::format($row['created_at']),
        ];
    }
}
