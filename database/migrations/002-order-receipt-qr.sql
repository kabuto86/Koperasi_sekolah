SET NAMES utf8mb4;
-- B7 — Checkout & pesanan: resit ibu bapa memaparkan QR ambil barang ({pickup_qr}) dan pautan pesanan ({order_url}).
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/002-order-receipt-qr.sql
-- Pemasangan baharu: seed.sql sudah mengandungi perubahan ini. Tiada perubahan jadual (schema.sql tidak berubah).

UPDATE email_templates
   SET body_html = CONCAT(body_html, '\n<p>{pickup_qr}</p>')
 WHERE template_key = 'order_receipt' AND body_html NOT LIKE '%{pickup_qr}%';

UPDATE email_templates
   SET body_html = CONCAT(body_html, '\n<p><a href="{order_url}">{order_url}</a></p>')
 WHERE template_key = 'order_receipt' AND body_html NOT LIKE '%{order_url}%';
