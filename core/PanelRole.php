<?php
// Peranan pengguna panel (owner / vendor) & menu yang dibenarkan (PELAN 4).
// Sejak B2: peranan dari sesi (OwnerAuth / VendorAuth). Halaman disekat oleh requirePage(),
// API oleh requireRole() — menu yang ditapis hanya kemudahan paparan.
class PanelRole
{
    const ROLES = [
        'owner'  => ['owner', 'owner_staff'],
        'vendor' => ['vendor_admin', 'coop_manager', 'cashier'],
    ];

    // Halaman modul add-on ('module.*') — juruwang tidak mengurus modul
    const ADDON_ROLES = ['vendor_admin', 'coop_manager'];

    // Vendor digantung: hanya halaman langganan & invois (activeMenu 'menu.subscription')
    const BILLING_MENUS = ['menu.subscription'];

    public static function current($panel)
    {
        return $panel === 'owner' ? OwnerAuth::role() : VendorAuth::role();
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

    // Halaman dengan $activeMenu ini boleh dibuka oleh peranan ini?
    // Menu tiada dalam senarai (cth. profil, '') = semua peranan panel itu
    public static function canOpen($panel, $role, $activeMenu)
    {
        if (strpos((string) $activeMenu, 'module.') === 0) {
            return in_array($role, self::ADDON_ROLES, true);
        }
        foreach (self::menu($panel) as $section) {
            foreach ($section['items'] as $item) {
                if ($item['key'] === $activeMenu) {
                    return empty($item['roles']) || in_array($role, $item['roles'], true);
                }
            }
        }
        return in_array($role, self::ROLES[$panel], true);
    }

    // URL halaman pertama yang dibenarkan untuk peranan ini
    public static function firstPage($panel, $role)
    {
        $sections = self::filterMenu(self::menu($panel), $role);
        return $sections ? $sections[0]['items'][0]['url'] : $panel . '/login.php';
    }

    public static function isBillingMenu($activeMenu)
    {
        return in_array($activeMenu, self::BILLING_MENUS, true);
    }

    private static function menu($panel)
    {
        return require APP_ROOT . '/' . $panel . '/_layout/menu.php';
    }
}
