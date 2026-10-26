<?php
// Padam kit draf { id }. Kit diterbitkan → tarik balik dahulu. Kit yang sudah ada dalam pesanan tidak dipadam (sejarah).
// Baris troli yang merujuk kit ini (tinggal dari masa ia diterbitkan) dibuang bersama.
class DeleteKitJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;
    private $kitId;

    public function __construct($vendorId, $cooperativeId, $actorId, $kitId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
        $this->kitId = (int) $kitId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $kit = SchoolKits::find($this->vendorId, $this->cooperativeId, $this->kitId, true);
            if ($kit === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($kit['is_published']) {
                throw new ValidationException(t('kit.delete_published'));
            }
            if (Database::fetchValue("SELECT 1 FROM order_items WHERE vendor_id = ? AND school_kit_id = ? LIMIT 1", [$this->vendorId, $this->kitId])) {
                throw new ValidationException(t('kit.delete_has_orders'));
            }
            // cart_items tiada vendor_id — kit sudah disahkan milik vendor sesi di atas
            Database::execute("DELETE FROM cart_items WHERE school_kit_id = ?", [$this->kitId]);
            Database::execute("DELETE FROM school_kit_items WHERE vendor_id = ? AND school_kit_id = ?", [$this->vendorId, $this->kitId]);
            Database::execute(
                "DELETE FROM school_kits WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                [$this->vendorId, $this->cooperativeId, $this->kitId]
            );
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'kit_delete', 'school_kit', $this->kitId,
            $kit['grade_level_code'] . ' ' . $kit['school_year']))->run();
        return ['id' => $this->kitId];
    }
}
