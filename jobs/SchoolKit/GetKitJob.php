<?php
// Butiran kit + item (KitDetail, PELAN 18.9), atau null jika tiada dalam koperasi vendor sesi.
class GetKitJob
{
    private $vendorId;
    private $cooperativeId;
    private $kitId;

    public function __construct($vendorId, $cooperativeId, $kitId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->kitId = (int) $kitId;
    }

    public function run()
    {
        return SchoolKits::detail($this->vendorId, $this->cooperativeId, $this->kitId);
    }
}
