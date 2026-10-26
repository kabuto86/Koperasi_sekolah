<?php
// Bacaan jadual platform_settings (kunci → nilai teks), dicache untuk satu permintaan.
class PlatformSettings
{
    private static $values = null;

    public static function all()
    {
        if (self::$values === null) {
            self::$values = [];
            foreach (Database::fetchAll("SELECT setting_key, setting_value FROM platform_settings") as $row) {
                self::$values[$row['setting_key']] = (string) $row['setting_value'];
            }
        }
        return self::$values;
    }

    public static function get($key, $default = '')
    {
        $values = self::all();
        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function set($key, $value, $actorId)
    {
        Database::execute(
            "INSERT INTO platform_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)",
            [$key, (string) $value, $actorId]
        );
        self::$values = null;
    }
}
