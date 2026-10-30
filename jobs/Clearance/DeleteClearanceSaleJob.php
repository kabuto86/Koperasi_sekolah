<?php
// Padam jualan murah bersama itemnya (vendor/clearance/delete). Pesanan lama menyimpan harga snapshot — tidak terjejas.
class DeleteClearanceSaleJob
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
        $sale = ClearanceSales::find($this->vendorId, $this->cooperativeId, $this->id);
        if ($sale === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::beginTransaction();
        try {
            Database::execute("DELETE FROM clearance_items WHERE vendor_id = ? AND clearance_sale_id = ?", [$this->vendorId, $this->id]);
            Database::execute("DELETE FROM clearance_sales WHERE id = ? AND vendor_id = ? AND cooperative_id = ?", [$this->id, $this->vendorId, $this->cooperativeId]);
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'clearance_delete', 'clearance_sale', $this->id, $sale['name']))->run();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        return ['id' => $this->id];
    }
}
