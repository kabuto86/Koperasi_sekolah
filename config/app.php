<?php
// Tetapan aplikasi. Fail ini selamat di-commit (tiada rahsia).
return [
    'name'              => 'KoperasiKu',
    'timezone'          => 'Asia/Kuala_Lumpur',
    'default_language'  => 'ms',
    'languages'         => ['ms', 'en', 'zh', 'ta'],

    // null = dikesan automatik (cth. '/Koperasi_sekolah' di XAMPP htdocs)
    'base_url'          => null,

    // Naikkan nombor ini bila CSS/JS berubah supaya pelayar tidak guna cache lama
    'asset_version'     => '2',

    // Peringkat 1: data dari assets/js/mock/*.js. Peringkat 2: tukar ke false
    'use_mock'          => true,

    // Peringkat 2 (B1): true = ralat penuh dipapar (pembangunan sahaja). Produksi: false
    'debug'             => false,

    // Hash kata laluan & PIN (PELAN 15: Argon2id 19456/2/1)
    'password_options'  => ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1],

    // Kunci penyulitan (sodium secretbox) — dijana oleh: php tools/generate-secret.php
    'secret_key_file'   => 'config/secret.key',
];
