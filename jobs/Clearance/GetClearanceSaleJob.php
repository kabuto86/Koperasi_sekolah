<?php
// Butiran jualan murah + item (vendor/clearance/get).
class GetClearanceSaleJob
{
    private $vendorId;
    private $cooperativeId;
    private $id;

    public function __construct($vendorId, $cooperativeId, $id)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->id = (int) $id;
    }

    public function run()
    {
        $sale = ClearanceSales::find($this->vendorId, $this->cooperativeId, $this->id);
        if ($sale === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = ClearanceSales::shape($sale);
        $data['items'] = ClearanceSales::items($this->vendorId, $this->cooperativeId, $this->id);
        return $data;
    }
}
