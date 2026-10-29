<?php
// Kedai modul add-on & template untuk koperasi semasa (PELAN 18.10 vendor/addons/catalog). Harga ikut kitaran langganan vendor.
class GetAddonCatalogJob
{
    private $vendorId;
    private $cooperative;

    // $cooperative = baris VendorAuth::requireCooperative()
    public function __construct($vendorId, array $cooperative)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        $subscription = AddonItems::requireSubscription($this->vendorId);
        $cycle = $subscription['billing_cycle'];
        return [
            'billing_cycle'      => $cycle,
            'current_period_end' => $subscription['current_period_end'],
            'modules'            => $this->modules($cycle),
            'templates'          => $this->templates($cycle),
        ];
    }

    private function modules($cycle)
    {
        $rows = Database::fetchAll(
            "SELECT m.code, m.price_monthly, m.price_yearly, m.is_active, cm.status AS sub_status, cm.started_at, cm.ends_at,
                    cm.cancel_at_period_end
               FROM modules m
               LEFT JOIN cooperative_modules cm ON cm.module_id = m.id AND cm.vendor_id = ? AND cm.cooperative_id = ?
              ORDER BY m.sort_order, m.id",
            [$this->vendorId, (int) $this->cooperative['id']]
        );
        $active = ModuleAccess::codes($this->vendorId, $this->cooperative['id']);
        return array_map(function ($row) use ($cycle, $active) {
            $owned = in_array($row['code'], $active, true);
            if ((int) $row['is_active'] !== 1) {
                $status = 'unavailable';
            } elseif ($owned) {
                $status = (int) $row['cancel_at_period_end'] === 1 ? 'cancelling' : 'active';
            } else {
                $status = 'available';
            }
            return [
                'code'          => $row['code'],
                'price_monthly' => (int) $row['price_monthly'],
                'price_yearly'  => (int) $row['price_yearly'],
                'price'         => Subscriptions::cyclePrice($row, $cycle),
                'status'        => $status,
                'started_at'    => $owned ? $row['started_at'] : null,
            ];
        }, $rows);
    }

    private function templates($cycle)
    {
        $rows = Database::fetchAll("SELECT * FROM templates WHERE is_active = 1 ORDER BY id");
        return array_map(function ($row) use ($cycle) {
            $free = (int) $row['is_free'] === 1;
            return [
                'id'         => (int) $row['id'],
                'code'       => $row['code'],
                'name'       => $row['name'],
                'is_free'    => $free,
                'price'      => $free ? 0 : Subscriptions::cyclePrice($row, $cycle),
                'subscribed' => $free || AddonItems::templateSubscribed($this->vendorId, $this->cooperative['id'], $row['id']),
                'in_use'     => (int) $this->cooperative['template_id'] === (int) $row['id'],
            ];
        }, $rows);
    }
}
