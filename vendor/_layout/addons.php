<?php
// Menu modul add-on (PELAN 5.3). Semua bermula berkunci;
// vendor-layout.js membuka kunci modul yang dilanggan oleh koperasi semasa.
// Kawalan sebenar di pelayan: ModuleAccess::has($cooperativeId, $code)
return [
    ['code' => 'news',            'icon' => 'bi-newspaper'],
    ['code' => 'clearance',       'icon' => 'bi-percent'],
    ['code' => 'calendar',        'icon' => 'bi-calendar-event'],
    ['code' => 'school_opening',  'icon' => 'bi-door-open'],
    ['code' => 'whatsapp_sms',    'icon' => 'bi-whatsapp'],
    ['code' => 'advanced_report', 'icon' => 'bi-graph-up-arrow'],
    ['code' => 'mobile_app',      'icon' => 'bi-phone'],
    ['code' => 'membership',      'icon' => 'bi-person-vcard'],
];
