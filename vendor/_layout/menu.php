<?php
// Menu sidebar vendor — pakej asas (PELAN 5.2)
return [
    [
        'title' => 'menu.section.main',
        'items' => [
            ['key' => 'menu.dashboard',       'icon' => 'bi-speedometer2', 'url' => 'vendor/index.php'],
            ['key' => 'menu.my_cooperatives', 'icon' => 'bi-building',     'url' => 'vendor/cooperatives.php'],
        ],
    ],
    [
        'title' => 'menu.section.catalog',
        'items' => [
            ['key' => 'menu.products',    'icon' => 'bi-box-seam',     'url' => 'vendor/products.php'],
            ['key' => 'menu.inventory',   'icon' => 'bi-boxes',        'url' => 'vendor/inventory.php'],
            ['key' => 'menu.suppliers',   'icon' => 'bi-truck',        'url' => 'vendor/suppliers.php'],
            ['key' => 'menu.school_kits', 'icon' => 'bi-backpack',     'url' => 'vendor/school-kits.php'],
        ],
    ],
    [
        'title' => 'menu.section.sales',
        'items' => [
            ['key' => 'menu.orders',     'icon' => 'bi-bag-check',    'url' => 'vendor/orders.php'],
            ['key' => 'menu.handover',   'icon' => 'bi-hand-index',   'url' => 'vendor/pos/handover.php'],
            ['key' => 'menu.pos',        'icon' => 'bi-upc-scan',     'url' => 'vendor/pos/index.php'],
            ['key' => 'menu.customers',  'icon' => 'bi-people',       'url' => 'vendor/customers.php'],
        ],
    ],
    [
        'title' => 'menu.section.finance',
        'items' => [
            ['key' => 'menu.payments',   'icon' => 'bi-credit-card',     'url' => 'vendor/payments.php'],
            ['key' => 'menu.refunds',    'icon' => 'bi-arrow-return-left', 'url' => 'vendor/refunds.php'],
            ['key' => 'menu.promotions', 'icon' => 'bi-tag',             'url' => 'vendor/promotions.php'],
            ['key' => 'menu.reports',    'icon' => 'bi-bar-chart',       'url' => 'vendor/reports.php'],
        ],
    ],
    [
        'title' => 'menu.section.admin',
        'items' => [
            ['key' => 'menu.staff',        'icon' => 'bi-person-badge',  'url' => 'vendor/staff.php'],
            ['key' => 'menu.settings',     'icon' => 'bi-gear',          'url' => 'vendor/settings.php'],
            ['key' => 'menu.subscription', 'icon' => 'bi-receipt',       'url' => 'vendor/subscription.php'],
            ['key' => 'menu.activity_log', 'icon' => 'bi-journal-text',  'url' => 'vendor/activity-log.php'],
        ],
    ],
];
