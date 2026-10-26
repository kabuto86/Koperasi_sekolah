SET NAMES utf8mb4;
-- KoperasiKu SaaS — data permulaan (PELAN 8.7: DUA vendor sejak hari pertama)
-- Import selepas schema.sql:
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/seed.sql
--
-- Akaun demo (kata laluan semua: Demo@12345 · PIN POS: 1234) — TUKAR sebelum produksi
--   Owner         owner@koperasiku.my        (2FA disediakan semasa log masuk pertama, B2)
--   Owner staff   staf@koperasiku.my
--   VENDOR01      ahmad@niagasekolah.my (admin) · kasim@niagasekolah.my (juruwang)
--   VENDOR02      meiling@bestari.my (admin) · chong@bestari.my (pengurus Chung Hwa) · kavitha@bestari.my (juruwang)
--   Ibu bapa A    siti@contoh.my / 012-3456701  (anak di SK Taman Melati DAN SJKC Chung Hwa)
--   Ibu bapa B    rajesh@contoh.my / 016-7788990 (anak di SMK Seri Indah)

SET time_zone = '+08:00';

-- ---------------------------------------------------------------------
-- A. Platform
-- ---------------------------------------------------------------------

INSERT INTO languages (code, name, native_name, is_active, is_default, sort_order) VALUES
('ms', 'Malay',   'Bahasa Melayu', 1, 1, 1),
('en', 'English', 'English',       1, 0, 2),
('zh', 'Chinese', '中文',           1, 0, 3),
('ta', 'Tamil',   'தமிழ்',          1, 0, 4);

INSERT INTO grade_levels (code, name_key, level_type, sort_order) VALUES
('PRA', 'grade.PRA', 'preschool', 0),
('D1',  'grade.D1',  'primary',   1),
('D2',  'grade.D2',  'primary',   2),
('D3',  'grade.D3',  'primary',   3),
('D4',  'grade.D4',  'primary',   4),
('D5',  'grade.D5',  'primary',   5),
('D6',  'grade.D6',  'primary',   6),
('T1',  'grade.T1',  'secondary', 7),
('T2',  'grade.T2',  'secondary', 8),
('T3',  'grade.T3',  'secondary', 9),
('T4',  'grade.T4',  'secondary', 10),
('T5',  'grade.T5',  'secondary', 11),
('T6',  'grade.T6',  'secondary', 12);

INSERT INTO platform_users (id, email, password_hash, full_name, role, language, is_active) VALUES
(1, 'owner@koperasiku.my', '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Pemilik Platform', 'owner', 'ms', 1),
(2, 'staf@koperasiku.my',  '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Siti Aminah', 'owner_staff', 'ms', 1);

INSERT INTO platform_settings (setting_key, setting_value) VALUES
('platform_name',       'KoperasiKu'),
('support_email',       'sokongan@koperasiku.my'),
('support_phone',       '03-12345678'),
('smtp_host',           ''),
('smtp_port',           '587'),
('smtp_encryption',     'tls'),
('smtp_username',       ''),
('smtp_password_enc',   ''),
('mail_from_address',   'noreply@koperasiku.my'),
('mail_from_name',      'KoperasiKu'),
('sst_rate',            '8'),
('sst_registration_no', ''),
('trial_days',          '14'),
('grace_days',          '7'),
('default_language',    'ms'),
('owner_gateway_code',  'toyyibpay'),
('owner_gateway_mode',  'sandbox'),
('owner_gateway_config', '{}'),
('owner_gateway_secrets_enc', '');

