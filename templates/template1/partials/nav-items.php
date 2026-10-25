<?php
// Item navigasi store — dikongsi oleh navbar atas (desktop) dan navbar bawah (telefon).
// 'module' = dipapar hanya jika modul add-on aktif (layout.js); tanpa sekolah → menu ringkas
if ($schoolSlug === '') {
    return [
        ['page' => 'home',          'key' => 'store.nav.home',     'icon' => 'bi-house'],
        ['page' => 'school-select', 'key' => 'store.select_school', 'icon' => 'bi-search'],
        ['page' => 'children',      'key' => 'store.nav.children', 'icon' => 'bi-people'],
        ['page' => 'profile',       'key' => 'store.nav.account',  'icon' => 'bi-person-circle'],
    ];
}

return [
    ['page' => 'home',      'key' => 'store.nav.home',      'icon' => 'bi-house'],
    ['page' => 'catalog',   'key' => 'store.nav.catalog',   'icon' => 'bi-grid'],
    ['page' => 'kit',       'key' => 'store.nav.kit',       'icon' => 'bi-backpack'],
    ['page' => 'cart',      'key' => 'store.nav.cart',      'icon' => 'bi-cart3'],
    ['page' => 'orders',    'key' => 'store.nav.orders',    'icon' => 'bi-receipt'],
    ['page' => 'news',      'key' => 'store.nav.news',      'icon' => 'bi-megaphone', 'module' => 'news'],
    ['page' => 'clearance', 'key' => 'store.nav.clearance', 'icon' => 'bi-tags',      'module' => 'clearance'],
];
