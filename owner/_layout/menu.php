<?php
// Menu sidebar owner (PELAN 5.1).
// 'roles' = peranan yang boleh nampak item (tiada = semua). owner_staff: sokongan — vendor, koperasi, invois, log audit (PELAN 4)
return [
    [
        'title' => 'menu.section.main',
        'items' => [
            ['key' => 'menu.dashboard',    'icon' => 'bi-speedometer2',  'url' => 'owner/index.php'],
            ['key' => 'menu.vendors',      'icon' => 'bi-shop',          'url' => 'owner/vendors.php'],
            ['key' => 'menu.cooperatives', 'icon' => 'bi-building',      'url' => 'owner/cooperatives.php'],
        ],
    ],
    [
        'title' => 'menu.section.billing',
        'items' => [
            ['key' => 'menu.plans',         'icon' => 'bi-card-checklist', 'url' => 'owner/plans.php', 'roles' => ['owner']],
            ['key' => 'menu.coop_pricing',  'icon' => 'bi-layers',         'url' => 'owner/coop-pricing.php', 'roles' => ['owner']],
            ['key' => 'menu.modules',       'icon' => 'bi-puzzle',         'url' => 'owner/modules.php', 'roles' => ['owner']],
            ['key' => 'menu.templates',     'icon' => 'bi-palette',        'url' => 'owner/templates.php', 'roles' => ['owner']],
            ['key' => 'menu.subscriptions', 'icon' => 'bi-receipt',        'url' => 'owner/subscriptions.php'],
            ['key' => 'menu.gateways',      'icon' => 'bi-credit-card',    'url' => 'owner/gateways.php', 'roles' => ['owner']],
        ],
    ],
    [
        'title' => 'menu.section.system',
        'items' => [
            ['key' => 'menu.translations',      'icon' => 'bi-translate',     'url' => 'owner/translations.php', 'roles' => ['owner']],
            ['key' => 'menu.grades',            'icon' => 'bi-mortarboard',   'url' => 'owner/grades.php', 'roles' => ['owner']],
            ['key' => 'menu.email_templates',   'icon' => 'bi-envelope',      'url' => 'owner/email-templates.php', 'roles' => ['owner']],
            ['key' => 'menu.announcements',     'icon' => 'bi-megaphone',     'url' => 'owner/announcements.php', 'roles' => ['owner']],
            ['key' => 'menu.audit_log',         'icon' => 'bi-journal-text',  'url' => 'owner/audit-log.php'],
            ['key' => 'menu.platform_settings', 'icon' => 'bi-gear',          'url' => 'owner/settings.php', 'roles' => ['owner']],
            ['key' => 'menu.owner_staff',       'icon' => 'bi-people',        'url' => 'owner/staff.php', 'roles' => ['owner']],
        ],
    ],
];
