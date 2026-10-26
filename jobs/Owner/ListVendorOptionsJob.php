<?php
// Pilihan vendor ringkas untuk dropdown (pengumuman, tapisan koperasi).
class ListVendorOptionsJob
{
    public function run()
    {
        $rows = Database::fetchAll("SELECT id, code, company_name, status FROM vendors ORDER BY code");
        return array_map(function ($row) {
            return ['id' => (int) $row['id'], 'code' => $row['code'], 'company_name' => $row['company_name'], 'status' => $row['status']];
        }, $rows);
    }
}
