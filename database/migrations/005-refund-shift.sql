SET NAMES utf8mb4;
-- B11 — Refund: rujukan syif POS (refund tunai ditolak dari jangkaan laci), rujukan refund gateway, no. refund ikut no. pesanan.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/005-refund-shift.sql
-- Pemasangan baharu: schema.sql sudah mengandungi perubahan ini.

ALTER TABLE refunds
    MODIFY COLUMN refund_no VARCHAR(40) NOT NULL COMMENT 'RF-{order_no}-{n}',
    ADD COLUMN gateway_ref VARCHAR(100) NULL COMMENT 'id refund di gateway (method gateway)' AFTER payment_id,
    ADD COLUMN pos_shift_id INT UNSIGNED NULL COMMENT 'syif POS terbuka pengguna kaunter (refund tunai ditolak dari laci)' AFTER vendor_user_id,
    ADD KEY idx_refunds_shift (vendor_id, pos_shift_id),
    ADD CONSTRAINT fk_refunds_shift FOREIGN KEY (pos_shift_id) REFERENCES pos_shifts (id);
