<?php
// Pilihan template untuk borang koperasi (PELAN 18.8). Template premium dilanggan PER KOPERASI (B12):
// subscribed = percuma ATAU item langganan template koperasi ini yang masih berjalan. Koperasi baharu (id 0) = percuma sahaja.
class ListTemplateOptionsJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId = 0)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT t.id, t.code, t.name, t.is_free, t.price_monthly, t.price_yearly,
                    EXISTS (SELECT 1 FROM subscription_items si
                             WHERE si.vendor_id = ? AND si.cooperative_id = ? AND si.item_type = 'template' AND si.ref_id = t.id
                               AND (si.ended_at IS NULL OR si.ended_at >= CURDATE())) AS has_subscription
               FROM templates t
              WHERE t.is_active = 1
              ORDER BY t.id",
            [$this->vendorId, $this->cooperativeId]
        );
        return array_map(function ($row) {
            return [
                'id'            => (int) $row['id'],
                'code'          => $row['code'],
                'name'          => $row['name'],
                'is_free'       => (bool) $row['is_free'],
                'price_monthly' => (int) $row['price_monthly'],
                'price_yearly'  => (int) $row['price_yearly'],
                'subscribed'    => (bool) $row['is_free'] || (bool) $row['has_subscription'],
            ];
        }, $rows);
    }

    // Template boleh dipakai oleh koperasi ini?
    public static function isUsable($vendorId, $templateId, $cooperativeId)
    {
        foreach ((new self($vendorId, $cooperativeId))->run() as $template) {
            if ($template['id'] === (int) $templateId) {
                return $template['subscribed'];
            }
        }
        return false;
    }
}
