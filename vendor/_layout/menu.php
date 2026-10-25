<?php
// Menu sidebar vendor — pakej asas (PELAN 5.2).
// 'roles' = peranan yang boleh nampak item (tiada = semua). Juruwang: POS & serah barang sahaja (PELAN 4)
return [
    [
        'title' => 'menu.section.main',
        'items' => [
            ['key' => 'menu.dashboard',       'icon' => 'bi-speedometer2', 'url' => 'vendor/index.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.my_cooperatives', 'icon' => 'bi-building',     'url' => 'vendor/cooperatives.php', 'roles' => ['vendor_admin', 'coop_manager']],
        ],
    ],
    [
        'title' => 'menu.section.catalog',
        'items' => [
            ['key' => 'menu.products',    'icon' => 'bi-box-seam',     'url' => 'vendor/products.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.inventory',   'icon' => 'bi-boxes',        'url' => 'vendor/inventory.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.suppliers',   'icon' => 'bi-truck',        'url' => 'vendor/suppliers.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.school_kits', 'icon' => 'bi-backpack',     'url' => 'vendor/school-kits.php', 'roles' => ['vendor_admin', 'coop_manager']],
        ],
    ],
    [
        'title' => 'menu.section.sales',
        'items' => [
            ['key' => 'menu.orders',     'icon' => 'bi-bag-check',    'url' => 'vendor/orders.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.handover',   'icon' => 'bi-hand-index',   'url' => 'vendor/pos/handover.php'],
            ['key' => 'menu.pos',        'icon' => 'bi-upc-scan',     'url' => 'vendor/pos/index.php'],
            ['key' => 'menu.customers',  'icon' => 'bi-people',       'url' => 'vendor/customers.php', 'roles' => ['vendor_admin', 'coop_manager']],
        ],
    ],
    [
        'title' => 'menu.section.finance',
        'items' => [
            ['key' => 'menu.payments',   'icon' => 'bi-credit-card',     'url' => 'vendor/payments.php', 'roles' => ['vendor_admin']],
            ['key' => 'menu.refunds',    'icon' => 'bi-arrow-return-left', 'url' => 'vendor/refunds.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.promotions', 'icon' => 'bi-tag',             'url' => 'vendor/promotions.php', 'roles' => ['vendor_admin', 'coop_manager']],
            ['key' => 'menu.reports',    'icon' => 'bi-bar-chart',       'url' => 'vendor/reports.php', 'roles' => ['vendor_admin', 'coop_manager']],
        ],
    ],
    [
        'title' => 'menu.section.admin',
        'items' => [
            ['key' => 'menu.staff',        'icon' => 'bi-person-badge',  'url' => 'vendor/staff.php', 'roles' => ['vendor_admin']],
            ['key' => 'menu.settings',     'icon' => 'bi-gear',          'url' => 'vendor/settings.php', 'roles' => ['vendor_admin']],
            ['key' => 'menu.subscription', 'icon' => 'bi-receipt',       'url' => 'vendor/subscription.php', 'roles' => ['vendor_admin']],
            ['key' => 'menu.activity_log', 'icon' => 'bi-journal-text',  'url' => 'vendor/activity-log.php', 'roles' => ['vendor_admin', 'coop_manager']],
        ],
    ],
];
