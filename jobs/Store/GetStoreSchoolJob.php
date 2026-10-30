<?php
// Maklumat sekolah untuk layout store (PELAN 18.12 store/school + notis modul add-on 18.13).
class GetStoreSchoolJob
{
    private $cooperative;

    // $cooperative = baris StoreSchool::require($slug)
    public function __construct(array $cooperative)
    {
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        $coop = $this->cooperative;
        $modules = StoreSchool::modules($coop);
        $hours = json_decode((string) CooperativeSettings::get($coop['vendor_id'], $coop['id'], 'opening_hours', ''), true);

        $data = StoreSchool::publicShape($coop);
        $data['address'] = (string) $coop['address'];
        $data['phone'] = (string) $coop['phone'];
        $data['logo_url'] = Uploader::url($coop['logo_path']);
        $data['default_language'] = $coop['default_language'];
        $data['opening_hours'] = GetCooperativeJob::normaliseHours(is_array($hours) ? $hours : []);
        $data['grades'] = StoreSchool::grades($coop);
        $data['modules'] = $modules;
        $data['payment_methods'] = StoreSchool::paymentMethods($coop);
        $data['app_enabled'] = StoreSchool::appEnabled($coop);
        $data['notices'] = [
            'closure'  => in_array('calendar', $modules, true) ? $this->closure() : null,
            'preorder' => in_array('school_opening', $modules, true) ? $this->preorder() : null,
        ];
        return $data;
    }

    // Koperasi tutup (blocks_orders) yang sedang berlaku atau dalam 14 hari
    private function closure()
    {
        $event = Database::fetchOne(
            "SELECT title, starts_on, ends_on FROM calendar_events
              WHERE vendor_id = ? AND cooperative_id = ? AND blocks_orders = 1
                AND ends_on >= CURDATE() AND starts_on <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
              ORDER BY starts_on LIMIT 1",
            [(int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']]
        );
        if ($event === null) {
            return null;
        }
        return [
            'title'      => $event['title'],
            'starts_on'  => $event['starts_on'],
            'ends_on'    => $event['ends_on'],
            'is_today'   => $event['starts_on'] <= date('Y-m-d'),
            'reopens_on' => date('Y-m-d', strtotime($event['ends_on'] . ' +1 day')),
        ];
    }

    // Musim pre-order yang sedang dibuka
    private function preorder()
    {
        $season = Database::fetchOne(
            "SELECT s.id, s.school_year, s.closes_at, s.pickup_starts_on, s.note,
                    EXISTS (SELECT 1 FROM pickup_slots p WHERE p.vendor_id = s.vendor_id AND p.preorder_season_id = s.id) AS has_slots
               FROM preorder_seasons s
              WHERE s.vendor_id = ? AND s.cooperative_id = ? AND s.opens_at <= NOW() AND s.closes_at >= NOW()
              ORDER BY s.school_year LIMIT 1",
            [(int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']]
        );
        if ($season === null) {
            return null;
        }
        return [
            'school_year'      => (int) $season['school_year'],
            'closes_at'        => IsoDate::format($season['closes_at']),
            'pickup_starts_on' => $season['pickup_starts_on'],
            'note'             => (string) $season['note'],
            'has_slots'        => (bool) $season['has_slots'],
        ];
    }
}
