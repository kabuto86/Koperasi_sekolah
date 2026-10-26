<?php
// Senarai PO koperasi (PELAN 18.8): tapis q (no. PO / pembekal / nota), status, supplier_id. Terbaru dahulu.
class ListPurchaseOrdersJob
{
    const STATUSES = ['draft', 'ordered', 'partial', 'received', 'cancelled'];

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
        $where = '';
        $args = [];
        $q = trim((string) ($this->params['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (po.po_no LIKE ? OR s.name LIKE ? OR po.note LIKE ?)';
            $args = array_fill(0, 3, Pagination::like($q));
        }
        $status = (string) ($this->params['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where .= ' AND po.status = ?';
            $args[] = $status;
        }
        $supplierId = (int) ($this->params['supplier_id'] ?? 0);
        if ($supplierId > 0) {
            $where .= ' AND po.supplier_id = ?';
            $args[] = $supplierId;
        }
        $total = Database::fetchValue(
            "SELECT COUNT(*) FROM purchase_orders po JOIN suppliers s ON s.vendor_id = po.vendor_id AND s.id = po.supplier_id
              WHERE po.vendor_id = ? AND po.cooperative_id = ? $where",
            array_merge([$this->vendorId, $this->cooperativeId], $args)
        );
        $items = PurchaseOrders::summaries($this->vendorId, $this->cooperativeId, $where, $args, "LIMIT $perPage OFFSET $offset");
        return Pagination::result($items, $total, $page, $perPage);
    }
}
