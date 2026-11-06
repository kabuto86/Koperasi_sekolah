<?php
// Pratonton dividen (tahun + kadar) sebelum diisytihar — tiada apa-apa disimpan.
class PreviewDividendJob
{
    private $vendorId;
    private $cooperativeId;
    private $input;

    public function __construct($vendorId, $cooperativeId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->input = $input;
    }

    public function run()
    {
        list($year, $rate) = DividendCalculator::validate($this->input);
        list($lines, $shares, $total) = DividendCalculator::lines($this->vendorId, $this->cooperativeId, $year, $rate);
        return [
            'year'         => $year,
            'rate'         => $rate,
            'lines'        => $lines,
            'total_shares' => $shares,
            'total_amount' => $total,
            'member_count' => count($lines),
            'exists'       => (bool) Database::fetchValue(
                "SELECT 1 FROM membership_dividends WHERE vendor_id = ? AND cooperative_id = ? AND year = ?",
                [$this->vendorId, $this->cooperativeId, $year]
            ),
        ];
    }
}
