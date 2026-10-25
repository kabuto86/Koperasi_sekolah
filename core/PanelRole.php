<?php
// Peranan pengguna panel (owner / vendor) & menu yang dibenarkan (PELAN 4).
// Peringkat 1: peranan dari cookie demo `mock_role` (hanya bila use_mock) — lalai peranan tertinggi.
// B2: peranan dari sesi; pelayan juga menyekat halaman & API, bukan menu sahaja.
class PanelRole
{
    const ROLES = [
        'owner'  => ['owner', 'owner_staff'],
        'vendor' => ['vendor_admin', 'coop_manager', 'cashier'],
    ];

    public static function current($panel)
    {
        $roles = self::ROLES[$panel];
        if (Config::get('use_mock')) {
            $role = $_COOKIE['mock_role'] ?? '';
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return $roles[0];
    }

    // Item menu tanpa 'roles' = semua peranan. Seksyen kosong dibuang
    public static function filterMenu(array $sections, $role)
    {
        $result = [];
        foreach ($sections as $section) {
            $section['items'] = array_values(array_filter($section['items'], function ($item) use ($role) {
                return empty($item['roles']) || in_array($role, $item['roles'], true);
            }));
            if ($section['items']) {
                $result[] = $section;
            }
        }

        return $result;
    }
}
