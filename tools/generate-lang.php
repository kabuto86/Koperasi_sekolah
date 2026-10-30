<?php
// Jana semula storage/lang/*.php (lang/*.php + jadual translations) dan papar kunci hilang setiap bahasa (B14).
// Jalankan selepas deploy / kemas kini lang/*.php (Lang juga menjana sendiri bila fail jana lebih lama daripada sumber):
//   php tools/generate-lang.php            → ringkasan
//   php tools/generate-lang.php --missing  → senarai kunci hilang
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';

$counts = (new GenerateLangFilesJob())->run();
$missing = (new MissingKeysJob())->run();
foreach ($counts as $code => $overrides) {
    echo "storage/lang/$code.php — nilai owner: $overrides, kunci hilang: " . $missing['counts'][$code] . "\n";
    if (in_array('--missing', $argv, true)) {
        foreach ($missing['keys'][$code] as $key) {
            echo "    $key\n";
        }
    }
}
