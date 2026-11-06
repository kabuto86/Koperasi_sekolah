<?php
// Satu dividen + senarai ahli (salinan semasa diisytihar). Dividen koperasi / vendor lain → 404.
class GetDividendJob
{
    private $vendorId;
    private $cooperativeId;
    private $dividendId;

    public function __construct($vendorId, $cooperativeId, $dividendId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->dividendId = (int) $dividendId;
    }

    public function run()
    {
        $dividend = Membership::findDividend($this->vendorId, $this->cooperativeId, $this->dividendId);
        if ($dividend === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $lines = Database::fetchAll(
            "SELECT l.member_id, l.share_balance, l.amount, m.member_no, m.full_name
               FROM membership_dividend_lines l
               JOIN membership_members m ON m.id = l.member_id AND m.vendor_id = l.vendor_id
              WHERE l.vendor_id = ? AND l.dividend_id = ?
              ORDER BY m.member_no",
            [$this->vendorId, $this->dividendId]
        );
        $result = Membership::shapeDividend($dividend);
        $result['lines'] = array_map(function ($row) {
            return [
                'member_id'     => (int) $row['member_id'],
                'member_no'     => $row['member_no'],
                'full_name'     => $row['full_name'],
                'share_balance' => (int) $row['share_balance'],
                'amount'        => (int) $row['amount'],
            ];
        }, $lines);
        return $result;
    }
}
