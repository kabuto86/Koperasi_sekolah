<?php
// Kod modul add-on aktif untuk koperasi (menu add-on vendor — PELAN 18.4). Koperasi sudah disahkan oleh pemanggil.
// Status 'expired' ditetapkan oleh cron langganan (B12); ModuleAccess penuh dalam B13.
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
        $rows = Database::fetchAll(
            "SELECT m.code FROM cooperative_modules cm JOIN modules m ON m.id = cm.module_id
              WHERE cm.vendor_id = ? AND cm.cooperative_id = ? AND cm.status = 'active'
              ORDER BY m.sort_order",
            [$this->vendorId, $this->cooperativeId]
        );
        return ['cooperative_id' => $this->cooperativeId, 'modules' => array_column($rows, 'code')];
    }
}
