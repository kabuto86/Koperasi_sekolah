<?php
// Semakan B3 — panel owner: vendor, koperasi, pelan & peringkat koperasi, modul, template, gateway, darjah,
// templat e-mel, pengumuman, tetapan, log audit + sekatan owner_staff + ujian kebocoran. CLI sahaja:
//   php tools/verify-b3.php
// Perlu: DB koperasi_saas (schema + seed), config/database.php, config/secret.key, sambungan PHP curl.
// Skrip memulakan pelayan PHP sendiri (php -S 127.0.0.1:8099). Data yang diubah dipulihkan di hujung.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/core/bootstrap.php';
require __DIR__ . '/test-helpers.php';

// Baca terus dari DB (PlatformSettings dicache dalam proses CLI ini, perubahan dibuat oleh pelayan ujian)
function settingValue($key)
{
    return (string) Database::fetchValue("SELECT setting_value FROM platform_settings WHERE setting_key = ?", [$key]);
}

function ownerSecret($name)
{
    $secrets = json_decode((string) Crypto::decrypt(settingValue('owner_gateway_secrets_enc')), true);
    return $secrets[$name] ?? null;
}

$port = 8099;
$base = 'http://127.0.0.1:' . $port;
$server = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', APP_ROOT],
    [0 => ['pipe', 'r'], 1 => ['file', sys_get_temp_dir() . '/kop-verify-b3.log', 'w'], 2 => ['file', sys_get_temp_dir() . '/kop-verify-b3.log', 'a']],
    $pipes
);
usleep(700000);

// Simpan keadaan asal untuk dipulihkan
$savedUsers = Database::fetchAll("SELECT id, totp_secret, totp_last_step FROM platform_users");
$savedCodes = Database::fetchAll("SELECT user_type, user_id, code_hash, used_at, created_at FROM totp_recovery_codes WHERE user_type = 'owner'");
$savedVendors = Database::fetchAll("SELECT id, status FROM vendors");
$savedSubscriptions = Database::fetchAll("SELECT id, status FROM subscriptions");
$savedSettings = Database::fetchAll("SELECT setting_key, setting_value FROM platform_settings");
$savedGateways = Database::fetchAll("SELECT code, is_enabled_for_vendors FROM payment_gateways");
$savedTemplates = Database::fetchAll("SELECT id, name, description, price_monthly, price_yearly, is_free, is_active FROM templates");
$savedEmail = Database::fetchOne("SELECT subject, body_html FROM email_templates WHERE template_key = 'order_ready' AND language_code = 'ta'");
$maxNoteId = (int) Database::fetchValue("SELECT COALESCE(MAX(id), 0) FROM vendor_notes");
$maxAnnouncementId = (int) Database::fetchValue("SELECT COALESCE(MAX(id), 0) FROM vendor_announcements");
$maxAuditId = (int) Database::fetchValue("SELECT COALESCE(MAX(id), 0) FROM audit_logs");
$maxEmailId = (int) Database::fetchValue("SELECT COALESCE(MAX(id), 0) FROM email_queue");
clearTestLocks();
Database::execute("UPDATE platform_users SET totp_secret = NULL, totp_last_step = NULL");

