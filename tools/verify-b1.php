<?php
// Semakan B1 — skema, seed & core. CLI sahaja:  php tools/verify-b1.php
// Perlu: DB koperasi_saas diimport (schema.sql + seed.sql), config/database.php, config/secret.key
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';

$passed = 0;
$failed = 0;

function check($label, $condition)
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [LULUS] $label\n";
    } else {
        $failed++;
        echo "  [GAGAL] $label\n";
    }
}

echo "1. Sambungan PDO\n";
$pdo = Database::connection();
check('EMULATE_PREPARES dimatikan', (int) $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) === 0);
check('ERRMODE_EXCEPTION', $pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION);
$charset = Database::fetchOne("SELECT @@character_set_client AS client, @@character_set_connection AS conn, @@character_set_results AS results, @@collation_connection AS coll");
check('character_set_client/connection/results = utf8mb4', $charset['client'] === 'utf8mb4' && $charset['conn'] === 'utf8mb4' && $charset['results'] === 'utf8mb4');
check('collation_connection = utf8mb4_unicode_ci', $charset['coll'] === 'utf8mb4_unicode_ci');
$dbCharset = Database::fetchOne("SELECT DEFAULT_CHARACTER_SET_NAME AS cs, DEFAULT_COLLATION_NAME AS coll FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()");
check('Pangkalan data utf8mb4_unicode_ci', $dbCharset['cs'] === 'utf8mb4' && $dbCharset['coll'] === 'utf8mb4_unicode_ci');

echo "2. Skema\n";
$tableCount = (int) Database::fetchValue("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
check("78 jadual (dapat $tableCount) — 75 B1 + platform_user_tokens B2 + 2 jadual kebenaran B3b", $tableCount === 78);
$badTables = (int) Database::fetchValue(
    "SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND (ENGINE <> 'InnoDB' OR TABLE_COLLATION <> 'utf8mb4_unicode_ci')"
);
check('Semua jadual InnoDB + utf8mb4_unicode_ci', $badTables === 0);
$missingVendorId = Database::fetchAll(
    "SELECT t.TABLE_NAME FROM information_schema.TABLES t
      WHERE t.TABLE_SCHEMA = DATABASE()
        AND t.TABLE_NAME IN ('cooperatives','products','product_variants','stock_levels','stock_movements','orders','order_items',
                             'payments','refunds','school_kits','school_kit_items','children','carts','promotions','pos_shifts',
                             'news_posts','clearance_sales','calendar_events','pickup_slots','activity_logs','invoices')
        AND NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS c
                         WHERE c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND c.COLUMN_NAME = 'vendor_id')"
);
check('Jadual perniagaan utama ada vendor_id', count($missingVendorId) === 0);

echo "3. utf8mb4 melalui PDO (nama Cina / Tamil)\n";
$expectedZh = '练习簿（80页）';
$row = Database::fetchOne(
    "SELECT name, HEX(name) AS hex_name FROM product_translations WHERE product_id = ? AND language_code = 'zh'",
    [6]
);
check('Nama Cina dibaca sama', $row !== null && $row['name'] === $expectedZh);
check('HEX nama Cina = ' . strtoupper(bin2hex($expectedZh)), $row !== null && $row['hex_name'] === strtoupper(bin2hex($expectedZh)));
$tamil = Database::fetchValue("SELECT name FROM product_translations WHERE product_id = ? AND language_code = 'ta'", [8]);
check('Nama Tamil dibaca sama', $tamil === 'சீன எழுத்துப் பயிற்சி புத்தகம்');

// Tulis & baca semula melalui PDO (termasuk emoji 4 bait) dalam transaksi yang dibatalkan
Database::beginTransaction();
$emojiName = '测试 பரிசோதனை 📚';
Database::execute(
    "UPDATE product_translations SET name = ? WHERE vendor_id = ? AND product_id = ? AND language_code = 'zh'",
    [$emojiName, 2, 6]
);
$readBack = Database::fetchOne(
    "SELECT name, HEX(name) AS hex_name FROM product_translations WHERE vendor_id = ? AND product_id = ? AND language_code = 'zh'",
    [2, 6]
);
Database::rollBack();
check('Tulis/baca Cina + Tamil + emoji (4 bait)', $readBack['name'] === $emojiName && $readBack['hex_name'] === strtoupper(bin2hex($emojiName)));

echo "4. UNIQUE barcode per koperasi\n";
$sameBarcode = (int) Database::fetchValue("SELECT COUNT(DISTINCT cooperative_id) FROM products WHERE barcode = ?", ['9555000000017']);
check('Barcode sama wujud di 2 koperasi berbeza', $sameBarcode === 2);
$duplicateRejected = false;
Database::beginTransaction();
try {
    Database::insert(
        "INSERT INTO products (vendor_id, cooperative_id, name, barcode, price) VALUES (?, ?, ?, ?, ?)",
        [1, 1, 'Pendua', '9555000000017', 100]
    );
} catch (PDOException $exception) {
    $duplicateRejected = ($exception->errorInfo[1] ?? 0) === 1062;
}
Database::rollBack();
check('Barcode pendua dalam koperasi SAMA ditolak (1062)', $duplicateRejected);
$otherCoopAllowed = false;
Database::beginTransaction();
try {
    Database::insert(
        "INSERT INTO products (vendor_id, cooperative_id, name, barcode, price) VALUES (?, ?, ?, ?, ?)",
        [2, 3, 'Barcode sama koperasi lain', '9555000000017', 100]
    );
    $otherCoopAllowed = true;
} catch (PDOException $exception) {
    $otherCoopAllowed = false;
}
Database::rollBack();
check('Barcode sama di koperasi LAIN dibenarkan', $otherCoopAllowed);

