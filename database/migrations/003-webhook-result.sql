SET NAMES utf8mb4;
-- B8 — Payment gateway: keputusan setiap panggilan balik gateway disimpan untuk semakan owner/vendor.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/003-webhook-result.sql

ALTER TABLE webhook_logs
    ADD COLUMN result VARCHAR(40) NULL COMMENT 'B8: paid, already_paid, pending, failed, bad_signature, amount_mismatch, …' AFTER processed;
