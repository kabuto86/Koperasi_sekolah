SET NAMES utf8mb4;
-- B13 — Modul add-on: mesej WhatsApp/SMS dirujuk kepada pesanan (peringatan tunai sekali sahaja setiap pesanan).
-- Pemasangan baharu: schema.sql sudah mengandungi perubahan ini.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/006-notification-order.sql

ALTER TABLE notification_queue
    ADD COLUMN order_id INT UNSIGNED NULL COMMENT 'B13: pesanan berkaitan (order_ready, order_collected, cash_reminder)' AFTER cooperative_id,
    ADD KEY idx_notifications_order (vendor_id, order_id, event);
