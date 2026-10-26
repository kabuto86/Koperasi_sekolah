<?php
// Salin fail ini kepada config/database.php dan isi kata laluan sebenar.
// config/database.php ada dalam .gitignore — JANGAN commit kata laluan.
// Gunakan pengguna koperasi_user (akses koperasi_saas sahaja), bukan root.
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'koperasi_saas',
    'username' => 'koperasi_user',
    'password' => 'GANTI_KATA_LALUAN_KUAT',
];
