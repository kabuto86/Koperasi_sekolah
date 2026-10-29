<?php
// Senarai invois (PELAN 18.7 InvoiceSummary). Owner: semua vendor (tapis q, status, vendor_id).
// Vendor: $vendorId dari sesi — invois sendiri sahaja, tanpa draf, + line_count (18.10).
class ListInvoicesJob
{
    const STATUSES = ['draft', 'issued', 'paid', 'void'];

    private $vendorId;
    private $input;

    // $vendorId null = owner
    public function __construct($vendorId, array $input)
    {
        $this->vendorId = $vendorId === null ? null : (int) $vendorId;
        $this->input = $input;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $where = [];
        $params = [];
        if ($this->vendorId !== null) {
            $where[] = "i.vendor_id = ? AND i.status <> 'draft'";
            $params[] = $this->vendorId;
        } elseif ((int) ($this->input['vendor_id'] ?? 0) > 0) {
            $where[] = 'i.vendor_id = ?';
            $params[] = (int) $this->input['vendor_id'];
        }
        $status = (string) ($this->input['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'i.status = ?';
            $params[] = $status;
        }
        $q = trim((string) ($this->input['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(i.invoice_no LIKE ? OR v.code LIKE ? OR v.company_name LIKE ?)';
            $params = array_merge($params, array_fill(0, 3, Pagination::like($q)));
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = " FROM invoices i JOIN vendors v ON v.id = i.vendor_id" . $whereSql;

        $total = Database::fetchValue("SELECT COUNT(*)" . $from, $params);
        $rows = Database::fetchAll(
            "SELECT i.*, v.code AS vendor_code, v.company_name AS vendor_name,
                    (SELECT COUNT(*) FROM invoice_lines l WHERE l.invoice_id = i.id) AS line_count" . $from
            . " ORDER BY i.id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $items = array_map(function ($row) {
            $data = Invoices::summary($row);
            if ($this->vendorId !== null) {
                $data['line_count'] = (int) $row['line_count'];
            }
            return $data;
        }, $rows);
        return Pagination::result($items, $total, $page, $perPage);
    }
}
