<?php
// Menu sidebar owner (PELAN 5.1).
// 'permission' = kod kebenaran (core/Permission.php, PELAN 4.1) yang perlu untuk nampak & buka item ini (tiada = semua).
return [
    [
        'title' => 'menu.section.main',
        'items' => [
            ['key' => 'menu.dashboard',    'icon' => 'bi-speedometer2',  'url' => 'owner/index.php', 'permission' => 'dashboard.view'],
            ['key' => 'menu.vendors',      'icon' => 'bi-shop',          'url' => 'owner/vendors.php', 'permission' => 'vendor.view'],
            ['key' => 'menu.cooperatives', 'icon' => 'bi-building',      'url' => 'owner/cooperatives.php', 'permission' => 'cooperative.view'],
        ],
    ],
    [
        'title' => 'menu.section.billing',
        'items' => [
            ['key' => 'menu.plans',         'icon' => 'bi-card-checklist', 'url' => 'owner/plans.php', 'permission' => 'plan.manage'],
            ['key' => 'menu.coop_pricing',  'icon' => 'bi-layers',         'url' => 'owner/coop-pricing.php', 'permission' => 'plan.manage'],
            ['key' => 'menu.modules',       'icon' => 'bi-puzzle',         'url' => 'owner/modules.php', 'permission' => 'module.manage'],
            ['key' => 'menu.templates',     'icon' => 'bi-palette',        'url' => 'owner/templates.php', 'permission' => 'template.manage'],
            ['key' => 'menu.subscriptions', 'icon' => 'bi-receipt',        'url' => 'owner/subscriptions.php', 'permission' => 'subscription.view'],
            ['key' => 'menu.gateways',      'icon' => 'bi-credit-card',    'url' => 'owner/gateways.php', 'permission' => 'gateway.manage'],
        ],
    ],
    [
        'title' => 'menu.section.system',
        'items' => [
            ['key' => 'menu.translations',      'icon' => 'bi-translate',     'url' => 'owner/translations.php', 'permission' => 'translation.manage'],
            ['key' => 'menu.grades',            'icon' => 'bi-mortarboard',   'url' => 'owner/grades.php', 'permission' => 'grade.manage'],
            ['key' => 'menu.email_templates',   'icon' => 'bi-envelope',      'url' => 'owner/email-templates.php', 'permission' => 'email_template.manage'],
            ['key' => 'menu.announcements',     'icon' => 'bi-megaphone',     'url' => 'owner/announcements.php', 'permission' => 'announcement.manage'],
            ['key' => 'menu.audit_log',         'icon' => 'bi-journal-text',  'url' => 'owner/audit-log.php', 'permission' => 'audit.view'],
            ['key' => 'menu.platform_settings', 'icon' => 'bi-gear',          'url' => 'owner/settings.php', 'permission' => 'settings.manage'],
            ['key' => 'menu.owner_staff',       'icon' => 'bi-people',        'url' => 'owner/staff.php', 'permission' => 'staff.manage'],
        ],
    ],
];
