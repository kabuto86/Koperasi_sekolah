<?php
// Menu sidebar vendor — pakej asas (PELAN 5.2).
// 'permission' = kod kebenaran (core/Permission.php, PELAN 4.1) yang perlu untuk nampak & buka item ini (tiada = semua).
return [
    [
        'title' => 'menu.section.main',
        'items' => [
            ['key' => 'menu.dashboard',       'icon' => 'bi-speedometer2', 'url' => 'vendor/index.php', 'permission' => 'dashboard.view'],
            ['key' => 'menu.my_cooperatives', 'icon' => 'bi-building',     'url' => 'vendor/cooperatives.php', 'permission' => 'cooperative.view'],
        ],
    ],
    [
        'title' => 'menu.section.catalog',
        'items' => [
            ['key' => 'menu.products',    'icon' => 'bi-box-seam',     'url' => 'vendor/products.php', 'permission' => 'product.view'],
            ['key' => 'menu.inventory',   'icon' => 'bi-boxes',        'url' => 'vendor/inventory.php', 'permission' => 'inventory.view'],
            ['key' => 'menu.suppliers',   'icon' => 'bi-truck',        'url' => 'vendor/suppliers.php', 'permission' => 'supplier.manage'],
            ['key' => 'menu.school_kits', 'icon' => 'bi-backpack',     'url' => 'vendor/school-kits.php', 'permission' => 'kit.manage'],
        ],
    ],
    [
        'title' => 'menu.section.sales',
        'items' => [
            ['key' => 'menu.orders',     'icon' => 'bi-bag-check',    'url' => 'vendor/orders.php', 'permission' => 'order.view'],
            ['key' => 'menu.handover',   'icon' => 'bi-hand-index',   'url' => 'vendor/pos/handover.php', 'permission' => 'handover.process'],
            ['key' => 'menu.pos',        'icon' => 'bi-upc-scan',     'url' => 'vendor/pos/index.php', 'permission' => 'pos.sell'],
            ['key' => 'menu.customers',  'icon' => 'bi-people',       'url' => 'vendor/customers.php', 'permission' => 'customer.view'],
        ],
    ],
    [
        'title' => 'menu.section.finance',
        'items' => [
            ['key' => 'menu.payments',   'icon' => 'bi-credit-card',     'url' => 'vendor/payments.php', 'permission' => 'payment.manage'],
            ['key' => 'menu.refunds',    'icon' => 'bi-arrow-return-left', 'url' => 'vendor/refunds.php', 'permission' => 'refund.manage'],
            ['key' => 'menu.promotions', 'icon' => 'bi-tag',             'url' => 'vendor/promotions.php', 'permission' => 'promotion.manage'],
            ['key' => 'menu.reports',    'icon' => 'bi-bar-chart',       'url' => 'vendor/reports.php', 'permission' => 'report.view'],
        ],
    ],
    [
        'title' => 'menu.section.admin',
        'items' => [
            ['key' => 'menu.staff',        'icon' => 'bi-person-badge',  'url' => 'vendor/staff.php', 'permission' => 'staff.manage'],
            ['key' => 'menu.settings',     'icon' => 'bi-gear',          'url' => 'vendor/settings.php', 'permission' => 'settings.manage'],
            ['key' => 'menu.subscription', 'icon' => 'bi-receipt',       'url' => 'vendor/subscription.php', 'permission' => 'subscription.manage'],
            ['key' => 'menu.activity_log', 'icon' => 'bi-journal-text',  'url' => 'vendor/activity-log.php', 'permission' => 'activity_log.view'],
        ],
    ],
];
