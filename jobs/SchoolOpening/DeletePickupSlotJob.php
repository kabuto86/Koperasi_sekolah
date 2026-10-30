<?php
// Padam slot ambil (vendor/school-opening/slot-delete): dikunci FOR UPDATE; ditolak jika bertempah atau pernah dipilih dalam pesanan.
class DeletePickupSlotJob
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
            $slot = Database::fetchOne(
                "SELECT p.id, p.booked, s.id AS season_id, s.school_year FROM pickup_slots p
                   JOIN preorder_seasons s ON s.id = p.preorder_season_id AND s.vendor_id = p.vendor_id
                  WHERE p.id = ? AND p.vendor_id = ? AND p.cooperative_id = ? FOR UPDATE",
                [$this->id, $this->vendorId, $this->cooperativeId]
            );
            if ($slot === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ((int) $slot['booked'] > 0) {
                throw new ValidationException(t('opening.slot_has_bookings', ['n' => (int) $slot['booked']]));
            }
            if (PreorderSeasons::slotUsed($this->vendorId, [$this->id])) {
                throw new ValidationException(t('opening.slot_in_orders'));
            }
            Database::execute("DELETE FROM pickup_slots WHERE id = ? AND vendor_id = ?", [$this->id, $this->vendorId]);
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'season_save', 'preorder_season', (int) $slot['season_id'],
                (string) $slot['school_year']))->run();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        return ['id' => $this->id];
    }
}
