<?php
// Isytihar dividen tahunan: kiraan dibuat semula di pelayan (DividendCalculator) dan disimpan sebagai salinan (lines) —
// transaksi syer selepas ini tidak mengubah dividen yang sudah diisytihar. Satu dividen setiap tahun setiap koperasi.
// payout: cash (dibayar tunai / pindahan) atau reinvest (ditambah sebagai syer bila ditanda dibayar).
class DeclareDividendJob
{
    const PAYOUTS = ['cash', 'reinvest'];

    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        list($year, $rate) = DividendCalculator::validate($this->input);
        $payout = (string) ($this->input['payout'] ?? 'cash');
        if (!in_array($payout, self::PAYOUTS, true)) {
            throw new ValidationException(t('error.invalid'), ['payout' => t('error.invalid')]);
        }
        Database::beginTransaction();
        try {
            Membership::lockCooperative($this->vendorId, $this->cooperativeId);
            if (Database::fetchValue("SELECT 1 FROM membership_dividends WHERE vendor_id = ? AND cooperative_id = ? AND year = ?",
                [$this->vendorId, $this->cooperativeId, $year])) {
                throw new ValidationException(t('membership.dividend_exists', ['year' => $year]), ['year' => t('membership.dividend_exists', ['year' => $year])]);
            }
            list($lines, $shares, $total) = DividendCalculator::lines($this->vendorId, $this->cooperativeId, $year, $rate);
            if (!$lines) {
                throw new ValidationException(t('membership.dividend_no_members', ['year' => $year]));
            }
            $id = Database::insert(
                "INSERT INTO membership_dividends (vendor_id, cooperative_id, year, rate, total_shares, total_amount, member_count, payout, declared_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$this->vendorId, $this->cooperativeId, $year, $rate, $shares, $total, count($lines), $payout, $this->userId]
            );
            foreach ($lines as $line) {
                Database::insert(
                    "INSERT INTO membership_dividend_lines (vendor_id, dividend_id, member_id, share_balance, amount) VALUES (?, ?, ?, ?, ?)",
                    [$this->vendorId, $id, $line['member_id'], $line['share_balance'], $line['amount']]
                );
            }
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'membership_dividend_declare', 'membership_dividend', $id,
            $year . ' · ' . $rate . '% · ' . Money::format($total)))->run();
        return (new GetDividendJob($this->vendorId, $this->cooperativeId, $id))->run();
    }
}
