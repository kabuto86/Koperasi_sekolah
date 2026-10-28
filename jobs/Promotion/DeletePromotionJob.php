<?php
// Padam promosi (vendor/promotions/delete). Promosi yang pernah digunakan (used_count > 0 atau dirujuk pesanan)
// tidak boleh dipadam — rekod pesanan merujuknya; nyahaktifkan sahaja.
class DeletePromotionJob
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
        $promotion = Promotions::find($this->vendorId, $this->cooperativeId, $this->id);
        if ($promotion === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $referenced = Database::fetchValue("SELECT 1 FROM orders WHERE vendor_id = ? AND promotion_id = ? LIMIT 1", [$this->vendorId, $this->id]);
        if ($promotion['used_count'] > 0 || $referenced) {
            throw new ValidationException(t('promo.delete_used'));
        }
        Database::execute("DELETE FROM promotions WHERE id = ? AND vendor_id = ? AND cooperative_id = ?", [$this->id, $this->vendorId, $this->cooperativeId]);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'promotion_delete', 'promotion', $this->id,
            $promotion['code'] ?? $promotion['name']))->run();
        return ['id' => $this->id];
    }
}
