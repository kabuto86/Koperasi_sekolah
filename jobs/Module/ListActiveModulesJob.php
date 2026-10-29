<?php
// Kod modul add-on aktif untuk koperasi (menu add-on vendor — PELAN 18.4). Koperasi sudah disahkan oleh pemanggil.
// Sejak B12: ModuleAccess (status active, belum tamat, modul aktif katalog); 'expired' ditetapkan oleh pusingan langganan.
class ListActiveModulesJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        return ['cooperative_id' => $this->cooperativeId, 'modules' => ModuleAccess::codes($this->vendorId, $this->cooperativeId)];
    }
}
