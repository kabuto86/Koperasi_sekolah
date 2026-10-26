SET NAMES utf8mb4;
-- B2 — Auth: token jemputan / reset untuk kakitangan owner + elak guna semula kod TOTP.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/001-auth-tokens.sql
-- Perubahan yang sama sudah ada dalam schema.sql (pemasangan baharu tidak perlu fail ini).

-- Jemputan & reset kata laluan kakitangan owner (sama seperti vendor_user_tokens). Token disimpan sebagai hash SHA-256
CREATE TABLE IF NOT EXISTS platform_user_tokens (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    platform_user_id  INT UNSIGNED NOT NULL,
    type              ENUM('invite','reset_password') NOT NULL,
    token_hash        CHAR(64)     NOT NULL,
    expires_at        DATETIME     NOT NULL,
    used_at           DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_platform_user_tokens_hash (token_hash),
    KEY idx_platform_user_tokens (platform_user_id, type),
    CONSTRAINT fk_pu_tokens_user FOREIGN KEY (platform_user_id) REFERENCES platform_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Langkah masa (30 saat) kod TOTP terakhir yang diterima — kod yang sama tidak boleh diguna dua kali
ALTER TABLE platform_users ADD COLUMN totp_last_step INT UNSIGNED NULL AFTER totp_secret;
ALTER TABLE vendor_users   ADD COLUMN totp_last_step INT UNSIGNED NULL AFTER totp_secret;
