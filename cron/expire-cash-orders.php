<?php
// Luputkan pesanan tunai yang tidak diambil (expires_at lepas) dan lepaskan tahanan stok (PELAN 6, 16 perkara 4).
// B13: peringatan WhatsApp/SMS (modul whatsapp_sms) untuk pesanan tunai yang luput dalam 24 jam — sekali setiap pesanan.
// CLI sahaja — jalankan setiap jam:
//   0 * * * * php /laluan/Koperasi_sekolah/cron/expire-cash-orders.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/core/bootstrap.php';

$count = (new ExpireCashOrdersJob())->run();
echo 'Pesanan tunai diluputkan: ' . $count . "\n";

$reminders = (new QueueCashRemindersJob())->run();
echo 'Peringatan tunai (WhatsApp/SMS) dimasukkan ke baris gilir: ' . $reminders . "\n";
