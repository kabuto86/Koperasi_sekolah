SET NAMES utf8mb4;
-- B15 — API token Flutter & push: peranti push dirujuk kepada token apps (log keluar / batal token → peranti dibuang),
-- berita dihantar sebagai push sekali sahaja (push_sent_at).
-- Pemasangan baharu: schema.sql sudah mengandungi perubahan ini.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/008-push-devices-token.sql

ALTER TABLE push_devices
    ADD COLUMN api_token_id INT UNSIGNED NULL COMMENT 'B15: token apps yang mendaftar peranti ini' AFTER customer_id,
    ADD COLUMN updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD KEY idx_push_devices_api_token (api_token_id),
    ADD CONSTRAINT fk_push_devices_api_token FOREIGN KEY (api_token_id) REFERENCES api_tokens (id) ON DELETE CASCADE;

ALTER TABLE api_tokens
    ADD KEY idx_api_tokens_active (customer_id, revoked_at, expires_at);

ALTER TABLE news_posts
    ADD COLUMN push_sent_at DATETIME NULL COMMENT 'B15: push apps dimasukkan ke baris gilir (sekali sahaja)' AFTER email_recipients,
    ADD COLUMN push_recipients INT UNSIGNED NOT NULL DEFAULT 0 AFTER push_sent_at;

-- Berita yang sudah terbit sebelum B15 tidak dihantar sebagai push
UPDATE news_posts SET push_sent_at = NOW() WHERE published_at IS NOT NULL AND published_at <= NOW();
