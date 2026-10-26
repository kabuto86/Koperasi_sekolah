<?php
// Buang anak { id } — dinyahaktif (pesanan lama kekal dengan salinan nama), baris troli anak itu dibuang.
class RemoveChildJob
{
    private $customerId;
    private $childId;

    public function __construct($customerId, $childId)
    {
        $this->customerId = (int) $customerId;
        $this->childId = (int) $childId;
    }

    public function run()
    {
        Database::beginTransaction();
        if (Children::find($this->customerId, $this->childId, null, true) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute("UPDATE children SET is_active = 0 WHERE customer_id = ? AND id = ?", [$this->customerId, $this->childId]);
        Children::removeFromCarts($this->customerId, $this->childId);
        Database::commit();
        return ['id' => $this->childId];
    }
}
