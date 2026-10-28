<?php
// Tetapan aplikasi. Fail ini selamat di-commit (tiada rahsia).
return [
    'name'              => 'KoperasiKu',
    'timezone'          => 'Asia/Kuala_Lumpur',
    'default_language'  => 'ms',
    'languages'         => ['ms', 'en', 'zh', 'ta'],

    // null = dikesan automatik (cth. '/Koperasi_sekolah' di XAMPP htdocs)
    'base_url'          => null,

    // URL penuh untuk pautan dalam e-mel (cth. 'https://koperasiku.my'). null = dari hos permintaan (pembangunan sahaja).
    // Produksi: WAJIB diisi — elak pautan reset kata laluan dipalsukan melalui header Host
    'app_url'           => null,

    // URL store cantik /{slug}/{halaman} (B6). Perlu Apache mod_rewrite + .htaccess di akar projek.
    // false = store/index.php?school={slug}&page=... (berfungsi di semua pelayan, termasuk php -S)
    'pretty_store_urls' => false,

    // Naikkan nombor ini bila CSS/JS berubah supaya pelayar tidak guna cache lama
    'asset_version'     => '9',

    // Peringkat 1: data dari assets/js/mock/*.js. Peringkat 2: laluan yang sudah siap guna fetch() (Api.livePrefixes
    // dalam api.js); selebihnya masih mock sehingga session masing-masing. Tukar ke false bila semua B siap
    'use_mock'          => true,

    // Peringkat 2 (B1): true = ralat penuh dipapar (pembangunan sahaja). Produksi: false
    'debug'             => false,

    // Hash kata laluan & PIN (PELAN 15: Argon2id 19456/2/1)
    'password_options'  => ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1],

    // E-mel (B7): 'smtp' = tetapan SMTP owner (panel Owner → Tetapan); 'log' = tulis ke mail_log_file (pembangunan tanpa SMTP).
    // E-mel dihantar oleh cron/send-emails.php sahaja
    'mail_driver'       => 'smtp',
    'mail_log_file'     => 'storage/mail.log',

    // Bayaran online (B8): pesanan pending_payment yang tidak dibayar dalam tempoh ini diluputkan oleh
    // cron/expire-online-orders.php (stok dilepas). Bil gateway disemak semula dahulu sebelum diluputkan
    'online_payment_expiry_minutes' => 60,

    // Kunci penyulitan (sodium secretbox) — dijana oleh: php tools/generate-secret.php
    'secret_key_file'   => 'config/secret.key',
];