-- Templat e-mel (owner sunting di panel — B3). Bahasa yang belum diisi jatuh balik ke Bahasa Melayu
INSERT INTO email_templates (template_key, language_code, subject, body_html, created_at) VALUES
('order_new_vendor', 'ms', 'Pesanan baharu {order_no} — {school_name}', '<p>Pesanan baharu <strong>{order_no}</strong> daripada {customer_name}.</p>\n<p>Jumlah: {total}</p>\n<p><a href="{order_url}">Lihat pesanan</a></p>', '2026-10-01 10:00:00'),
('order_new_vendor', 'en', 'New order {order_no} — {school_name}', '<p>New order <strong>{order_no}</strong> from {customer_name}.</p>\n<p>Total: {total}</p>\n<p><a href="{order_url}">View order</a></p>', '2026-10-01 10:00:00'),
('order_receipt', 'ms', 'Resit pesanan {order_no}', '<p>Terima kasih {customer_name}.</p>\n{items_table}\n<p>Jumlah: <strong>{total}</strong></p>\n<p>Kod ambil: {pickup_code}</p>', '2026-10-01 10:00:00'),
('order_receipt', 'en', 'Receipt for order {order_no}', '<p>Thank you {customer_name}.</p>\n{items_table}\n<p>Total: <strong>{total}</strong></p>\n<p>Pickup code: {pickup_code}</p>', '2026-10-01 10:00:00'),
('order_receipt', 'zh', '订单 {order_no} 收据', '<p>谢谢 {customer_name}。</p>\n{items_table}\n<p>总额：<strong>{total}</strong></p>', '2026-10-02 10:00:00'),
('order_ready', 'ms', 'Pesanan {order_no} sedia diambil', '<p>Barang untuk {child_name} ({child_class}) sedia diambil di koperasi {school_name}.</p>\n<p>Waktu operasi: {opening_hours}</p>', '2026-10-01 10:00:00'),
('order_ready', 'en', 'Order {order_no} is ready for collection', '<p>Items for {child_name} ({child_class}) are ready at the {school_name} cooperative.</p>', '2026-10-01 10:00:00'),
('order_ready', 'zh', '订单 {order_no} 可领取', '<p>{child_name}（{child_class}）的物品已可在 {school_name} 合作社领取。</p>', '2026-10-02 10:00:00'),
('order_ready', 'ta', 'ஆர்டர் {order_no} பெறத் தயார்', '<p>{child_name} ({child_class}) பொருட்கள் {school_name} கூட்டுறவில் தயார்.</p>', '2026-10-03 10:00:00'),
('order_collected', 'ms', 'Pesanan {order_no} telah diambil', '<p>Barang telah diambil oleh {collected_by} pada {collected_at}.</p>', '2026-10-01 10:00:00'),
('invoice_issued', 'ms', 'Invois {invoice_no} — KoperasiKu', '<p>Kepada {vendor_name},</p>\n<p>Invois {invoice_no} berjumlah {total} perlu dibayar sebelum {due_date}.</p>\n<p><a href="{pay_url}">Bayar sekarang</a></p>', '2026-10-01 10:00:00'),
('password_reset', 'ms', 'Tetapkan semula kata laluan', '<p>Hai {name},</p>\n<p><a href="{reset_url}">Tetapkan semula kata laluan</a> (sah {expires_minutes} minit).</p>', '2026-10-01 10:00:00'),
('password_reset', 'en', 'Reset your password', '<p>Hi {name},</p>\n<p><a href="{reset_url}">Reset your password</a> (valid for {expires_minutes} minutes).</p>', '2026-10-01 10:00:00');

