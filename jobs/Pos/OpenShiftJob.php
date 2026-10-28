<?php
// Buka syif POS (vendor/pos/shift-open). Satu syif terbuka setiap juruwang setiap koperasi —
// baris kakitangan dikunci (FOR UPDATE) supaya dua permintaan serentak tidak membuka dua syif.
class OpenShiftJob
{
    const MAX_CASH = 100000000;

    private $vendorId;
    private $cooperative;
    private $cashier;
    private $openingCash;

    public function __construct($vendorId, array $cooperative, array $cashier, $openingCash)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->cashier = $cashier;
        $this->openingCash = $openingCash;
    }

    public function run()
    {
        $cash = filter_var($this->openingCash, FILTER_VALIDATE_INT);
        if ($cash === false || $cash < 0 || $cash > self::MAX_CASH) {
            throw new ValidationException(t('error.check_form'), ['opening_cash' => t('error.required')]);
        }
        $coopId = (int) $this->cooperative['id'];
        $userId = (int) $this->cashier['id'];
        Database::beginTransaction();
        try {
            Database::fetchOne("SELECT id FROM vendor_users WHERE id = ? AND vendor_id = ? FOR UPDATE", [$userId, $this->vendorId]);
            if (PosShifts::openFor($this->vendorId, $coopId, $userId) !== null) {
                throw new ValidationException(t('pos.shift_already_open'));
            }
            $id = Database::insert(
                "INSERT INTO pos_shifts (vendor_id, cooperative_id, vendor_user_id, opened_at, opening_cash, status) VALUES (?, ?, ?, NOW(), ?, 'open')",
                [$this->vendorId, $coopId, $userId, $cash]
            );
            (new WriteActivityLogJob($this->vendorId, $coopId, $userId, 'pos_shift_open', 'pos_shift', $id, Money::format($cash)))->run();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return PosShifts::summary(PosShifts::find($this->vendorId, $coopId, $id));
    }
}
