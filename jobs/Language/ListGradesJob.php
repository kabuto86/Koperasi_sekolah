<?php
// Senarai master darjah / tingkatan (PELAN 18.7 Grade) dengan nama 4 bahasa.
class ListGradesJob
{
    public function run()
    {
        return self::shapeAll(Database::fetchAll("SELECT code, name_key, level_type, sort_order FROM grade_levels ORDER BY sort_order, code"));
    }

    public static function find($code)
    {
        $rows = Database::fetchAll("SELECT code, name_key, level_type, sort_order FROM grade_levels WHERE code = ?", [(string) $code]);
        return count($rows) ? self::shapeAll($rows)[0] : null;
    }

    private static function shapeAll(array $rows)
    {
        $names = TranslationValues::many(array_column($rows, 'name_key'));
        return array_map(function ($row) use ($names) {
            return [
                'code'       => $row['code'],
                'level_type' => $row['level_type'],
                'sort_order' => (int) $row['sort_order'],
                'names'      => $names[$row['name_key']],
            ];
        }, $rows);
    }
}
