<?php
// Jana config/secret.key (32 bait rawak, base64) untuk core/Crypto. CLI sahaja.
// Jangan jalankan semula di produksi — rahsia lama tidak boleh dibuka dengan kunci baharu.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$file = dirname(__DIR__) . '/config/secret.key';
if (is_file($file) && !in_array('--force', $argv, true)) {
    echo "config/secret.key sudah wujud. Guna --force untuk menggantikannya (rahsia lama akan hilang).\n";
    exit(1);
}

file_put_contents($file, base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)) . "\n");
chmod($file, 0600);
echo "config/secret.key dijana.\n";
