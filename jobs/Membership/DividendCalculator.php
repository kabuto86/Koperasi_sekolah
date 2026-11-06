<?php
// Kiraan dividen tahunan (W4): untuk setiap ahli yang mempunyai baki syer > 0 pada 31 Disember tahun itu,
// dividen = baki × kadar % (dibundarkan ke sen terdekat). Dikira di pelayan sahaja — nilai dari pelayar tidak dipercayai.
class DividendCalculator
{
    const MIN_YEAR = 2000;

    // [tahun, kadar] yang disahkan; ralat → ValidationException
    public static function validate(array $input)
    {
        $v = new Validator($input);
        $year = filter_var($input['year'] ?? null, FILTER_VALIDATE_INT);
        $rate = $input['rate'] ?? null;
        if ($year === false || $year < self::MIN_YEAR || $year > (int) date('Y')) {
            $v->addError('year', t('membership.year_invalid'));
        }
        if (!is_numeric($rate) || !preg_match('/^\d{1,3}(\.\d{1,2})?$/', (string) $rate) || (float) $rate <= 0 || (float) $rate > 100) {
            $v->addError('rate', t('membership.rate_invalid'));
        }
        ValidationException::throwIfFails($v);
        return [(int) $year, round((float) $rate, 2)];
    }

    // [baris { member_id, member_no, full_name, share_balance, amount }, jumlah syer, jumlah dividen]
    public static function lines($vendorId, $cooperativeId, $year, $rate)
    {
        $yearEnd = $year . '-12-31';
        $rows = Database::fetchAll(
            "SELECT m.id AS member_id, m.member_no, m.full_name, " . Membership::BALANCE_SQL . " AS share_balance
               FROM membership_members m
               JOIN membership_share_transactions t ON t.member_id = m.id AND t.vendor_id = m.vendor_id AND t.transaction_date <= ?
              WHERE m.vendor_id = ? AND m.cooperative_id = ? AND m.joined_on <= ?
              GROUP BY m.id, m.member_no, m.full_name
             HAVING share_balance > 0
              ORDER BY m.member_no",
            [$yearEnd, (int) $vendorId, (int) $cooperativeId, $yearEnd]
        );
        $lines = [];
        $shares = 0;
        $total = 0;
        foreach ($rows as $row) {
            $balance = (int) $row['share_balance'];
            $amount = (int) round($balance * $rate / 100);
            $lines[] = [
                'member_id'     => (int) $row['member_id'],
                'member_no'     => $row['member_no'],
                'full_name'     => $row['full_name'],
                'share_balance' => $balance,
                'amount'        => $amount,
            ];
            $shares += $balance;
            $total += $amount;
        }
        return [$lines, $shares, $total];
    }
}
