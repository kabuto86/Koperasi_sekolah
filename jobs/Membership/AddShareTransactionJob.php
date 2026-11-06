<?php
// Transaksi syer ahli: buy (beli syer — ahli aktif sahaja) atau withdraw (keluarkan — tidak melebihi baki).
// Baris ahli dikunci (FOR UPDATE) supaya dua pengeluaran serentak tidak melebihi baki. Tarikh tidak boleh pada masa hadapan.
class AddShareTransactionJob
{
    const MAX_AMOUNT = 100000000;   // RM 1,000,000

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
        $v = new Validator($this->input);
        $v->required('type')->inList('type', Membership::TX_TYPES)
          ->required('amount')->integer('amount', 1, self::MAX_AMOUNT)
          ->required('transaction_date')->date('transaction_date')
          ->maxLength('note', 255);
        if (!$v->fails() && $this->input['transaction_date'] > date('Y-m-d')) {
            $v->addError('transaction_date', t('membership.date_future'));
        }
        ValidationException::throwIfFails($v);
        $type = $this->input['type'];
        $amount = (int) $this->input['amount'];

        Database::beginTransaction();
        try {
            $member = Membership::find($this->vendorId, $this->cooperativeId, (int) ($this->input['member_id'] ?? 0), true);
            if ($member === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($type === 'buy' && $member['status'] !== 'active') {
                throw new ValidationException(t('membership.member_inactive'));
            }
            $balance = Membership::balance($this->vendorId, $member['id']);
            if ($type === 'withdraw' && $amount > $balance) {
                throw new ValidationException(t('membership.withdraw_exceeds', ['balance' => Money::format($balance)]),
                    ['amount' => t('membership.withdraw_exceeds', ['balance' => Money::format($balance)])]);
            }
            Database::insert(
                "INSERT INTO membership_share_transactions (vendor_id, cooperative_id, member_id, type, amount, transaction_date, note, vendor_user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$this->vendorId, $this->cooperativeId, (int) $member['id'], $type, $amount, $this->input['transaction_date'],
                    trim((string) ($this->input['note'] ?? '')) ?: null, $this->userId]
            );
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'membership_share_' . $type, 'membership_member',
            (int) $member['id'], $member['member_no'] . ' · ' . Money::format($amount)))->run();
        return (new GetMemberJob($this->vendorId, $this->cooperativeId, (int) $member['id']))->run();
    }
}
