<?php
// Halaman vendor modul mobile_app (W4): penggunaan apps ibu bapa untuk koperasi semasa — ibu bapa berdaftar (ada anak aktif di
// koperasi), ibu bapa yang log masuk apps (token aktif), peranti push, push 30 hari ikut peristiwa & status, pautan muat turun
// (tetapan owner — kosong sehingga apps diterbitkan F4) dan pautan store. Tiada data peribadi ibu bapa dipulangkan (kiraan sahaja).
class GetMobileAppStatsJob
{
    const PUSH_EVENTS = ['order_ready', 'order_collected', 'news'];

    private $vendorId;
    private $cooperative;

    public function __construct($vendorId, array $cooperative)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        $coopId = (int) $this->cooperative['id'];
        $parents = "SELECT DISTINCT ch.customer_id FROM children ch WHERE ch.vendor_id = ? AND ch.cooperative_id = ? AND ch.is_active = 1";
        $args = [$this->vendorId, $coopId];
        $push = [];
        foreach (self::PUSH_EVENTS as $event) {
            $push[$event] = ['sent' => 0, 'failed' => 0, 'pending' => 0];
        }
        $rows = Database::fetchAll(
            "SELECT event, status, COUNT(*) AS total FROM notification_queue
              WHERE vendor_id = ? AND cooperative_id = ? AND channel = 'push' AND created_at >= NOW() - INTERVAL 30 DAY
              GROUP BY event, status",
            $args
        );
        foreach ($rows as $row) {
            if (isset($push[$row['event']][$row['status']])) {
                $push[$row['event']][$row['status']] = (int) $row['total'];
            }
        }
        return [
            'school_name'   => $this->cooperative['school_name'],
            'parents'       => (int) Database::fetchValue("SELECT COUNT(*) FROM ($parents) p", $args),
            'app_parents'   => (int) Database::fetchValue(
                "SELECT COUNT(DISTINCT t.customer_id) FROM api_tokens t
                  WHERE t.customer_id IN ($parents) AND t.revoked_at IS NULL AND t.expires_at > NOW()",
                $args
            ),
            'devices'       => (int) Database::fetchValue("SELECT COUNT(*) FROM push_devices d WHERE d.customer_id IN ($parents)", $args),
            'push'          => $push,
            'android_url'   => PlatformSettings::get('app_android_url'),
            'ios_url'       => PlatformSettings::get('app_ios_url'),
            'store_url'     => View::absoluteStoreUrl($this->cooperative['slug']),
        ];
    }
}
