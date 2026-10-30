<?php
// Buang item dari jualan murah (vendor/clearance/item-delete).
class DeleteClearanceItemJob
{
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
        $sale = ClearanceSales::find($this->vendorId, $this->cooperativeId, (int) ($this->input['sale_id'] ?? 0));
        $id = (int) ($this->input['id'] ?? 0);
        if ($sale === null || ClearanceSales::findItem($this->vendorId, $sale['id'], $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute("DELETE FROM clearance_items WHERE id = ? AND vendor_id = ? AND clearance_sale_id = ?", [$id, $this->vendorId, (int) $sale['id']]);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'clearance_save', 'clearance_sale', (int) $sale['id'],
            $sale['name']))->run();
        return ['id' => $id];
    }
}
