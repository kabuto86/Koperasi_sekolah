<?php
// Padam dividen yang belum dibayar (silap kadar / tahun). Dividen dibayar tidak boleh dipadam.
class DeleteDividendJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $dividendId;

    public function __construct($vendorId, $cooperativeId, $userId, $dividendId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->dividendId = (int) $dividendId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $dividend = Membership::findDividend($this->vendorId, $this->cooperativeId, $this->dividendId, true);
            if ($dividend === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($dividend['status'] !== 'declared') {
                throw new ValidationException(t('membership.dividend_paid_locked'));
            }
            Database::execute("DELETE FROM membership_dividend_lines WHERE vendor_id = ? AND dividend_id = ?", [$this->vendorId, $this->dividendId]);
            Database::execute("DELETE FROM membership_dividends WHERE id = ? AND vendor_id = ?", [$this->dividendId, $this->vendorId]);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'membership_dividend_delete', 'membership_dividend',
            $this->dividendId, (string) $dividend['year']))->run();
        return ['deleted' => true];
    }
}
