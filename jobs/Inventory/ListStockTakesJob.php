<?php
// Senarai stock take koperasi (terbaru dahulu, format senarai PELAN 18.2).
class ListStockTakesJob
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
        $total = Database::fetchValue(
            "SELECT COUNT(*) FROM stock_takes WHERE vendor_id = ? AND cooperative_id = ?",
            [$this->vendorId, $this->cooperativeId]
        );
        $items = StockTakes::summaries($this->vendorId, $this->cooperativeId, '', [], "LIMIT $perPage OFFSET $offset");
        return Pagination::result($items, $total, $page, $perPage);
    }
}
