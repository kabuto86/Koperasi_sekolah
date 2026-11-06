<?php
// Senarai dividen yang diisytihar koperasi (terbaru dahulu).
class ListDividendsJob
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
            "SELECT * FROM membership_dividends WHERE vendor_id = ? AND cooperative_id = ? ORDER BY year DESC",
            [$this->vendorId, $this->cooperativeId]
        );
        return array_map([Membership::class, 'shapeDividend'], $rows);
    }
}
