<?php
// Tarikh DB (DATETIME, zon +08:00) → ISO 8601 untuk API (PELAN 10.3), cth. 2026-10-24T10:15:00+08:00
class IsoDate
{
    public static function format($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = new DateTime((string) $value, new DateTimeZone(Config::get('timezone')));
        return $date->format('Y-m-d\TH:i:sP');
    }
}
