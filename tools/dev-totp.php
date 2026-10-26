<?php
// Kod TOTP semasa untuk ujian pembangunan (ui-audit.js, ujian manual). CLI sahaja — perlu akses DB & secret.key.
//   php tools/dev-totp.php owner@koperasiku.my      → kod daripada rahsia tersimpan (disulitkan) dalam DB
//   php tools/dev-totp.php --secret=ABCD...          → kod daripada rahsia base32 (cth. semasa sediakan 2FA)
// Cetak "TIADA" jika pengguna belum ada 2FA.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';

$arg = $argv[1] ?? '';
if (strpos($arg, '--secret=') === 0) {
    echo Totp::codeAt(substr($arg, 9), Totp::currentStep()) . "\n";
    exit(0);
}

$encrypted = Database::fetchValue("SELECT totp_secret FROM platform_users WHERE email = ?", [strtolower($arg)]);
if ($encrypted === null) {
    $encrypted = Database::fetchValue("SELECT totp_secret FROM vendor_users WHERE email = ?", [strtolower($arg)]);
}
if ($encrypted === null) {
    echo "TIADA\n";
    exit(0);
}
// Langkah seterusnya — kod langkah semasa mungkin sudah diguna (kod tidak boleh diguna semula)
echo Totp::codeAt(Crypto::decrypt($encrypted), Totp::currentStep() + 1) . "\n";
