<?php
// Butiran satu koperasi vendor sesi + waktu operasi (PELAN 18.8). null = tiada / milik vendor lain.
class GetCooperativeJob
{
    const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $row = Database::fetchOne(
            "SELECT id, vendor_id, slug, school_name, school_code, school_type, state, district, address, phone, logo_path,
                    template_id, default_language, accept_cash, accept_online, status, created_at
               FROM cooperatives WHERE vendor_id = ? AND id = ?",
            [$this->vendorId, $this->cooperativeId]
        );
        if ($row === null) {
            return null;
        }
        $data = ListCooperativesJob::shape($row);
        unset($data['vendor_id']);
        $hours = json_decode((string) CooperativeSettings::get($this->vendorId, $this->cooperativeId, 'opening_hours', ''), true);
        $data['opening_hours'] = self::normaliseHours(is_array($hours) ? $hours : []);
        return $data;
    }

    // 7 hari sentiasa ada: { open, from, to }
    public static function normaliseHours(array $hours)
    {
        $result = [];
        foreach (self::DAYS as $day) {
            $item = is_array($hours[$day] ?? null) ? $hours[$day] : [];
            $open = !empty($item['open']);
            $result[$day] = [
                'open' => $open,
                'from' => $open ? (string) ($item['from'] ?? '') : '',
                'to'   => $open ? (string) ($item['to'] ?? '') : '',
            ];
        }
        return $result;
    }
}
