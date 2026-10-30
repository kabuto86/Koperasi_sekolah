<?php
// Padam musim pre-order bersama slotnya (vendor/school-opening/season-delete). Ditolak jika mana-mana slot bertempah
// atau pernah dipilih dalam pesanan. Slot dikunci FOR UPDATE — checkout serentak tidak boleh menempah slot yang sedang dipadam.
class DeletePreorderSeasonJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $id;

    public function __construct($vendorId, $cooperativeId, $userId, $id)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->id = (int) $id;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $season = Database::fetchOne(
                "SELECT id, school_year FROM preorder_seasons WHERE id = ? AND vendor_id = ? AND cooperative_id = ? FOR UPDATE",
                [$this->id, $this->vendorId, $this->cooperativeId]
            );
            if ($season === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            $slots = PreorderSeasons::slots($this->vendorId, $this->id, true);
            $booked = array_sum(array_column($slots, 'booked'));
            if ($booked > 0 || PreorderSeasons::slotUsed($this->vendorId, array_column($slots, 'id'))) {
                throw new ValidationException(t('opening.season_has_bookings'));
            }
            Database::execute("DELETE FROM pickup_slots WHERE vendor_id = ? AND preorder_season_id = ?", [$this->vendorId, $this->id]);
            Database::execute("DELETE FROM preorder_seasons WHERE id = ? AND vendor_id = ? AND cooperative_id = ?", [$this->id, $this->vendorId, $this->cooperativeId]);
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'season_delete', 'preorder_season', $this->id,
                (string) $season['school_year']))->run();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        return ['id' => $this->id];
    }
}
