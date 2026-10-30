<?php
// Acara kalendar yang bertindih julat [from, to] (vendor/calendar/list) + acara yang menutup tempahan hari ini.
class ListCalendarEventsJob
{
    private $vendorId;
    private $cooperativeId;
    private $params;

    public function __construct($vendorId, $cooperativeId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->params = $params;
    }

    public function run()
    {
        $from = $this->day('from', '1000-01-01');
        $to = $this->day('to', '9999-12-31');
        $rows = Database::fetchAll(
            "SELECT * FROM calendar_events WHERE vendor_id = ? AND cooperative_id = ? AND ends_on >= ? AND starts_on <= ?
              ORDER BY starts_on, id LIMIT 500",
            [$this->vendorId, $this->cooperativeId, $from, $to]
        );
        $blocked = Database::fetchOne(
            "SELECT * FROM calendar_events WHERE vendor_id = ? AND cooperative_id = ? AND blocks_orders = 1
                AND starts_on <= CURDATE() AND ends_on >= CURDATE() ORDER BY starts_on, id LIMIT 1",
            [$this->vendorId, $this->cooperativeId]
        );
        return [
            'items'         => array_map([CalendarEvents::class, 'shape'], $rows),
            'today'         => date('Y-m-d'),
            'today_blocked' => $blocked === null ? null : CalendarEvents::shape($blocked),
        ];
    }

    // Tarikh tidak sah → nilai lalai (julat penuh)
    private function day($name, $default)
    {
        $value = (string) ($this->params[$name] ?? '');
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : $default;
    }
}
