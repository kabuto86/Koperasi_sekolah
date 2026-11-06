SET NAMES utf8mb4;
-- Pemasangan baharu: schema.sql sudah mengandungi jadual ini.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/010-membership.sql

-- W4 — modul keahlian koperasi (membership): ahli, transaksi syer, dividen tahunan. Tiada no. IC (PDPA).
-- Amaun dalam sen. Baki syer = SUM(buy) − SUM(withdraw) (+ reinvest dividen). Dividen = kadar % × baki syer pada 31 Dis tahun itu.
CREATE TABLE membership_members (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    member_no       VARCHAR(20)  NOT NULL COMMENT 'A0001 — unik dalam koperasi',
    full_name       VARCHAR(150) NOT NULL,
    member_type     ENUM('student','teacher','staff','parent','other') NOT NULL DEFAULT 'student',
    class_name      VARCHAR(50)  NULL COMMENT 'murid sahaja',
    phone           VARCHAR(20)  NULL,
    email           VARCHAR(191) NULL,
    joined_on       DATE         NOT NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    note            VARCHAR(255) NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_membership_members_no (cooperative_id, member_no),
    KEY idx_membership_members_vendor (vendor_id, cooperative_id, status),
    CONSTRAINT fk_membership_members_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_membership_members_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_share_transactions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    type            ENUM('buy','withdraw','dividend') NOT NULL COMMENT 'dividend = dividen dilabur semula sebagai syer',
    amount          INT UNSIGNED NOT NULL,
    transaction_date DATE        NOT NULL,
    note            VARCHAR(255) NULL,
    dividend_id     INT UNSIGNED NULL,
    vendor_user_id  INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_membership_tx_vendor (vendor_id, cooperative_id, transaction_date),
    KEY idx_membership_tx_member (vendor_id, member_id, transaction_date),
    CONSTRAINT fk_membership_tx_member FOREIGN KEY (member_id) REFERENCES membership_members (id),
    CONSTRAINT fk_membership_tx_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_membership_tx_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_dividends (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    year            SMALLINT UNSIGNED NOT NULL,
    rate            DECIMAL(5,2) NOT NULL COMMENT 'peratus',
    total_shares    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_amount    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    member_count    INT UNSIGNED NOT NULL DEFAULT 0,
    payout          ENUM('cash','reinvest') NOT NULL DEFAULT 'cash',
    status          ENUM('declared','paid') NOT NULL DEFAULT 'declared',
    declared_by     INT UNSIGNED NULL,
    paid_at         DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_membership_dividends_year (cooperative_id, year),
    KEY idx_membership_dividends_vendor (vendor_id, cooperative_id),
    CONSTRAINT fk_membership_dividends_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_membership_dividends_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_dividend_lines (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    dividend_id     INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    share_balance   INT UNSIGNED NOT NULL COMMENT 'baki syer pada 31 Dis',
    amount          INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_membership_dividend_lines (dividend_id, member_id),
    KEY idx_membership_dividend_lines_vendor (vendor_id, dividend_id),
    CONSTRAINT fk_membership_lines_dividend FOREIGN KEY (dividend_id) REFERENCES membership_dividends (id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_lines_member FOREIGN KEY (member_id) REFERENCES membership_members (id),
    CONSTRAINT fk_membership_lines_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
