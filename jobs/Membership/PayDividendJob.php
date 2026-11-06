<?php
// Tanda dividen dibayar. payout reinvest → setiap ahli menerima transaksi syer `dividend` (tarikh hari ini) dalam transaksi yang sama.
// Sekali sahaja: baris dividen dikunci & mesti masih `declared`.
class PayDividendJob
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
                throw new ValidationException(t('membership.dividend_already_paid'));
            }
            if ($dividend['payout'] === 'reinvest') {
                Database::execute(
                    "INSERT INTO membership_share_transactions (vendor_id, cooperative_id, member_id, type, amount, transaction_date, note, dividend_id, vendor_user_id)
                     SELECT l.vendor_id, ?, l.member_id, 'dividend', l.amount, CURDATE(), ?, l.dividend_id, ?
                       FROM membership_dividend_lines l WHERE l.vendor_id = ? AND l.dividend_id = ? AND l.amount > 0",
                    [$this->cooperativeId, t('membership.dividend_note', ['year' => $dividend['year']]), $this->userId, $this->vendorId, $this->dividendId]
                );
            }
            Database::execute("UPDATE membership_dividends SET status = 'paid', paid_at = NOW() WHERE id = ? AND vendor_id = ?",
                [$this->dividendId, $this->vendorId]);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'membership_dividend_pay', 'membership_dividend',
            $this->dividendId, $dividend['year'] . ' · ' . Money::format((int) $dividend['total_amount'])))->run();
        return (new GetDividendJob($this->vendorId, $this->cooperativeId, $this->dividendId))->run();
    }
}