-- Nama modul ada dalam lang/*.php (module.{code}); keterangan disimpan di sini (owner boleh sunting)
INSERT INTO translations (language_code, translation_key, value) VALUES
('ms', 'module.news.description',            'Pos berita kepada ibu bapa koperasi itu.'),
('en', 'module.news.description',            'Post news to the cooperative''s parents.'),
('ms', 'module.clearance.description',       'Harga khas dengan tempoh dan lencana Clearance.'),
('en', 'module.clearance.description',       'Special prices with a period and a Clearance badge.'),
('ms', 'module.calendar.description',        'Tempahan ditutup automatik pada hari cuti.'),
('en', 'module.calendar.description',        'Orders close automatically on holidays.'),
('ms', 'module.school_opening.description',  'Musim pre-order kit dan slot ambil barang.'),
('en', 'module.school_opening.description',  'Kit pre-order season and pickup slots.'),
('ms', 'module.mobile_app.description',      'Ibu bapa guna apps Flutter dengan notifikasi push.'),
('en', 'module.mobile_app.description',      'Parents use the Flutter app with push notifications.'),
('ms', 'module.whatsapp_sms.description',    'Mesej "sedia diambil" dan "telah diambil".'),
('en', 'module.whatsapp_sms.description',    '"Ready" and "collected" messages.'),
('ms', 'module.advanced_report.description', 'Perbandingan tahun dan analisis kit.'),
('en', 'module.advanced_report.description', 'Year comparison and kit analysis.'),
('ms', 'module.membership.description',      'Ahli, syer dan dividen.'),
('en', 'module.membership.description',      'Members, shares and dividends.');

-- ---------------------------------------------------------------------
-- B. Langganan & bil
-- ---------------------------------------------------------------------

INSERT INTO plans (id, code, name, price_monthly, price_yearly, max_cooperatives, max_products, max_staff, features_json, is_active, sort_order) VALUES
(1, 'ASAS',    'Asas',    4900,  49000,  2,    300,  3,    '["Store ibu bapa","Kit sekolah","Serah barang"]', 1, 1),
(2, 'PRO',     'Pro',     9900,  99000,  5,    2000, 10,   '["Semua dalam Asas","POS kaunter","Laporan penuh"]', 1, 2),
(3, 'PREMIUM', 'Premium', 19900, 199000, NULL, NULL, NULL, '["Semua dalam Pro","Koperasi tanpa had","Sokongan keutamaan"]', 1, 3);

INSERT INTO plan_coop_tiers (id, plan_id, from_coop, to_coop, price_monthly, price_yearly) VALUES
(1, 1, 2, NULL, 4900, 49000),
(2, 2, 2, 3,    7900, 79000),
(3, 2, 4, NULL, 5900, 59000),
(4, 3, 2, NULL, 9900, 99000);

INSERT INTO modules (id, code, name_key, description_key, price_monthly, price_yearly, is_core, is_active, sort_order) VALUES
(1, 'news',            'module.news',            'module.news.description',            1500, 15000, 0, 1, 1),
(2, 'clearance',       'module.clearance',       'module.clearance.description',       1000, 10000, 0, 1, 2),
(3, 'calendar',        'module.calendar',        'module.calendar.description',        500,  5000,  0, 1, 3),
(4, 'school_opening',  'module.school_opening',  'module.school_opening.description',  2000, 20000, 0, 1, 4),
(5, 'mobile_app',      'module.mobile_app',      'module.mobile_app.description',      3000, 30000, 0, 1, 5),
(6, 'whatsapp_sms',    'module.whatsapp_sms',    'module.whatsapp_sms.description',    2500, 25000, 0, 1, 6),
(7, 'advanced_report', 'module.advanced_report', 'module.advanced_report.description', 2000, 20000, 0, 1, 7),
(8, 'membership',      'module.membership',      'module.membership.description',      3000, 30000, 0, 0, 8);

INSERT INTO templates (id, code, name, description, folder, preview_image, price_monthly, price_yearly, is_free, is_active) VALUES
(1, 'template1', 'Template 1 — Asas',   'Ringkas, mobile-first, navbar bawah.', 'templates/template1', NULL, 0,    0,     1, 1),
(2, 'template2', 'Template 2 — Ceria',  'Warna ceria untuk sekolah rendah.',    'templates/template2', NULL, 1000, 10000, 0, 1),
(3, 'template3', 'Template 3 — Klasik', 'Rupa formal untuk sekolah menengah.',  'templates/template3', NULL, 1500, 15000, 0, 0);

INSERT INTO vendors (id, code, company_name, registration_no, contact_name, phone, email, address, status, trial_ends_at, created_at) VALUES
(1, 'VENDOR01', 'Niaga Sekolah Sdn Bhd',       '202301012345', 'Ahmad bin Ismail', '012-3456789', 'ahmad@niagasekolah.my', 'No. 12, Jalan Melati 3, 53100 Gombak, Selangor', 'active', NULL, '2026-01-05 09:00:00'),
(2, 'VENDOR02', 'Koperasi Bestari Enterprise', 'JM0876543-T',  'Lim Mei Ling',     '017-2223344', 'meiling@bestari.my',    '8, Jalan Indah 2, 81200 Johor Bahru, Johor',     'active', NULL, '2026-02-11 10:30:00');

INSERT INTO subscriptions (id, vendor_id, plan_id, billing_cycle, status, started_at, current_period_start, current_period_end) VALUES
(1, 1, 2, 'yearly',  'active', '2026-01-05', '2026-01-05', '2027-01-04'),
(2, 2, 1, 'monthly', 'active', '2026-02-11', '2026-10-11', '2026-11-10');

-- Nota dalaman owner & pengumuman vendor (B3)
INSERT INTO vendor_notes (id, vendor_id, body, created_by, created_at) VALUES
(1, 1, 'Vendor perintis — dua kali hubungi sokongan tentang import produk.', 1, '2026-03-02 11:00:00'),
(2, 2, 'Minta pakej tahunan selepas koperasi ketiga dibuka.', 2, '2026-09-18 15:30:00');

INSERT INTO vendor_announcements (id, title, body, target, published_at, created_by, created_at) VALUES
(1, 'Penyelenggaraan sistem 1 November', 'Sistem akan ditutup dari 1:00 hingga 3:00 pagi untuk penyelenggaraan.', 'all', '2026-10-22 09:00:00', 1, '2026-10-22 08:50:00'),
(2, 'Modul Pembukaan sekolah kini tersedia', 'Langgan modul ini untuk musim pre-order kit 2027.', 'all', '2026-10-10 10:00:00', 1, '2026-10-10 09:00:00'),
(3, 'Peringatan invois tertunggak', 'Sila jelaskan invois tertunggak untuk mengelakkan penggantungan.', 'selected', NULL, 2, '2026-10-23 15:00:00');

INSERT INTO vendor_announcement_targets (announcement_id, vendor_id) VALUES
(3, 2);

-- ---------------------------------------------------------------------
-- C. Vendor & koperasi
-- ---------------------------------------------------------------------

INSERT INTO cooperatives (id, vendor_id, slug, school_name, school_code, school_type, state, district, address, phone, template_id, default_language, accept_cash, accept_online, status, created_at) VALUES
(1, 1, 'sk-taman-melati', 'SK Taman Melati', 'BBA1234', 'SK',   'Selangor', 'Gombak',      'Jalan Melati 5, Taman Melati, 53100 Kuala Lumpur', '03-41081234', 1, 'ms', 1, 1, 'active', '2026-01-06 10:00:00'),
(2, 2, 'sjkc-chung-hwa',  'SJKC Chung Hwa',  'JBC5678', 'SJKC', 'Johor',    'Johor Bahru', 'Jalan Tebrau, 80250 Johor Bahru, Johor',           '07-3325678', 1, 'zh', 1, 0, 'active', '2026-02-12 10:00:00'),
(3, 2, 'smk-seri-indah',  'SMK Seri Indah',  'JEA9012', 'SMK',  'Johor',    'Johor Bahru', 'Jalan Seri Indah 1, 81200 Johor Bahru, Johor',     '07-2369012', 1, 'ms', 1, 0, 'active', '2026-02-14 10:00:00');

-- Koperasi ke-2 VENDOR02 dicaj ikut plan_coop_tiers (pelan Asas, peringkat 2+)
INSERT INTO subscription_items (id, vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at) VALUES
(1, 1, 1, 'module',      1, 1, 15000, '2026-01-06'),
(2, 1, 1, 'module',      1, 3, 5000,  '2026-01-06'),
(3, 1, 1, 'module',      1, 4, 20000, '2026-01-06'),
(4, 1, 1, 'module',      1, 6, 25000, '2026-01-06'),
(5, 2, 2, 'cooperative', 3, NULL, 4900, '2026-02-14'),
(6, 2, 2, 'module',      3, 2, 1000,  '2026-02-14');

INSERT INTO cooperative_modules (vendor_id, cooperative_id, module_id, status, started_at, ends_at) VALUES
(1, 1, 1, 'active', '2026-01-06', '2027-01-04'),
(1, 1, 3, 'active', '2026-01-06', '2027-01-04'),
(1, 1, 4, 'active', '2026-01-06', '2027-01-04'),
(1, 1, 6, 'active', '2026-01-06', '2027-01-04'),
(2, 3, 2, 'active', '2026-02-14', '2026-11-10');

INSERT INTO vendor_users (id, vendor_id, email, password_hash, full_name, phone, role, pin_hash, language, is_active) VALUES
(1, 1, 'ahmad@niagasekolah.my', '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Ahmad bin Ismail', '012-3456789', 'vendor_admin', '$argon2id$v=19$m=19456,t=2,p=1$NUpHQm9nZ3Zjbi4yNGVDSw$JrKzHMVwj/Nuc3AfilNztiI4V10rIWAg83Jnph1Ksl4', 'ms', 1),
(2, 1, 'kasim@niagasekolah.my', '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Kasim bin Osman',  '013-4567890', 'cashier',      '$argon2id$v=19$m=19456,t=2,p=1$NUpHQm9nZ3Zjbi4yNGVDSw$JrKzHMVwj/Nuc3AfilNztiI4V10rIWAg83Jnph1Ksl4', 'ms', 1),
(3, 2, 'meiling@bestari.my',    '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Lim Mei Ling',     '017-2223344', 'vendor_admin', '$argon2id$v=19$m=19456,t=2,p=1$NUpHQm9nZ3Zjbi4yNGVDSw$JrKzHMVwj/Nuc3AfilNztiI4V10rIWAg83Jnph1Ksl4', 'zh', 1),
(4, 2, 'chong@bestari.my',      '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Chong Wei Han',    '012-7001122', 'coop_manager', NULL, 'zh', 1),
(5, 2, 'kavitha@bestari.my',    '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'Kavitha a/p Raman', '016-3004455', 'cashier',     '$argon2id$v=19$m=19456,t=2,p=1$NUpHQm9nZ3Zjbi4yNGVDSw$JrKzHMVwj/Nuc3AfilNztiI4V10rIWAg83Jnph1Ksl4', 'ta', 1);

-- vendor_admin = semua koperasi (tidak perlu baris); lain-lain ikut senarai
INSERT INTO vendor_user_cooperatives (vendor_id, vendor_user_id, cooperative_id) VALUES
(1, 2, 1),
(2, 4, 2),
(2, 5, 2),
(2, 5, 3);

INSERT INTO cooperative_settings (vendor_id, cooperative_id, setting_key, setting_value) VALUES
(1, 1, 'alert_emails',               '["ahmad@niagasekolah.my"]'),
(1, 1, 'pickup_methods',             'A,B,C,D'),
(1, 1, 'pickup_code_required_above', '10000'),
(1, 1, 'cash_order_expiry_days',     '3'),
(1, 1, 'opening_hours',              '{"mon":{"open":true,"from":"07:30","to":"13:00"},"tue":{"open":true,"from":"07:30","to":"13:00"},"wed":{"open":true,"from":"07:30","to":"13:00"},"thu":{"open":true,"from":"07:30","to":"13:00"},"fri":{"open":true,"from":"07:30","to":"12:00"},"sat":{"open":false,"from":"","to":""},"sun":{"open":false,"from":"","to":""}}'),
(1, 1, 'receipt_header',             'Koperasi SK Taman Melati'),
(1, 1, 'receipt_footer',             'Terima kasih. Barang yang dibeli tidak boleh ditukar tanpa resit.'),
(1, 1, 'receipt_show_logo',          '1'),
(1, 1, 'receipt_paper',              '58'),
(1, 1, 'tax_rate',                   '0'),
(1, 1, 'low_stock_default',          '5'),
(2, 2, 'alert_emails',               '["meiling@bestari.my","chong@bestari.my"]'),
(2, 2, 'pickup_methods',             'A,B,D'),
(2, 2, 'pickup_code_required_above', '0'),
(2, 2, 'cash_order_expiry_days',     '3'),
(2, 2, 'opening_hours',              '{"mon":{"open":true,"from":"07:00","to":"12:30"},"tue":{"open":true,"from":"07:00","to":"12:30"},"wed":{"open":true,"from":"07:00","to":"12:30"},"thu":{"open":true,"from":"07:00","to":"12:30"},"fri":{"open":true,"from":"07:00","to":"12:00"},"sat":{"open":false,"from":"","to":""},"sun":{"open":false,"from":"","to":""}}'),
(2, 2, 'receipt_header',             '中华国民型华文小学合作社'),
(2, 2, 'receipt_footer',             '谢谢光临 · Terima kasih'),
(2, 2, 'receipt_show_logo',          '1'),
(2, 2, 'receipt_paper',              '80'),
(2, 2, 'tax_rate',                   '0'),
(2, 2, 'low_stock_default',          '5'),
(2, 3, 'alert_emails',               '["meiling@bestari.my"]'),
(2, 3, 'pickup_methods',             'A,B,C'),
(2, 3, 'pickup_code_required_above', '0'),
(2, 3, 'cash_order_expiry_days',     '5'),
(2, 3, 'opening_hours',              '{"mon":{"open":true,"from":"07:00","to":"14:00"},"tue":{"open":true,"from":"07:00","to":"14:00"},"wed":{"open":true,"from":"07:00","to":"14:00"},"thu":{"open":true,"from":"07:00","to":"14:00"},"fri":{"open":true,"from":"07:00","to":"12:00"},"sat":{"open":false,"from":"","to":""},"sun":{"open":false,"from":"","to":""}}'),
(2, 3, 'receipt_header',             'Koperasi SMK Seri Indah'),
(2, 3, 'receipt_footer',             'Terima kasih.'),
(2, 3, 'receipt_show_logo',          '0'),
(2, 3, 'receipt_paper',              '80'),
(2, 3, 'tax_rate',                   '0'),
(2, 3, 'low_stock_default',          '10');

INSERT INTO school_classes (id, vendor_id, cooperative_id, grade_level_code, class_name, sort_order) VALUES
(1, 1, 1, 'D1', 'Bestari', 1),
(2, 1, 1, 'D1', 'Cemerlang', 2),
(3, 1, 1, 'D4', 'Bestari', 1),
(4, 1, 1, 'D4', 'Cemerlang', 2),
(5, 2, 2, 'D1', '1A', 1),
(6, 2, 2, 'D2', '2A', 1),
(7, 2, 3, 'T1', 'Amanah', 1),
(8, 2, 3, 'T2', 'Amanah', 1),
(9, 2, 3, 'T2', 'Bijak', 2);

-- ---------------------------------------------------------------------
-- D. Katalog & inventori
-- ---------------------------------------------------------------------

INSERT INTO categories (id, vendor_id, cooperative_id, name, sort_order, is_active) VALUES
(1, 1, 1, 'Buku latihan',  1, 1),
(2, 1, 1, 'Alat tulis',    2, 1),
(3, 1, 1, 'Pakaian',       3, 1),
(4, 2, 2, 'Buku latihan',  1, 1),
(5, 2, 2, 'Alat tulis',    2, 1),
(6, 2, 3, 'Buku latihan',  1, 1),
(7, 2, 3, 'Pakaian',       2, 1),
(8, 2, 3, 'Alat tulis',    3, 1);

-- Barcode 9555000000017 & 9555000000024 sengaja sama di koperasi 1 dan 2 (UNIQUE per koperasi sahaja)
INSERT INTO products (id, vendor_id, cooperative_id, category_id, name, description, sku, barcode, price, cost_price, unit, max_per_order, has_variants, is_active, sort_order) VALUES
(1,  1, 1, 1, 'Buku Latihan Garis 80 muka',    'Buku latihan bergaris, kulit nipis.',     'BL-080',  '9555000000017', 180,  110,  'buah',  NULL, 0, 1, 1),
(2,  1, 1, 2, 'Pensel 2B (kotak 12)',           NULL,                                       'PS-2B12', '9555000000024', 650,  420,  'kotak', 5,    0, 1, 2),
(3,  1, 1, 2, 'Pemadam Putih',                  NULL,                                       'PM-001',  '9555000000031', 100,  50,   'unit',  NULL, 0, 1, 3),
(4,  1, 1, 3, 'Baju Sekolah Putih Lelaki',      'Baju kemeja putih lengan pendek.',         'BJ-PL',   NULL,            2500, 1600, 'helai', 3,    1, 1, 4),
(5,  1, 1, 1, 'Buku Tulisan Jawi',              NULL,                                       'BL-JAWI', '9555000000048', 220,  140,  'buah',  NULL, 0, 1, 5),
(6,  2, 2, 4, '练习簿 Buku Latihan 80 muka',     '中文练习簿，80页。',                         'ZH-BL80', '9555000000017', 190,  120,  'buah',  NULL, 0, 1, 1),
(7,  2, 2, 5, 'Pensel 2B (kotak 12)',           NULL,                                       'ZH-PS2B', '9555000000024', 680,  430,  'kotak', 5,    0, 1, 2),
(8,  2, 2, 4, 'Buku Latihan Tulisan Cina',      '汉字书写练习。',                              'ZH-HZ01', '9555000000123', 350,  210,  'buah',  NULL, 0, 1, 3),
(9,  2, 3, 6, 'Buku Latihan Kotak 120 muka',    NULL,                                       'SI-BK120','9555000000208', 320,  200,  'buah',  NULL, 0, 1, 1),
(10, 2, 3, 7, 'Seluar Sekolah Hijau',           'Seluar panjang hijau tua.',                'SI-SLR',  NULL,            4500, 3000, 'helai', 2,    1, 1, 2),
(11, 2, 3, 8, 'Kalkulator Saintifik',           'Dibenarkan untuk peperiksaan SPM.',        'SI-KAL',  '9555000000215', 4990, 3500, 'unit',  1,    0, 1, 3);

-- Nama produk Cina & Tamil (uji utf8mb4)
INSERT INTO product_translations (vendor_id, product_id, language_code, name, description) VALUES
(2, 6,  'zh', '练习簿（80页）',              '中文练习簿，80页。'),
(2, 6,  'en', 'Exercise Book 80 pages',     NULL),
(2, 7,  'zh', '2B铅笔（12支装）',            NULL),
(2, 8,  'zh', '汉字书写练习簿',              '汉字书写练习。'),
(2, 8,  'ta', 'சீன எழுத்துப் பயிற்சி புத்தகம்', NULL),
(1, 1,  'ta', 'பயிற்சி புத்தகம் 80 பக்கம்',   NULL),
(1, 1,  'zh', '横线练习簿（80页）',           NULL),
(2, 11, 'ta', 'அறிவியல் கணிப்பான்',           NULL);

INSERT INTO product_variants (id, vendor_id, cooperative_id, product_id, label, sku, barcode, price_delta, is_active) VALUES
(1, 1, 1, 4,  'Saiz S', 'BJ-PL-S', '9555000000055', 0,   1),
(2, 1, 1, 4,  'Saiz M', 'BJ-PL-M', '9555000000062', 0,   1),
(3, 1, 1, 4,  'Saiz L', 'BJ-PL-L', '9555000000079', 300, 1),
(4, 2, 3, 10, 'Saiz 28', 'SI-SLR-28', '9555000000222', 0,   1),
(5, 2, 3, 10, 'Saiz 30', 'SI-SLR-30', '9555000000239', 0,   1),
(6, 2, 3, 10, 'Saiz 32', 'SI-SLR-32', '9555000000246', 500, 1);

INSERT INTO product_grade_levels (vendor_id, product_id, grade_level_code) VALUES
(1, 1, 'D1'), (1, 1, 'D4'),
(1, 5, 'D1'), (1, 5, 'D4'),
(2, 8, 'D1'), (2, 8, 'D2'),
(2, 11, 'T2');

INSERT INTO stock_levels (vendor_id, cooperative_id, product_id, variant_id, qty_on_hand, qty_reserved, min_stock) VALUES
(1, 1, 1, 0, 500, 0, 50),
(1, 1, 2, 0, 120, 0, 20),
(1, 1, 3, 0, 300, 0, 30),
(1, 1, 4, 1, 40,  0, 5),
(1, 1, 4, 2, 60,  0, 5),
(1, 1, 4, 3, 4,   0, 5),
(1, 1, 5, 0, 200, 0, 20),
(2, 2, 6, 0, 400, 0, 40),
(2, 2, 7, 0, 90,  0, 15),
(2, 2, 8, 0, 3,   0, 10),
(2, 3, 9, 0, 250, 0, 30),
(2, 3, 10, 4, 30, 0, 5),
(2, 3, 10, 5, 25, 0, 5),
(2, 3, 10, 6, 10, 0, 5),
(2, 3, 11, 0, 15, 0, 5);

INSERT INTO stock_movements (vendor_id, cooperative_id, product_id, variant_id, type, qty_change, balance_after, ref_type, ref_id, vendor_user_id, note) VALUES
(1, 1, 1, 0, 'restock', 500, 500, NULL, NULL, 1, 'Stok permulaan'),
(1, 1, 2, 0, 'restock', 120, 120, NULL, NULL, 1, 'Stok permulaan'),
(1, 1, 3, 0, 'restock', 300, 300, NULL, NULL, 1, 'Stok permulaan'),
(1, 1, 4, 1, 'restock', 40,  40,  NULL, NULL, 1, 'Stok permulaan'),
(1, 1, 4, 2, 'restock', 60,  60,  NULL, NULL, 1, 'Stok permulaan'),
(1, 1, 4, 3, 'restock', 4,   4,   NULL, NULL, 1, 'Stok permulaan'),
(1, 1, 5, 0, 'restock', 200, 200, NULL, NULL, 1, 'Stok permulaan'),
(2, 2, 6, 0, 'restock', 400, 400, NULL, NULL, 3, 'Stok permulaan'),
(2, 2, 7, 0, 'restock', 90,  90,  NULL, NULL, 3, 'Stok permulaan'),
(2, 2, 8, 0, 'restock', 3,   3,   NULL, NULL, 3, 'Stok permulaan'),
(2, 3, 9, 0, 'restock', 250, 250, NULL, NULL, 3, 'Stok permulaan'),
(2, 3, 10, 4, 'restock', 30, 30,  NULL, NULL, 3, 'Stok permulaan'),
(2, 3, 10, 5, 'restock', 25, 25,  NULL, NULL, 3, 'Stok permulaan'),
(2, 3, 10, 6, 'restock', 10, 10,  NULL, NULL, 3, 'Stok permulaan'),
(2, 3, 11, 0, 'restock', 15, 15,  NULL, NULL, 3, 'Stok permulaan');

INSERT INTO suppliers (id, vendor_id, name, contact_name, phone, email, address, is_active) VALUES
(1, 1, 'Syarikat Alat Tulis Gombak', 'Encik Rahim', '03-61881122', 'jualan@alatulisgombak.my', 'Batu Caves, Selangor', 1),
(2, 2, '新山文具批发 JB Stationery',  'Mr. Tan',     '07-3551234',  'order@jbstationery.my',    'Johor Bahru, Johor',   1);

-- Contoh PO & stock take (B4): PO dipesan belum diterima; stock take selesai tanpa perbezaan (baki kekal sama)
INSERT INTO purchase_orders (id, vendor_id, cooperative_id, supplier_id, po_no, status, ordered_at, received_at, total, note, created_by, created_at) VALUES
(1, 1, 1, 1, 'PO-2026-0001', 'ordered', '2026-10-20 09:30:00', NULL, 25000, 'Stok tambahan sesi 2027', 1, '2026-10-20 09:00:00');

INSERT INTO purchase_order_items (vendor_id, purchase_order_id, product_id, variant_id, qty_ordered, qty_received, unit_cost) VALUES
(1, 1, 2, 0, 50, 0, 420),
(1, 1, 3, 0, 40, 0, 100);

INSERT INTO stock_takes (id, vendor_id, cooperative_id, status, started_by, started_at, completed_at) VALUES
(1, 1, 1, 'completed', 1, '2026-07-01 08:00:00', '2026-07-01 12:30:00');

INSERT INTO stock_take_items (vendor_id, stock_take_id, product_id, variant_id, qty_system, qty_counted, variance) VALUES
(1, 1, 1, 0, 500, 500, 0),
(1, 1, 2, 0, 120, 120, 0),
(1, 1, 3, 0, 300, 300, 0);

-- ---------------------------------------------------------------------
-- E. Kit keperluan sekolah (tahun persekolahan 2027)
-- ---------------------------------------------------------------------

INSERT INTO school_kits (id, vendor_id, cooperative_id, grade_level_code, school_year, name, note, is_published) VALUES
(1, 1, 1, 'D1', 2027, 'Kit Darjah 1 — 2027', 'Senarai keperluan rasmi sekolah.', 1),
(2, 1, 1, 'D4', 2027, 'Kit Darjah 4 — 2027', NULL, 1),
(3, 2, 2, 'D1', 2027, '一年级用品 Kit Darjah 1', '请家长检查清单。', 1),
(4, 2, 3, 'T2', 2027, 'Kit Tingkatan 2 — 2027', NULL, 0);

INSERT INTO school_kit_items (vendor_id, school_kit_id, product_id, variant_id, qty, is_mandatory, note, sort_order) VALUES
(1, 1, 1, NULL, 6, 1, NULL, 1),
(1, 1, 2, NULL, 1, 1, NULL, 2),
(1, 1, 3, NULL, 2, 0, NULL, 3),
(1, 1, 4, NULL, 2, 1, 'Pilih saiz', 4),
(1, 2, 1, NULL, 8, 1, NULL, 1),
(1, 2, 5, NULL, 2, 1, NULL, 2),
(1, 2, 2, NULL, 1, 0, NULL, 3),
(2, 3, 6, NULL, 6, 1, NULL, 1),
(2, 3, 8, NULL, 3, 1, NULL, 2),
(2, 3, 7, NULL, 1, 0, NULL, 3),
(2, 4, 9, NULL, 5, 1, NULL, 1),
(2, 4, 10, NULL, 1, 1, 'Pilih saiz', 2),
(2, 4, 11, NULL, 1, 0, NULL, 3);

-- ---------------------------------------------------------------------
-- F. Pembeli — ibu bapa A ada anak di DUA sekolah (dua vendor berlainan)
-- ---------------------------------------------------------------------

INSERT INTO customers (id, full_name, phone, email, password_hash, relationship, language, email_verified_at, pdpa_consent_at, is_active) VALUES
(1, 'Siti Nurhaliza binti Ahmad', '012-3456701', 'siti@contoh.my',   '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'mother', 'ms', '2026-10-01 09:00:00', '2026-10-01 09:00:00', 1),
(2, 'Rajesh a/l Kumar',           '016-7788990', 'rajesh@contoh.my', '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw', 'father', 'ta', '2026-10-02 09:00:00', '2026-10-02 09:00:00', 1);

INSERT INTO children (id, customer_id, cooperative_id, vendor_id, full_name, grade_level_code, school_class_id, class_name, school_year, is_active) VALUES
(1, 1, 1, 1, 'Aisyah binti Faizal', 'D4', 3,    'Bestari', 2026, 1),
(2, 1, 1, 1, 'Adam bin Faizal',     'D1', 1,    'Bestari', 2027, 1),
(3, 1, 2, 2, 'Faris bin Faizal',    'D2', 6,    '2A',      2026, 1),
(4, 2, 3, 2, 'Arjun a/l Rajesh',    'T2', 8,    'Amanah',  2026, 1);

-- ---------------------------------------------------------------------
-- G. Gateway pembayaran (driver; kredensial vendor diisi melalui panel)
-- ---------------------------------------------------------------------

INSERT INTO payment_gateways (code, name, config_fields_json, supports_refund, is_enabled_for_vendors, is_enabled_for_platform) VALUES
('toyyibpay', 'ToyyibPay', '[{"name":"secret_key","secret":true},{"name":"category_code","secret":false}]', 0, 1, 1),
('billplz',   'Billplz',   '[{"name":"api_key","secret":true},{"name":"collection_id","secret":false},{"name":"x_signature","secret":true}]', 0, 1, 1),
('senangpay', 'SenangPay', '[{"name":"merchant_id","secret":false},{"name":"secret_key","secret":true}]', 0, 0, 0),
('ipay88',    'iPay88',    '[{"name":"merchant_code","secret":false},{"name":"merchant_key","secret":true}]', 0, 0, 0),
('stripe',    'Stripe',    '[{"name":"publishable_key","secret":false},{"name":"secret_key","secret":true},{"name":"webhook_secret","secret":true}]', 1, 0, 0);

-- ---------------------------------------------------------------------
-- H. Modul add-on — contoh kecil
-- ---------------------------------------------------------------------

INSERT INTO news_posts (id, vendor_id, cooperative_id, title, body, published_at, send_email, is_active) VALUES
(1, 1, 1, 'Jualan kit sekolah 2027 dibuka', 'Ibu bapa boleh menempah kit Darjah 1 dan Darjah 4 mulai hari ini.', '2026-10-20 08:00:00', 0, 1);

INSERT INTO calendar_events (id, vendor_id, cooperative_id, title, event_type, starts_on, ends_on, blocks_orders) VALUES
(1, 1, 1, 'Cuti akhir tahun', 'school_holiday', '2026-12-19', '2027-01-10', 0),
(2, 1, 1, 'Koperasi tutup — stock take', 'coop_closed', '2026-12-21', '2026-12-22', 1);

INSERT INTO clearance_sales (id, vendor_id, cooperative_id, name, starts_at, ends_at, is_active) VALUES
(1, 2, 3, 'Jualan murah hujung tahun', '2026-10-20 00:00:00', '2026-11-30 23:59:59', 1);

INSERT INTO clearance_items (vendor_id, clearance_sale_id, product_id, variant_id, sale_price, qty_limit) VALUES
(2, 1, 9, 0, 250, 20);
