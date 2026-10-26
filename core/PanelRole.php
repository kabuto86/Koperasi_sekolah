<?php
// Menu panel (owner / vendor) yang dibenarkan ikut kebenaran pengguna (PELAN 4 & 4.1).
// Sejak B3b: item menu ada 'permission' (kod dalam core/Permission.php; tiada = semua pengguna panel).
// Halaman disekat oleh requirePage(), API oleh requirePermission() — menu yang ditapis hanya kemudahan paparan.
class PanelRole
{
    // Halaman modul add-on ('module.*')
    const ADDON_PERMISSION = 'module.use';

    // Vendor digantung: hanya halaman langganan & invois (activeMenu 'menu.subscription')
    const BILLING_MENUS = ['menu.subscription'];

    public static function current($panel)
    {
        return $panel === 'owner' ? OwnerAuth::role() : VendorAuth::role();
    }

    public static function permissions($panel)
    {
        return $panel === 'owner' ? OwnerAuth::permissions() : VendorAuth::permissions();
    }

    // Item tanpa 'permission' = semua. Seksyen kosong dibuang
    public static function filterMenu(array $sections, array $permissions)
    {
        $result = [];
        foreach ($sections as $section) {
            $section['items'] = array_values(array_filter($section['items'], function ($item) use ($permissions) {
                return self::itemAllowed($item, $permissions);
            }));
            if ($section['items']) {
                $result[] = $section;
            }
        }

        return $result;
    }

    // Halaman dengan $activeMenu ini boleh dibuka? Menu tiada dalam senarai (cth. profil, '') = semua pengguna panel
    public static function canOpen($panel, array $permissions, $activeMenu)
    {
        if (strpos((string) $activeMenu, 'module.') === 0) {
            return in_array(self::ADDON_PERMISSION, $permissions, true);
        }
        foreach (self::menu($panel) as $section) {
            foreach ($section['items'] as $item) {
                if ($item['key'] === $activeMenu) {
                    return self::itemAllowed($item, $permissions);
                }
            }
        }
        return true;
    }

    // URL halaman pertama yang dibenarkan. Tiada menu langsung: owner → profil; vendor → null (tiada akses)
    public static function firstPage($panel, array $permissions)
    {
        $sections = self::filterMenu(self::menu($panel), $permissions);
        if ($sections) {
            return $sections[0]['items'][0]['url'];
        }
        return $panel === 'owner' ? 'owner/profile.php' : null;
    }

    public static function isBillingMenu($activeMenu)
    {
        return in_array($activeMenu, self::BILLING_MENUS, true);
    }

    private static function itemAllowed(array $item, array $permissions)
    {
        return empty($item['permission']) || in_array($item['permission'], $permissions, true);
    }

    private static function menu($panel)
    {
        return require APP_ROOT . '/' . $panel . '/_layout/menu.php';
    }
}