echo "5. ValidateCooperativeJob — ujian kebocoran dua vendor\n";
$job = new ValidateCooperativeJob(1, 1);
$coop = $job->run();
check('VENDOR01 → koperasi 1 (miliknya) diterima', $coop !== null && (int) $coop['vendor_id'] === 1);
$job = new ValidateCooperativeJob(2, 1);
check('VENDOR02 → koperasi 1 (VENDOR01) ditolak', $job->run() === null);
$job = new ValidateCooperativeJob(1, 2);
check('VENDOR01 → koperasi 2 (VENDOR02) ditolak', $job->run() === null);
$job = new ValidateCooperativeJob(1, 3);
check('VENDOR01 → koperasi 3 (VENDOR02) ditolak', $job->run() === null);
$job2 = new ValidateCooperativeJob(2, 2);
$job3 = new ValidateCooperativeJob(2, 3);
check('VENDOR02 → koperasi 2 & 3 diterima', $job2->run() !== null && $job3->run() !== null);
$job = new ValidateCooperativeJob(2, 999);
check('Koperasi tiada → null (sama seperti milik orang lain)', $job->run() === null);
$job = new ValidateCooperativeJob(0, 1);
check('vendor_id kosong → ditolak', $job->run() === null);
$job = new ValidateCooperativeJob(1, '1 OR 1=1');
$injected = $job->run();
check("Input '1 OR 1=1' dipaksa integer, tiada kebocoran", $injected === null || (int) $injected['vendor_id'] === 1);

echo "6. Seed dua vendor + dua ibu bapa\n";
$coopCounts = Database::fetchAll("SELECT vendor_id, COUNT(*) AS total FROM cooperatives GROUP BY vendor_id ORDER BY vendor_id");
check('VENDOR01 = 1 koperasi, VENDOR02 = 2 koperasi', count($coopCounts) === 2 && (int) $coopCounts[0]['total'] === 1 && (int) $coopCounts[1]['total'] === 2);
$parentA = (int) Database::fetchValue("SELECT COUNT(DISTINCT cooperative_id) FROM children WHERE customer_id = ?", [1]);
check('Ibu bapa A ada anak di 2 sekolah', $parentA === 2);
$childrenOfB = Database::fetchAll("SELECT id FROM children WHERE customer_id = ?", [2]);
$leak = false;
foreach ($childrenOfB as $child) {
    if (Database::fetchOne("SELECT id FROM children WHERE id = ? AND customer_id = ?", [$child['id'], 1]) !== null) {
        $leak = true;
    }
}
check('Anak ibu bapa B tidak dijumpai dengan customer_id ibu bapa A', !$leak);
$childVendorMismatch = (int) Database::fetchValue(
    "SELECT COUNT(*) FROM children ch JOIN cooperatives c ON c.id = ch.cooperative_id WHERE ch.vendor_id <> c.vendor_id"
);
check('children.vendor_id sepadan dengan vendor koperasi', $childVendorMismatch === 0);
$productVendorMismatch = (int) Database::fetchValue(
    "SELECT COUNT(*) FROM products p JOIN cooperatives c ON c.id = p.cooperative_id WHERE p.vendor_id <> c.vendor_id"
);
check('products.vendor_id sepadan dengan vendor koperasi', $productVendorMismatch === 0);
$kitCount = (int) Database::fetchValue("SELECT COUNT(DISTINCT cooperative_id) FROM school_kits");
check('Setiap koperasi ada kit', $kitCount === 3);

echo "7. Kata laluan Argon2id\n";
$hash = Database::fetchValue("SELECT password_hash FROM vendor_users WHERE email = ?", ['ahmad@niagasekolah.my']);
check('Hash bermula $argon2id$', strpos((string) $hash, '$argon2id$') === 0);
check('password_verify kata laluan demo', password_verify('Demo@12345', (string) $hash));
check('password_needs_rehash = false (19456/2/1)', !password_needs_rehash((string) $hash, PASSWORD_ARGON2ID, Config::get('password_options')));

echo "8. Crypto (sodium secretbox)\n";
$secret = 'kunci-rahsia-gateway-ABC123';
$encrypted = Crypto::encrypt($secret);
check('Sulit → buka semula sama', Crypto::decrypt($encrypted) === $secret);
check('Teks sulit tidak mengandungi teks asal', strpos($encrypted, $secret) === false);
check('Dua kali sulit → hasil berbeza (nonce rawak)', Crypto::encrypt($secret) !== $encrypted);
$tampered = base64_encode(substr(base64_decode($encrypted), 0, -1) . 'x');
check('Teks sulit diubah → null', Crypto::decrypt($tampered) === null);
check('hashToken = SHA-256 64 aksara', strlen(Crypto::hashToken('abc')) === 64);

echo "9. Validator\n";
$validator = new Validator(['name' => '  ', 'email' => 'bukan-emel', 'names' => ['ms' => 'Berita'], 'qty' => '5', 'date' => '2026-02-30']);
$validator->required('name')->email('email')->required('names.ms')->integer('qty', 1, 3)->date('date');
$errors = $validator->errors();
check('Medan kosong (ruang sahaja) → wajib', isset($errors['name']));
check('E-mel tidak sah dikesan', isset($errors['email']));
check('Medan bersarang names.ms lulus', !isset($errors['names.ms']));
check('Julat integer dikesan', isset($errors['qty']));
check('Tarikh 30 Feb ditolak', isset($errors['date']));

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed === 0 ? 0 : 1);
