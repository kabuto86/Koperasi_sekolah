<?php
// Template frontend store (PELAN 18.7 Template) + bilangan koperasi yang menggunakannya.
class ListTemplatesJob
{
    const SELECT = "SELECT t.*, (SELECT COUNT(*) FROM cooperatives c WHERE c.template_id = t.id AND c.status <> 'closed') AS cooperative_count
                    FROM templates t";

    public function run()
    {
        return array_map([self::class, 'shape'], Database::fetchAll(self::SELECT . " ORDER BY t.id"));
    }

    public static function find($id)
    {
        $row = Database::fetchOne(self::SELECT . " WHERE t.id = ?", [(int) $id]);
        return $row === null ? null : self::shape($row);
    }

    public static function shape($row)
    {
        return [
            'id'                => (int) $row['id'],
            'code'              => $row['code'],
            'name'              => $row['name'],
            'folder'            => $row['folder'],
            'preview_image'     => $row['preview_image'] ? View::url($row['preview_image']) : null,
            'description'       => (string) $row['description'],
            'price_monthly'     => (int) $row['price_monthly'],
            'price_yearly'      => (int) $row['price_yearly'],
            'is_free'           => (bool) $row['is_free'],
            'is_active'         => (bool) $row['is_active'],
            'cooperative_count' => (int) $row['cooperative_count'],
        ];
    }
}
