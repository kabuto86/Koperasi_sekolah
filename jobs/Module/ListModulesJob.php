<?php
// Katalog modul add-on (PELAN 18.7 Module). Nama & keterangan 4 bahasa dari translations / lang/*.php.
class ListModulesJob
{
    const SELECT = "SELECT m.*, (SELECT COUNT(*) FROM cooperative_modules cm
                     WHERE cm.module_id = m.id AND cm.status = 'active') AS cooperative_count
                    FROM modules m";

    public function run()
    {
        $rows = Database::fetchAll(self::SELECT . " ORDER BY m.sort_order, m.id");
        return self::shapeAll($rows);
    }

    public static function find($id)
    {
        $rows = Database::fetchAll(self::SELECT . " WHERE m.id = ?", [(int) $id]);
        return count($rows) ? self::shapeAll($rows)[0] : null;
    }

    private static function shapeAll(array $rows)
    {
        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $row['name_key'];
            $keys[] = $row['description_key'];
        }
        $texts = TranslationValues::many($keys);
        return array_map(function ($row) use ($texts) {
            return [
                'id'                => (int) $row['id'],
                'code'              => $row['code'],
                'names'             => $texts[$row['name_key']],
                'descriptions'      => $texts[$row['description_key']],
                'price_monthly'     => (int) $row['price_monthly'],
                'price_yearly'      => (int) $row['price_yearly'],
                'is_core'           => (bool) $row['is_core'],
                'is_active'         => (bool) $row['is_active'],
                'sort_order'        => (int) $row['sort_order'],
                'cooperative_count' => (int) $row['cooperative_count'],
            ];
        }, $rows);
    }
}
