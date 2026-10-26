<?php
// Katalog kebenaran panel owner & vendor (PELAN 4.1). Peranan = lalai; pentadbir boleh tambah / tarik
// kebenaran seorang kakitangan (pengecualian dalam platform_user_permissions / vendor_user_permissions).
// Tiada SQL di sini — pengecualian dibaca oleh LoadUserPermissionsJob.
class Permission
{
    // panel => [ kumpulan => [ kod, ... ] ] — susunan ini juga susunan paparan modal kebenaran
    const CATALOG = [
        'vendor' => [
            'main'    => ['dashboard.view', 'cooperative.view', 'cooperative.manage'],
            'catalog' => ['product.view', 'product.manage', 'inventory.view', 'inventory.adjust', 'supplier.manage', 'kit.manage'],
            'sales'   => ['order.view', 'order.manage', 'handover.process', 'pos.sell', 'pos.discount', 'customer.view'],
            'finance' => ['refund.manage', 'promotion.manage', 'report.view', 'report.export'],
            'admin'   => ['activity_log.view', 'module.use', 'payment.manage', 'staff.manage', 'settings.manage', 'subscription.manage'],
        ],
        'owner' => [
            'main'    => ['dashboard.view', 'vendor.view', 'vendor.note', 'vendor.status', 'cooperative.view'],
            'billing' => ['plan.manage', 'module.manage', 'template.manage', 'subscription.view', 'invoice.manage', 'gateway.manage'],
            'system'  => ['translation.manage', 'grade.manage', 'email_template.manage', 'announcement.manage', 'audit.view',
                          'settings.manage', 'staff.manage'],
        ],
    ];

    // Peranan yang sentiasa ada SEMUA kebenaran dan tidak boleh disunting (elak terkunci)
    const FULL_ROLES = ['vendor_admin', 'owner'];

    // Lalai peranan lain (sama seperti kawalan sebelum B3b)
    const DEFAULTS = [
        'coop_manager' => ['dashboard.view', 'cooperative.view', 'cooperative.manage', 'product.view', 'product.manage', 'inventory.view',
                           'inventory.adjust', 'supplier.manage', 'kit.manage', 'order.view', 'order.manage', 'handover.process', 'pos.sell',
                           'pos.discount', 'customer.view', 'refund.manage', 'promotion.manage', 'report.view', 'report.export',
                           'activity_log.view', 'module.use'],
        'cashier'      => ['handover.process', 'pos.sell', 'pos.discount'],
        'owner_staff'  => ['dashboard.view', 'vendor.view', 'vendor.note', 'cooperative.view', 'subscription.view', 'audit.view'],
    ];

    // Had: hanya FULL_ROLES — tidak boleh diberi kepada peranan lain
    const LOCKED = [
        'vendor' => ['payment.manage', 'staff.manage', 'settings.manage', 'subscription.manage'],
        'owner'  => ['gateway.manage', 'settings.manage', 'staff.manage'],
    ];

    public static function panelOf($role)
    {
        return in_array($role, ['owner', 'owner_staff'], true) ? 'owner' : 'vendor';
    }

    // Semua kod satu panel
    public static function all($panel)
    {
        return array_merge(...array_values(self::CATALOG[$panel]));
    }

    public static function exists($panel, $code)
    {
        return in_array($code, self::all($panel), true);
    }

    public static function isEditableRole($role)
    {
        return !in_array($role, self::FULL_ROLES, true);
    }

    public static function isLocked($panel, $code)
    {
        return in_array($code, self::LOCKED[$panel], true);
    }

    public static function defaults($role)
    {
        if (!self::isEditableRole($role)) {
            return self::all(self::panelOf($role));
        }
        return self::DEFAULTS[$role] ?? [];
    }

    // Kebenaran sebenar = lalai + tambah − tarik. $overrides = [ kod => true|false ]. Had & kod tidak dikenali diabaikan
    public static function effective($role, array $overrides)
    {
        $panel = self::panelOf($role);
        if (!self::isEditableRole($role)) {
            return self::all($panel);
        }
        $result = array_fill_keys(self::defaults($role), true);
        foreach ($overrides as $code => $allowed) {
            if (!self::exists($panel, $code) || self::isLocked($panel, $code)) {
                continue;
            }
            if ($allowed) {
                $result[$code] = true;
            } else {
                unset($result[$code]);
            }
        }
        // Susunan ikut katalog
        return array_values(array_filter(self::all($panel), function ($code) use ($result) {
            return isset($result[$code]);
        }));
    }
}
