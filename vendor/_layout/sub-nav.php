<?php
// Kumpulan sub-navigasi vendor. Halaman set $subNavGroup dan $subNavActive sebelum header.
$groups = [
    'cooperatives' => [
        ['key' => 'menu.my_cooperatives', 'url' => 'vendor/cooperatives.php', 'icon' => 'bi-building'],
        ['key' => 'menu.school_classes',  'url' => 'vendor/classes.php',      'icon' => 'bi-people'],
    ],
    'products' => [
        ['key' => 'menu.products_list', 'url' => 'vendor/products.php',   'icon' => 'bi-box-seam'],
        ['key' => 'menu.categories',    'url' => 'vendor/categories.php', 'icon' => 'bi-tags'],
    ],
    'inventory' => [
        ['key' => 'menu.stock_levels',    'url' => 'vendor/inventory.php',       'icon' => 'bi-boxes'],
        ['key' => 'menu.stock_movements', 'url' => 'vendor/stock-movements.php', 'icon' => 'bi-clock-history'],
        ['key' => 'menu.stock_takes',     'url' => 'vendor/stock-takes.php',     'icon' => 'bi-clipboard-check'],
    ],
    'suppliers' => [
        ['key' => 'menu.suppliers_list',  'url' => 'vendor/suppliers.php',       'icon' => 'bi-truck'],
        ['key' => 'menu.purchase_orders', 'url' => 'vendor/purchase-orders.php', 'icon' => 'bi-file-earmark-text'],
    ],
];

View::partial('partials/sub-nav.php', ['subNav' => $groups[$subNavGroup], 'subNavActive' => $subNavActive]);
