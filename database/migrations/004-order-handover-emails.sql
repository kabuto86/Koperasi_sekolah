SET NAMES utf8mb4;
-- B9 — Pesanan & serah barang: templat e-mel "telah diambil" (en/zh/ta + nama anak) dan "bayaran diterima" (order_paid, 4 bahasa).
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/004-order-handover-emails.sql
-- Pemasangan baharu: seed.sql sudah mengandungi perubahan ini. Tiada perubahan jadual (schema.sql tidak berubah).

UPDATE email_templates
   SET body_html = '<p>Barang untuk {child_name} telah diambil oleh <strong>{collected_by}</strong> pada {collected_at}.</p>'
 WHERE template_key = 'order_collected' AND language_code = 'ms' AND body_html NOT LIKE '%{child_name}%';

INSERT IGNORE INTO email_templates (template_key, language_code, subject, body_html, created_at) VALUES
('order_collected', 'en', 'Order {order_no} has been collected', '<p>Items for {child_name} were collected by <strong>{collected_by}</strong> at {collected_at}.</p>', '2026-10-28 10:00:00'),
('order_collected', 'zh', '订单 {order_no} 已领取', '<p>{child_name} 的物品已由 <strong>{collected_by}</strong> 于 {collected_at} 领取。</p>', '2026-10-28 10:00:00'),
('order_collected', 'ta', 'ஆர்டர் {order_no} பெறப்பட்டது', '<p>{child_name} பொருட்களை <strong>{collected_by}</strong> {collected_at} அன்று பெற்றார்.</p>', '2026-10-28 10:00:00'),
('order_paid', 'ms', 'Bayaran diterima — pesanan {order_no}', '<p>Terima kasih {customer_name}.</p>\n<p>Bayaran <strong>{total}</strong> untuk pesanan {order_no} telah diterima (rujukan {reference}).</p>', '2026-10-28 10:00:00'),
('order_paid', 'en', 'Payment received — order {order_no}', '<p>Thank you {customer_name}.</p>\n<p>Your payment of <strong>{total}</strong> for order {order_no} has been received (reference {reference}).</p>', '2026-10-28 10:00:00'),
('order_paid', 'zh', '已收款 — 订单 {order_no}', '<p>谢谢 {customer_name}。</p>\n<p>订单 {order_no} 的付款 <strong>{total}</strong> 已收到（参考号 {reference}）。</p>', '2026-10-28 10:00:00'),
('order_paid', 'ta', 'கட்டணம் பெறப்பட்டது — ஆர்டர் {order_no}', '<p>நன்றி {customer_name}.</p>\n<p>ஆர்டர் {order_no} க்கான <strong>{total}</strong> கட்டணம் பெறப்பட்டது (குறிப்பு {reference}).</p>', '2026-10-28 10:00:00');
