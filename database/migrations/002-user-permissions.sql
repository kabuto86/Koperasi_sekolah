SET NAMES utf8mb4;
-- B3b — Kebenaran ikut pengguna: pengecualian (tambah / tarik) dari lalai peranan untuk kakitangan owner & vendor (PELAN 4.1).
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/002-user-permissions.sql
-- Perubahan yang sama sudah ada dalam schema.sql (pemasangan baharu tidak perlu fail ini).

-- Pengecualian kebenaran kakitangan owner (B3b, migrations/002). allowed 1 = tambah, 0 = tarik dari lalai peranan (PELAN 4.1)
CREATE TABLE IF NOT EXISTS platform_user_permissions (
    platform_user_id  INT UNSIGNED NOT NULL,
    permission        VARCHAR(50)  NOT NULL,
    allowed           TINYINT(1)   NOT NULL,
    updated_by        INT UNSIGNED NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (platform_user_id, permission),
    CONSTRAINT fk_pu_permissions_user FOREIGN KEY (platform_user_id) REFERENCES platform_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pengecualian kebenaran kakitangan vendor (B3b, migrations/002). allowed 1 = tambah, 0 = tarik dari lalai peranan (PELAN 4.1)
CREATE TABLE IF NOT EXISTS vendor_user_permissions (
    vendor_id       INT UNSIGNED NOT NULL,
    vendor_user_id  INT UNSIGNED NOT NULL,
    permission      VARCHAR(50)  NOT NULL,
    allowed         TINYINT(1)   NOT NULL,
    updated_by      INT UNSIGNED NULL COMMENT 'vendor_users.id',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (vendor_user_id, permission),
    KEY idx_vu_permissions_vendor (vendor_id, vendor_user_id),
    CONSTRAINT fk_vu_permissions_user FOREIGN KEY (vendor_user_id) REFERENCES vendor_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_vu_permissions_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
