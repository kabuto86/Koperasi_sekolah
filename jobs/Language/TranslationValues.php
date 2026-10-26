<?php
// Nilai terjemahan 4 bahasa untuk satu kunci (nama modul, nama darjah, keterangan modul).
// Nilai owner dalam jadual translations mengatasi lang/*.php; tiada dalam kedua-duanya → ''.
class TranslationValues
{
    // [ kunci => [ ms => .., en => .., zh => .., ta => .. ] ] untuk banyak kunci sekali gus
    public static function many(array $keys)
    {
        $result = [];
        if (count($keys) === 0) {
            return $result;
        }
        $saved = [];
        $rows = Database::fetchAll(
            "SELECT translation_key, language_code, value FROM translations
              WHERE translation_key IN (" . implode(',', array_fill(0, count($keys), '?')) . ")",
            array_values($keys)
        );
        foreach ($rows as $row) {
            $saved[$row['translation_key']][$row['language_code']] = $row['value'];
        }
        foreach ($keys as $key) {
            foreach (Config::get('languages') as $code) {
                $result[$key][$code] = $saved[$key][$code] ?? (string) Lang::fileValue($code, $key);
            }
        }
        return $result;
    }

    public static function get($key)
    {
        return self::many([$key])[$key];
    }

    // Simpan nilai yang diisi; nilai kosong = buang rekod (kembali ke lang/*.php)
    public static function save($key, $values)
    {
        $values = is_array($values) ? $values : [];
        foreach (Config::get('languages') as $code) {
            $value = trim((string) ($values[$code] ?? ''));
            if ($value === '') {
                Database::execute("DELETE FROM translations WHERE language_code = ? AND translation_key = ?", [$code, $key]);
                continue;
            }
            Database::execute(
                "INSERT INTO translations (language_code, translation_key, value) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value)",
                [$code, $key, $value]
            );
        }
    }
}