try {
    list($owner, $ownerSecret) = ownerLogin($base, 'owner@koperasiku.my');
    list($staff) = ownerLogin($base, 'staf@koperasiku.my');
    $owner->page('owner/index.php');
    $staff->page('owner/index.php');

    echo "1. Akses & sekatan peranan (PELAN 4)\n";
    $guest = new Browser($base);
    check('Tanpa sesi → owner/plans/list auth_required', !empty($guest->get('owner/plans/list')['auth_required']));
    check('POST tanpa X-CSRF-Token → 419', $owner->postStatus('owner/plans/save', ['code' => 'X'], false) === 419);
    check('GET ke endpoint POST → 405', $owner->getStatus('owner/settings/save') === 405);
    $ownerOnly = [
        ['get', 'owner/plans/list', []], ['get', 'owner/plans/tiers', ['plan_id' => 1]], ['post', 'owner/plans/save', []],
        ['post', 'owner/plans/save-tiers', []], ['post', 'owner/plans/delete', ['id' => 1]], ['get', 'owner/modules/list', []],
        ['post', 'owner/modules/save', []], ['get', 'owner/templates/list', []], ['post', 'owner/templates/save', []],
        ['get', 'owner/gateways/list', []], ['post', 'owner/gateways/toggle', []], ['post', 'owner/gateways/save-owner', []],
        ['get', 'owner/grades/list', []], ['post', 'owner/grades/save', []], ['get', 'owner/email-templates/list', []],
        ['post', 'owner/email-templates/save', []], ['get', 'owner/announcements/list', []], ['post', 'owner/announcements/save', []],
        ['get', 'owner/settings/get', []], ['post', 'owner/settings/save', []], ['post', 'owner/settings/test-email', []],
        ['post', 'owner/vendors/set-status', ['id' => 2, 'status' => 'suspended', 'reason' => 'x']],
    ];
    $blocked = 0;
    foreach ($ownerOnly as $call) {
        $status = $call[0] === 'get'
            ? $staff->getStatus($call[1] . '.php?' . http_build_query($call[2]))
            : $staff->postStatus($call[1], $call[2]);
        $blocked += $status === 403 ? 1 : 0;
    }
    check('owner_staff → 22 laluan harga/pelan/modul/tetapan/gantung semua 403', $blocked === count($ownerOnly));
    check('owner_staff: vendor 2 tidak digantung oleh percubaan 403', Database::fetchValue("SELECT status FROM vendors WHERE id = 2") === 'active');
    check('owner_staff → papan pemuka, vendor, koperasi, log audit dibenarkan',
        $staff->get('owner/dashboard/summary')['ok'] && $staff->get('owner/vendors/list')['ok']
        && $staff->get('owner/cooperatives/list')['ok'] && $staff->get('owner/audit-logs/list')['ok']);
    $planOptions = $staff->get('owner/plans/options');
    check('owner_staff → plans/options tanpa harga', $planOptions['ok'] && count($planOptions['data']) === 3 && !isset($planOptions['data'][0]['price_monthly']));
    check('owner_staff → halaman tetapan dialih', $staff->page('owner/settings.php')['status'] === 302);
    check('owner_staff → halaman vendor tanpa butang gantung', strpos($staff->page('owner/vendor.php?id=1')['body'], 'btn-suspend') === false);
    check('owner → halaman vendor dengan butang gantung', strpos($owner->page('owner/vendor.php?id=1')['body'], 'btn-suspend') !== false);

    echo "2. Kebocoran: sesi vendor & ibu bapa tidak boleh ke API owner\n";
    $vendor1 = vendorLogin($base, 'ahmad@niagasekolah.my');
    $vendor2 = vendorLogin($base, 'meiling@bestari.my');
    list($parentA) = storeLogin($base, 'siti@contoh.my');
    list($parentB) = storeLogin($base, 'rajesh@contoh.my');
    foreach (['VENDOR01' => $vendor1, 'VENDOR02' => $vendor2, 'ibu bapa A' => $parentA, 'ibu bapa B' => $parentB] as $label => $browser) {
        $list = $browser->get('owner/vendors/list');
        $note = $browser->post('owner/vendors/add-note', ['id' => 1, 'body' => 'cubaan']);
        check("$label → owner/vendors/list & add-note ditolak (401)", !empty($list['auth_required']) && !empty($note['auth_required']));
    }
    check('Tiada nota dicipta oleh sesi bukan owner', (int) Database::fetchValue("SELECT COUNT(*) FROM vendor_notes WHERE id > ?", [$maxNoteId]) === 0);

    echo "3. Papan pemuka\n";
    $dash = $owner->get('owner/dashboard/summary')['data'];
    check('Vendor aktif = 2, koperasi = 3', $dash['vendors']['active'] === 2 && $dash['cooperatives_total'] === 3 && $dash['cooperatives_active'] === 3);
    check('MRR = (99000 + 65000) / 12 + 4900 + 4900 + 1000 = 24466 sen', $dash['mrr'] === 24466);
    check('Sejarah MRR 6 bulan, bulan terakhir = MRR semasa', count($dash['mrr_history']) === 6 && end($dash['mrr_history'])['amount'] === 24466);
    check('Langganan hampir tamat: VENDOR02 (10 Nov)', count($dash['expiring']) === 1 && $dash['expiring'][0]['vendor_code'] === 'VENDOR02');

    echo "4. Vendor\n";
    $vendors = $owner->get('owner/vendors/list');
    check('Senarai 2 vendor (bentuk VendorSummary)', $vendors['data']['total'] === 2 && $vendors['data']['items'][0]['plan_name'] === 'Pro'
        && $vendors['data']['items'][1]['cooperative_count'] === 2);
    check('Tapis q=bestari → VENDOR02', $owner->get('owner/vendors/list', ['q' => 'bestari'])['data']['items'][0]['code'] === 'VENDOR02');
    check('Tapis plan_id=1 → 1 vendor', $owner->get('owner/vendors/list', ['plan_id' => 1])['data']['total'] === 1);
    check('Tapis q dengan % tidak memadankan semua', $owner->get('owner/vendors/list', ['q' => '%'])['data']['total'] === 0);
    $detail = $owner->get('owner/vendors/get', ['id' => 2])['data'];
    $coopVendors = array_unique(array_column($detail['cooperatives'], 'vendor_id'));
    check('Butiran VENDOR02: 2 koperasi miliknya sahaja', count($detail['cooperatives']) === 2 && $coopVendors === [2]);
    check('Butiran VENDOR02: langganan & nota', $detail['subscription']['plan_name'] === 'Asas' && count($detail['notes']) === 1);
    check('Vendor tiada → 404', $owner->get('owner/vendors/get', ['id' => 999])['ok'] === false);
    $noReason = $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'suspended', 'reason' => '']);
    check('Gantung tanpa sebab → errors.reason', $noReason['ok'] === false && isset($noReason['errors']['reason']));
    check('Status tidak sah ditolak', $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'cancelled'])['ok'] === false);
    check('Aktifkan vendor yang sudah aktif ditolak', $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'active'])['ok'] === false);
    $suspend = $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'suspended', 'reason' => 'Invois tertunggak (ujian B3)']);
    check('Gantung VENDOR02 berjaya', $suspend['ok'] && $suspend['data']['status'] === 'suspended');
    check('Langganan VENDOR02 ikut digantung', Database::fetchValue("SELECT status FROM subscriptions WHERE vendor_id = 2") === 'suspended');
    check('VENDOR01 tidak terjejas', Database::fetchValue("SELECT status FROM vendors WHERE id = 1") === 'active');
    check('Sebab disimpan sebagai nota', (int) Database::fetchValue("SELECT COUNT(*) FROM vendor_notes WHERE vendor_id = 2 AND body = 'Invois tertunggak (ujian B3)'") === 1);
    $billing = $vendor2->get('vendor/staff/list');
    check('Sesi VENDOR02 sedia ada kini terhad kepada bil', $billing['ok'] === false && !empty($billing['billing_only']));
    check('Aktifkan semula VENDOR02', $owner->post('owner/vendors/set-status', ['id' => 2, 'status' => 'active'])['data']['status'] === 'active');
    $note = $owner->post('owner/vendors/add-note', ['id' => 1, 'body' => 'Nota sokongan (ujian B3)']);
    check('owner_staff boleh tambah nota', $staff->post('owner/vendors/add-note', ['id' => 1, 'body' => 'Nota staf (ujian B3)'])['ok']);
    check('Nota owner: created_by = nama owner', $note['ok'] && $note['data']['created_by'] === 'Pemilik Platform');
    check('Nota kosong ditolak', isset($owner->post('owner/vendors/add-note', ['id' => 1, 'body' => ' '])['errors']['body']));
    $options = $owner->get('owner/vendors/options')['data'];
    check('vendors/options: id, code, company_name, status', count($options) === 2 && array_keys($options[0]) === ['id', 'code', 'company_name', 'status']);

    echo "5. Koperasi (baca sahaja)\n";
    $coops = $owner->get('owner/cooperatives/list');
    check('3 koperasi semua vendor', $coops['data']['total'] === 3);
    check('Tapis vendor_id=2 → 2', $owner->get('owner/cooperatives/list', ['vendor_id' => 2])['data']['total'] === 2);
    $melati = $coops['data']['items'][0];
    check('Modul aktif & template koperasi', $melati['modules'] === ['news', 'calendar', 'school_opening', 'whatsapp_sms'] && $melati['template_code'] === 'template1');
    check('Tapis school_type=SJKC + q', $owner->get('owner/cooperatives/list', ['school_type' => 'SJKC', 'q' => 'chung'])['data']['total'] === 1);

    echo "6. Pelan & harga koperasi tambahan\n";
    $plans = $owner->get('owner/plans/list')['data'];
    check('3 pelan, vendor_count Pro = 1, Premium tanpa had', count($plans) === 3 && $plans[1]['vendor_count'] === 1 && $plans[2]['max_cooperatives'] === null);
    $newPlan = $owner->post('owner/plans/save', ['code' => 'uji_b3', 'name' => 'Ujian B3', 'price_monthly' => 2500, 'price_yearly' => 25000,
        'max_cooperatives' => '', 'max_products' => 50, 'max_staff' => null, 'features' => ['Satu', ' ', 'Dua'], 'is_active' => true, 'sort_order' => 9]);
    $newPlanId = $newPlan['data']['id'] ?? 0;
    check('Pelan baharu: kod huruf besar, ciri dibersihkan, had kosong = null', $newPlan['ok'] && $newPlan['data']['code'] === 'UJI_B3'
        && $newPlan['data']['features'] === ['Satu', 'Dua'] && $newPlan['data']['max_cooperatives'] === null);
    $tiers = $owner->get('owner/plans/tiers', ['plan_id' => $newPlanId])['data']['tiers'];
    check('Peringkat lalai: 2+ harga sama seperti pelan', count($tiers) === 1 && $tiers[0]['from_coop'] === 2 && $tiers[0]['to_coop'] === null
        && $tiers[0]['price_monthly'] === 2500);
    check('Kod pendua → errors.code', isset($owner->post('owner/plans/save', ['code' => 'PRO', 'name' => 'X', 'price_monthly' => 1, 'price_yearly' => 1])['errors']['code']));
    check('Harga negatif ditolak', isset($owner->post('owner/plans/save', ['code' => 'NEG', 'name' => 'X', 'price_monthly' => -1, 'price_yearly' => 1])['errors']['price_monthly']));
    $bad = [
        [['from_coop' => 3, 'to_coop' => null, 'price_monthly' => 1, 'price_yearly' => 1]],
        [['from_coop' => 2, 'to_coop' => 4, 'price_monthly' => 1, 'price_yearly' => 1], ['from_coop' => 6, 'to_coop' => null, 'price_monthly' => 1, 'price_yearly' => 1]],
        [['from_coop' => 2, 'to_coop' => 4, 'price_monthly' => 1, 'price_yearly' => 1]],
        [['from_coop' => 2, 'to_coop' => null, 'price_monthly' => null, 'price_yearly' => 1]],
        [],
    ];
    $rejected = 0;
    foreach ($bad as $set) {
        $rejected += $owner->post('owner/plans/save-tiers', ['plan_id' => $newPlanId, 'tiers' => $set])['ok'] ? 0 : 1;
    }
    check('5 set peringkat tidak sah ditolak (mula ≠ 2, jurang, terakhir ada had, harga kosong, kosong)', $rejected === 5);
    $saved = $owner->post('owner/plans/save-tiers', ['plan_id' => $newPlanId, 'tiers' => [
        ['from_coop' => 2, 'to_coop' => 3, 'price_monthly' => 2000, 'price_yearly' => 20000],
        ['from_coop' => 4, 'to_coop' => null, 'price_monthly' => 1500, 'price_yearly' => 15000],
    ]]);
    check('Peringkat berturutan disimpan', $saved['ok'] && count($saved['data']['tiers']) === 2);
    $price = (new CalculateCoopPriceJob($newPlanId, 5, 'monthly'))->run();
    check('CalculateCoopPriceJob: 5 koperasi = 2500 + 2000×2 + 1500×2 = 9500', $price['total'] === 9500 && $price['lines'][0]['price'] === 0);
    $pro = (new CalculateCoopPriceJob(2, 4, 'yearly'))->run();
    check('CalculateCoopPriceJob Pro tahunan 4 koperasi = 99000 + 79000×2 + 59000', $pro['total'] === 99000 + 79000 * 2 + 59000);
    check('Padam pelan yang digunakan ditolak', $owner->post('owner/plans/delete', ['id' => 2])['ok'] === false);
    check('Padam pelan baharu berjaya + peringkat dibuang', $owner->post('owner/plans/delete', ['id' => $newPlanId])['ok']
        && (int) Database::fetchValue("SELECT COUNT(*) FROM plan_coop_tiers WHERE plan_id = ?", [$newPlanId]) === 0);

    echo "7. Modul add-on\n";
    $modules = $owner->get('owner/modules/list')['data'];
    check('8 modul, nama Cina dari lang/zh.php, keterangan dari translations', count($modules) === 8 && $modules[0]['names']['zh'] === Lang::fileValue('zh', 'module.news')
        && $modules[0]['descriptions']['ms'] === 'Pos berita kepada ibu bapa koperasi itu.' && $modules[0]['cooperative_count'] === 1);
    check('Kod tidak sah → errors.code', isset($owner->post('owner/modules/save', ['code' => 'Bad-Code', 'names' => ['ms' => 'X'], 'price_monthly' => 1, 'price_yearly' => 1])['errors']['code']));
    check('Kod pendua → errors.code', isset($owner->post('owner/modules/save', ['code' => 'news', 'names' => ['ms' => 'X'], 'price_monthly' => 1, 'price_yearly' => 1])['errors']['code']));
    check('Nama Melayu wajib', isset($owner->post('owner/modules/save', ['code' => 'uji_modul', 'names' => ['en' => 'X'], 'price_monthly' => 1, 'price_yearly' => 1])['errors']['names.ms']));
    $module = $owner->post('owner/modules/save', ['code' => 'uji_modul', 'names' => ['ms' => 'Modul ujian', 'zh' => '测试模块', 'ta' => 'சோதனை'],
        'descriptions' => ['ms' => 'Keterangan'], 'price_monthly' => 900, 'price_yearly' => 9000, 'is_active' => true, 'sort_order' => 20]);
    $moduleId = $module['data']['id'] ?? 0;
    check('Modul baharu disimpan dengan nama 4 bahasa (utf8mb4)', $module['ok'] && $module['data']['names']['zh'] === '测试模块' && $module['data']['names']['en'] === '');
    $renamed = $owner->post('owner/modules/save', ['id' => $moduleId, 'code' => 'lain', 'names' => ['ms' => 'Modul ujian 2'], 'price_monthly' => 1000, 'price_yearly' => 10000]);
    check('Kod tidak berubah semasa sunting; nama zh dikosongkan dibuang', $renamed['data']['code'] === 'uji_modul' && $renamed['data']['names']['zh'] === ''
        && $renamed['data']['price_monthly'] === 1000);

    echo "8. Template\n";
    check('template1 tidak boleh dijadikan berbayar', $owner->post('owner/templates/save', ['id' => 1, 'name' => 'T1', 'is_free' => false, 'is_active' => true, 'price_monthly' => 100, 'price_yearly' => 1000])['ok'] === false);
    $template = $owner->post('owner/templates/save', ['id' => 2, 'name' => 'Template 2 — Ceria', 'description' => 'Baharu', 'is_free' => false, 'is_active' => true, 'price_monthly' => 1200, 'price_yearly' => 12000]);
    check('template2 harga dikemas kini', $template['ok'] && $template['data']['price_monthly'] === 1200);
    $free = $owner->post('owner/templates/save', ['id' => 3, 'name' => 'Template 3', 'is_free' => true, 'is_active' => false]);
    check('Percuma → harga 0', $free['ok'] && $free['data']['price_yearly'] === 0);
    check('Senarai template: bilangan koperasi', $owner->get('owner/templates/list')['data'][0]['cooperative_count'] === 3);

    echo "9. Gateway\n";
    $gateways = $owner->get('owner/gateways/list')['data'];
    check('5 driver + medan', count($gateways['drivers']) === 5 && $gateways['owner_gateway']['driver_code'] === 'toyyibpay' && $gateways['owner_gateway']['secrets_set'] === []);
    $missing = $owner->post('owner/gateways/save-owner', ['driver_code' => 'toyyibpay', 'sandbox' => true, 'credentials' => ['category_code' => 'kpk2026']]);
    check('Rahsia wajib kali pertama → errors.credentials.secret_key', isset($missing['errors']['credentials.secret_key']));
    $ownerGateway = $owner->post('owner/gateways/save-owner', ['driver_code' => 'toyyibpay', 'sandbox' => true,
        'credentials' => ['category_code' => 'kpk2026', 'secret_key' => 'RAHSIA-UJIAN-123']]);
    $raw = json_encode($ownerGateway);
    check('Disimpan: secrets_set, rahsia tidak dipulangkan', $ownerGateway['ok'] && $ownerGateway['data']['secrets_set'] === ['secret_key'] && strpos($raw, 'RAHSIA-UJIAN') === false);
    check('Rahsia disulitkan dalam DB', strpos(settingValue('owner_gateway_secrets_enc'), 'RAHSIA') === false
        && ownerSecret('secret_key') === 'RAHSIA-UJIAN-123');
    $kept = $owner->post('owner/gateways/save-owner', ['driver_code' => 'toyyibpay', 'sandbox' => false, 'credentials' => ['category_code' => 'baru', 'secret_key' => '']]);
    check('Rahsia kosong = kekal; mod live', $kept['ok'] && $kept['data']['sandbox'] === false && ownerSecret('secret_key') === 'RAHSIA-UJIAN-123'
        && settingValue('owner_gateway_config') === '{"category_code":"baru"}');
    check('Tukar driver: rahsia lama tidak dibawa', isset($owner->post('owner/gateways/save-owner', ['driver_code' => 'billplz', 'credentials' => ['collection_id' => 'c1']])['errors']['credentials.api_key']));
    check('Benarkan senangpay untuk vendor', $owner->post('owner/gateways/toggle', ['code' => 'senangpay', 'allowed' => true])['data']['allowed_for_vendors'] === true
        && Database::fetchValue("SELECT is_enabled_for_vendors FROM payment_gateways WHERE code = 'senangpay'") == 1);
    check('Driver tiada → 404', $owner->post('owner/gateways/toggle', ['code' => 'tiada', 'allowed' => true])['ok'] === false);

    echo "10. Darjah\n";
    $grades = $owner->get('owner/grades/list')['data'];
    check('13 darjah, nama Tamil T1', count($grades) === 13 && $grades[7]['names']['ta'] === Lang::fileValue('ta', 'grade.T1'));
    check('Nama Tamil wajib', isset($owner->post('owner/grades/save', ['code' => 'D1', 'names' => ['ms' => 'D1', 'en' => 'Y1', 'zh' => '一'], 'level_type' => 'primary', 'sort_order' => 1])['errors']['names.ta']));
    $grade = $owner->post('owner/grades/save', ['code' => 'D1', 'names' => ['ms' => 'Darjah Satu', 'en' => 'Year One', 'zh' => '一年级（新）', 'ta' => 'ஆண்டு ஒன்று'],
        'level_type' => 'primary', 'sort_order' => 1]);
    check('Nama darjah disimpan dalam translations', $grade['ok'] && $grade['data']['names']['zh'] === '一年级（新）');
    check('Kod darjah tiada → 404', $owner->post('owner/grades/save', ['code' => 'X9', 'names' => [], 'level_type' => 'primary', 'sort_order' => 1])['ok'] === false);

    echo "11. Templat e-mel\n";
    $list = $owner->get('owner/email-templates/list')['data'];
    check('6 kunci, order_ready ada 4 bahasa, invoice_issued en belum diisi', count($list) === 6 && $list[2]['languages']['ta'] !== null && $list[4]['languages']['en'] === null);
    $tpl = $owner->get('owner/email-templates/get', ['template_key' => 'order_ready', 'language_code' => 'ta'])['data'];
    check('get: subjek Tamil + pemegang tempat', strpos($tpl['subject'], '{order_no}') !== false && in_array('{child_name}', $tpl['placeholders'], true));
    $unknown = $owner->post('owner/email-templates/save', ['template_key' => 'order_ready', 'language_code' => 'ta', 'subject' => 'S {order_no}', 'body_html' => '<p>{salah_eja}</p>']);
    check('Pemegang tempat tidak dikenali ditolak', $unknown['ok'] === false && strpos($unknown['error'], '{salah_eja}') !== false);
    $savedTpl = $owner->post('owner/email-templates/save', ['template_key' => 'invoice_issued', 'language_code' => 'en', 'subject' => 'Invoice {invoice_no}', 'body_html' => '<p>{vendor_name}: {total}</p>']);
    check('Templat baharu disimpan', $savedTpl['ok'] && $savedTpl['data']['updated_at'] !== null);
    check('Kunci tiada → 404', $owner->get('owner/email-templates/get', ['template_key' => 'tiada', 'language_code' => 'ms'])['ok'] === false);

    echo "12. Pengumuman\n";
    check('Sasaran "selected" tanpa vendor → errors.vendor_ids', isset($owner->post('owner/announcements/save', ['title' => 'A', 'body' => 'B', 'target' => 'selected', 'vendor_ids' => [999]])['errors']['vendor_ids']));
    $published = $owner->post('owner/announcements/save', ['title' => 'Ujian B3', 'body' => 'Isi', 'target' => 'selected', 'vendor_ids' => [1, 999], 'publish' => true]);
    check('Terbit kepada VENDOR01 sahaja (id tiada diabaikan)', $published['ok'] && $published['data']['vendor_names'] === ['VENDOR01'] && $published['data']['published_at'] !== null);
    check('Sudah terbit tidak boleh disunting', $owner->post('owner/announcements/save', ['id' => $published['data']['id'], 'title' => 'X', 'body' => 'Y', 'target' => 'all'])['ok'] === false);
    $draft = $owner->post('owner/announcements/save', ['title' => 'Draf B3', 'body' => 'Isi', 'target' => 'all', 'publish' => false]);
    check('Draf: published_at null, created_by owner', $draft['ok'] && $draft['data']['published_at'] === null && $draft['data']['created_by'] === 'Pemilik Platform');
    check('Draf boleh dipadam', $owner->post('owner/announcements/delete', ['id' => $draft['data']['id']])['ok']);
    $announcements = $owner->get('owner/announcements/list', ['q' => 'invois']);
    check('Senarai + carian (seed: draf sasaran VENDOR02)', $announcements['data']['total'] === 1 && $announcements['data']['items'][0]['vendor_ids'] === [2]);

    echo "13. Tetapan platform\n";
    $settings = $owner->get('owner/settings/get')['data'];
    check('Tetapan: smtp_password_set false, tiada kata laluan dipulangkan', $settings['smtp_password_set'] === false && !array_key_exists('smtp_password', $settings)
        && (float) $settings['sst_rate'] === 8.0 && $settings['trial_days'] === 14);
    $settings['smtp_host'] = 'smtp.ujian.my';
    check('SST > 100 ditolak', isset($owner->post('owner/settings/save', array_merge($settings, ['sst_rate' => 150]))['errors']['sst_rate']));
    check('E-mel sokongan tidak sah ditolak', isset($owner->post('owner/settings/save', array_merge($settings, ['support_email' => 'bukan-emel']))['errors']['support_email']));
    $savedSettings1 = $owner->post('owner/settings/save', array_merge($settings, ['smtp_password' => 'SmtpRahsia!9', 'sst_rate' => 6, 'trial_days' => 30]));
    check('Simpan: smtp_password_set true, SST 6, trial 30', $savedSettings1['ok'] && $savedSettings1['data']['smtp_password_set'] === true
        && (float) $savedSettings1['data']['sst_rate'] === 6.0 && $savedSettings1['data']['trial_days'] === 30);
    check('Kata laluan SMTP disulitkan', Crypto::decrypt(settingValue('smtp_password_enc')) === 'SmtpRahsia!9'
        && strpos(json_encode($savedSettings1), 'SmtpRahsia') === false);
    $owner->post('owner/settings/save', array_merge($settings, ['smtp_password' => '']));
    check('Kata laluan kosong = kekal', Crypto::decrypt(settingValue('smtp_password_enc')) === 'SmtpRahsia!9');
    check('E-mel ujian masuk email_queue', $owner->post('owner/settings/test-email', ['to' => 'Ujian@Contoh.my'])['ok']
        && Database::fetchValue("SELECT template_key FROM email_queue WHERE id > ? AND to_email = 'ujian@contoh.my'", [$maxEmailId]) === 'test_email');
    check('E-mel ujian tanpa alamat ditolak', isset($owner->post('owner/settings/test-email', ['to' => ''])['errors']['to']));

    echo "14. Log audit\n";
    $audit = $owner->get('owner/audit-logs/list', ['q' => 'vendor.suspend']);
    $row = $audit['data']['items'][0] ?? [];
    check('Gantung direkod: pelaku, kod vendor, sebab, IP', ($row['actor_name'] ?? '') === 'Pemilik Platform' && ($row['vendor_code'] ?? '') === 'VENDOR02'
        && ($row['detail'] ?? '') === 'Invois tertunggak (ujian B3)' && ($row['ip_address'] ?? '') !== '');
    $actions = array_column(Database::fetchAll("SELECT DISTINCT action FROM audit_logs WHERE id > ?", [$maxAuditId]), 'action');
    $expected = ['vendor.suspend', 'vendor.activate', 'vendor.note', 'plan.save', 'plan.tiers_update', 'plan.delete', 'module.save', 'template.save',
        'gateway.owner_update', 'gateway.toggle', 'grade.update', 'email_template.update', 'announcement.publish', 'announcement.delete', 'settings.update'];
    check('Semua tindakan sensitif B3 direkod', count(array_diff($expected, $actions)) === 0);
    check('Tapis actor_type=system → 0', $owner->get('owner/audit-logs/list', ['actor_type' => 'system'])['data']['total'] === 0);
    check('Tarikh tidak sah ditolak', $owner->get('owner/audit-logs/list', ['date_from' => '2026-02-30'])['ok'] === false);
    check('Tapis tarikh hari ini', $owner->get('owner/audit-logs/list', ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')])['data']['total'] > 10);

    echo "15. Halaman owner (sesi sebenar)\n";
    $pages = ['index', 'vendors', 'vendor', 'cooperatives', 'plans', 'coop-pricing', 'modules', 'templates', 'gateways', 'grades', 'email-templates',
        'announcements', 'audit-log', 'settings'];
    $okPages = 0;
    foreach ($pages as $page) {
        $okPages += $owner->page('owner/' . $page . '.php?id=1')['status'] === 200 ? 1 : 0;
    }
    check('14 halaman owner B3 → 200', $okPages === count($pages));
} finally {
    // Pulihkan keadaan asal
    foreach ($savedUsers as $user) {
        Database::execute("UPDATE platform_users SET totp_secret = ?, totp_last_step = ? WHERE id = ?", [$user['totp_secret'], $user['totp_last_step'], $user['id']]);
    }
    Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = 'owner'");
    foreach ($savedCodes as $code) {
        Database::insert(
            "INSERT INTO totp_recovery_codes (user_type, user_id, code_hash, used_at, created_at) VALUES (?, ?, ?, ?, ?)",
            [$code['user_type'], $code['user_id'], $code['code_hash'], $code['used_at'], $code['created_at']]
        );
    }
    foreach ($savedVendors as $vendor) {
        Database::execute("UPDATE vendors SET status = ? WHERE id = ?", [$vendor['status'], $vendor['id']]);
    }
    foreach ($savedSubscriptions as $sub) {
        Database::execute("UPDATE subscriptions SET status = ? WHERE id = ?", [$sub['status'], $sub['id']]);
    }
    foreach ($savedSettings as $setting) {
        Database::execute("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?", [$setting['setting_value'], $setting['setting_key']]);
    }
    foreach ($savedGateways as $gateway) {
        Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = ? WHERE code = ?", [$gateway['is_enabled_for_vendors'], $gateway['code']]);
    }
    foreach ($savedTemplates as $tpl) {
        Database::execute(
            "UPDATE templates SET name = ?, description = ?, price_monthly = ?, price_yearly = ?, is_free = ?, is_active = ? WHERE id = ?",
            [$tpl['name'], $tpl['description'], $tpl['price_monthly'], $tpl['price_yearly'], $tpl['is_free'], $tpl['is_active'], $tpl['id']]
        );
    }
    Database::execute("UPDATE email_templates SET subject = ?, body_html = ? WHERE template_key = 'order_ready' AND language_code = 'ta'", [$savedEmail['subject'], $savedEmail['body_html']]);
    Database::execute("DELETE FROM email_templates WHERE template_key = 'invoice_issued' AND language_code = 'en'");
    Database::execute("DELETE FROM translations WHERE translation_key IN ('grade.D1', 'module.uji_modul', 'module.uji_modul.description')");
    Database::execute("DELETE FROM modules WHERE code = 'uji_modul'");
    Database::execute("DELETE FROM plans WHERE code IN ('UJI_B3', 'NEG')");
    Database::execute("DELETE FROM vendor_notes WHERE id > ?", [$maxNoteId]);
    Database::execute("DELETE FROM vendor_announcements WHERE id > ?", [$maxAnnouncementId]);
    Database::execute("DELETE FROM email_queue WHERE id > ?", [$maxEmailId]);
    clearTestLocks();
    proc_terminate($server);
}

echo "\nKeputusan: $passed lulus, $failed gagal\n";
exit($failed > 0 ? 1 : 0);
