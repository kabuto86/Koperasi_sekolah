<?php
// Pilihan pelan ringkas (id, kod, nama) untuk tapisan senarai vendor — owner_staff juga (tanpa harga).
class ListPlanOptionsJob
{
    public function run()
    {
        $rows = Database::fetchAll("SELECT id, code, name, is_active FROM plans ORDER BY sort_order, id");
        return array_map(function ($row) {
            return ['id' => (int) $row['id'], 'code' => $row['code'], 'name' => $row['name'], 'is_active' => (bool) $row['is_active']];
        }, $rows);
    }
}
