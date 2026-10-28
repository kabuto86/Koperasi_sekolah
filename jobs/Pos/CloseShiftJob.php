<?php
// Tutup syif POS (vendor/pos/shift-close): syif sendiri sahaja, ditolak jika masih ada bayaran QR `pending_payment`.
// Jangkaan tunai dibekukan (PosShifts::summary), variance = tunai dikira − jangkaan. Endpoint melog keluar juruwang.
class CloseShiftJob
{
    private $vendorId;
    private $cooperative;
    private $cashier;
    private $input;

    public function __construct($vendorId, array $cooperative, array $cashier, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->cashier = $cashier;
        $this->input = $input;
    }

    public function run()
    {
        $closing = filter_var($this->input['closing_cash'] ?? null, FILTER_VALIDATE_INT);
        if ($closing === false || $closing < 0 || $closing > OpenShiftJob::MAX_CASH) {
            throw new ValidationException(t('error.check_form'), ['closing_cash' => t('error.required')]);
        }
        $note = trim((string) ($this->input['note'] ?? ''));
        if (mb_strlen($note, 'UTF-8') > 255) {
            throw new ValidationException(t('error.check_form'), ['note' => t('error.max_length', ['max' => 255])]);
        }
        $coopId = (int) $this->cooperative['id'];
        Database::beginTransaction();
        try {
            $shift = PosShifts::require($this->vendorId, $coopId, (int) ($this->input['shift_id'] ?? 0), true);
            if ((int) $shift['vendor_user_id'] !== (int) $this->cashier['id'] || $shift['status'] !== 'open') {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            $summary = PosShifts::summary($shift);
            if ($summary['pending_count'] > 0) {
                throw new ValidationException(t('pos.shift_pending_payments', ['n' => $summary['pending_count']]));
            }
            $variance = $closing - $summary['expected_cash'];
            Database::execute(
                "UPDATE pos_shifts SET status = 'closed', closed_at = NOW(), closing_cash = ?, expected_cash = ?, variance = ?, note = ?
                  WHERE id = ? AND vendor_id = ?",
                [$closing, $summary['expected_cash'], $variance, $note === '' ? null : $note, (int) $shift['id'], $this->vendorId]
            );
            $detail = Money::format($closing) . ' · ' . t('pos.variance') . ' ' . Money::format($variance);
            (new WriteActivityLogJob($this->vendorId, $coopId, (int) $this->cashier['id'], 'pos_shift_close', 'pos_shift', (int) $shift['id'], $detail))->run();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return PosShifts::summary(PosShifts::find($this->vendorId, $coopId, $shift['id']));
    }
}
