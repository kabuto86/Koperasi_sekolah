SET NAMES utf8mb4;
-- KoperasiKu SaaS — skema pangkalan data koperasi_saas (PELAN 9) · 76 jadual sejak B2
-- Import: mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/schema.sql
-- Wang dalam sen (INT). Setiap jadual perniagaan ada vendor_id; indeks bermula vendor_id (PELAN 8.6).

SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+08:00';

-- =====================================================================
-- A. Platform
-- =====================================================================

CREATE TABLE languages (
    code         VARCHAR(5)   NOT NULL,
    name         VARCHAR(50)  NOT NULL,
    native_name  VARCHAR(50)  NOT NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    is_default   TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order   SMALLINT     NOT NULL DEFAULT 0,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE translations (
    language_code    VARCHAR(5)   NOT NULL,
    translation_key  VARCHAR(150) NOT NULL,
    value            TEXT         NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (language_code, translation_key),
    CONSTRAINT fk_translations_language FOREIGN KEY (language_code) REFERENCES languages (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grade_levels (
    code        VARCHAR(5)   NOT NULL,
    name_key    VARCHAR(50)  NOT NULL,
    level_type  ENUM('preschool','primary','secondary') NOT NULL,
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_users (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email            VARCHAR(191) NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    full_name        VARCHAR(150) NOT NULL,
    role             ENUM('owner','owner_staff') NOT NULL DEFAULT 'owner_staff',
    language         VARCHAR(5)   NOT NULL DEFAULT 'ms',
    totp_secret      VARCHAR(255) NULL COMMENT 'disulitkan (Crypto)',
    totp_last_step   INT UNSIGNED NULL COMMENT 'kod TOTP terakhir diterima (elak guna semula) — B2',
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    failed_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until     DATETIME     NULL,
    last_login_at    DATETIME     NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_platform_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jemputan & reset kata laluan kakitangan owner (B2, migrations/001). Token disimpan sebagai hash SHA-256
CREATE TABLE platform_user_tokens (
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

-- Kod pemulihan 2FA (owner wajib, vendor_admin pilihan). Disimpan sebagai hash.
CREATE TABLE totp_recovery_codes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_type  ENUM('owner','vendor_user') NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    code_hash  CHAR(64)     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recovery_user (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_templates (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    template_key   VARCHAR(50)  NOT NULL,
    language_code  VARCHAR(5)   NOT NULL,
    subject        VARCHAR(255) NOT NULL,
    body_html      MEDIUMTEXT   NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_templates (template_key, language_code),
    CONSTRAINT fk_email_templates_language FOREIGN KEY (language_code) REFERENCES languages (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_settings (
    setting_key    VARCHAR(100) NOT NULL,
    setting_value  TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by     INT UNSIGNED NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_type   ENUM('owner','vendor_user','customer','system') NOT NULL,
    actor_id     INT UNSIGNED NULL,
    vendor_id    INT UNSIGNED NULL,
    action       VARCHAR(80)  NOT NULL,
    entity       VARCHAR(50)  NULL,
    entity_id    INT UNSIGNED NULL,
    detail_json  TEXT         NULL,
    ip_address   VARCHAR(45)  NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_vendor (vendor_id, created_at),
    KEY idx_audit_actor (actor_type, actor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    login_type    ENUM('owner','vendor','customer','pos_pin') NOT NULL,
    identifier    VARCHAR(191) NOT NULL,
    ip_address    VARCHAR(45)  NOT NULL,
    user_agent    VARCHAR(255) NULL,
    success       TINYINT(1)   NOT NULL DEFAULT 0,
    attempted_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_identifier (identifier, attempted_at),
    KEY idx_login_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- B. Langganan & bil
-- =====================================================================

CREATE TABLE plans (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code              VARCHAR(30)  NOT NULL,
    name              VARCHAR(100) NOT NULL,
    price_monthly     INT UNSIGNED NOT NULL DEFAULT 0,
    price_yearly      INT UNSIGNED NOT NULL DEFAULT 0,
    max_cooperatives  INT UNSIGNED NULL COMMENT 'NULL = tanpa had',
    max_products      INT UNSIGNED NULL,
    max_staff         INT UNSIGNED NULL,
    features_json     TEXT         NULL,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order        SMALLINT     NOT NULL DEFAULT 0,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plans_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plan_coop_tiers (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_id        INT UNSIGNED NOT NULL,
    from_coop      INT UNSIGNED NOT NULL,
    to_coop        INT UNSIGNED NULL COMMENT 'NULL = dan seterusnya',
    price_monthly  INT UNSIGNED NOT NULL DEFAULT 0,
    price_yearly   INT UNSIGNED NOT NULL DEFAULT 0,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plan_tiers (plan_id, from_coop),
    CONSTRAINT fk_tiers_plan FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE modules (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code             VARCHAR(40)  NOT NULL,
    name_key         VARCHAR(100) NOT NULL,
    description_key  VARCHAR(100) NOT NULL,
    price_monthly    INT UNSIGNED NOT NULL DEFAULT 0,
    price_yearly     INT UNSIGNED NOT NULL DEFAULT 0,
    is_core          TINYINT(1)   NOT NULL DEFAULT 0,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order       SMALLINT     NOT NULL DEFAULT 0,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_modules_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE templates (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code           VARCHAR(40)  NOT NULL,
    name           VARCHAR(100) NOT NULL,
    description    VARCHAR(255) NULL,
    folder         VARCHAR(100) NOT NULL,
    preview_image  VARCHAR(255) NULL,
    price_monthly  INT UNSIGNED NOT NULL DEFAULT 0,
    price_yearly   INT UNSIGNED NOT NULL DEFAULT 0,
    is_free        TINYINT(1)   NOT NULL DEFAULT 0,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_templates_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendors (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code             VARCHAR(20)  NOT NULL,
    company_name     VARCHAR(150) NOT NULL,
    registration_no  VARCHAR(50)  NULL,
    contact_name     VARCHAR(150) NOT NULL,
    phone            VARCHAR(20)  NULL,
    email            VARCHAR(191) NOT NULL,
    address          VARCHAR(255) NULL,
    status           ENUM('trial','active','past_due','suspended','cancelled') NOT NULL DEFAULT 'trial',
    trial_ends_at    DATETIME     NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vendors_code (code),
    KEY idx_vendors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota dalaman owner tentang vendor (owner/vendors/add-note, sebab gantung)
CREATE TABLE vendor_notes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id   INT UNSIGNED NOT NULL,
    body        TEXT         NOT NULL,
    created_by  INT UNSIGNED NULL COMMENT 'platform_users.id',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vendor_notes (vendor_id, created_at),
    CONSTRAINT fk_vendor_notes_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscriptions (
    id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id              INT UNSIGNED NOT NULL,
    plan_id                INT UNSIGNED NOT NULL,
    billing_cycle          ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    status                 ENUM('trial','active','past_due','suspended','cancelled') NOT NULL DEFAULT 'trial',
    started_at             DATE         NOT NULL,
    current_period_start   DATE         NOT NULL,
    current_period_end     DATE         NOT NULL,
    pending_plan_id        INT UNSIGNED NULL COMMENT 'tukar pelan pada current_period_end',
    pending_billing_cycle  ENUM('monthly','yearly') NULL,
    cancelled_at           DATETIME     NULL,
    created_at             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_subscriptions_vendor (vendor_id, status),
    KEY idx_subscriptions_period (current_period_end),
    CONSTRAINT fk_subscriptions_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id),
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans (id),
    CONSTRAINT fk_subscriptions_pending_plan FOREIGN KEY (pending_plan_id) REFERENCES plans (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscription_items (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id             INT UNSIGNED NOT NULL,
    subscription_id       INT UNSIGNED NOT NULL,
    item_type             ENUM('cooperative','module','template') NOT NULL,
    cooperative_id        INT UNSIGNED NULL,
    ref_id                INT UNSIGNED NULL COMMENT 'module_id / template_id',
    unit_price            INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'snapshot',
    started_at            DATE         NOT NULL,
    ended_at              DATE         NULL,
    cancel_at_period_end  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sub_items_vendor (vendor_id, subscription_id),
    KEY idx_sub_items_coop (vendor_id, cooperative_id),
    CONSTRAINT fk_sub_items_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id),
    CONSTRAINT fk_sub_items_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions (id),
    CONSTRAINT fk_sub_items_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id     INT UNSIGNED NOT NULL,
    invoice_no    VARCHAR(30)  NOT NULL,
    period_start  DATE         NOT NULL,
    period_end    DATE         NOT NULL,
    subtotal      INT UNSIGNED NOT NULL DEFAULT 0,
    sst_rate      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax           INT UNSIGNED NOT NULL DEFAULT 0,
    total         INT UNSIGNED NOT NULL DEFAULT 0,
    status        ENUM('draft','issued','paid','void') NOT NULL DEFAULT 'draft',
    issued_at     DATETIME     NULL,
    due_at        DATETIME     NULL,
    paid_at       DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invoices_no (invoice_no),
    KEY idx_invoices_vendor (vendor_id, status),
    KEY idx_invoices_due (status, due_at),
    CONSTRAINT fk_invoices_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice_lines (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id      INT UNSIGNED NOT NULL,
    vendor_id       INT UNSIGNED NOT NULL,
    description     VARCHAR(255) NOT NULL,
    item_type       ENUM('plan','cooperative','module','template','other') NOT NULL,
    cooperative_id  INT UNSIGNED NULL,
    qty             INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price      INT UNSIGNED NOT NULL DEFAULT 0,
    amount          INT UNSIGNED NOT NULL DEFAULT 0,
    is_prorated     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_invoice_lines (vendor_id, invoice_id),
    CONSTRAINT fk_invoice_lines_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_lines_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_payments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id     INT UNSIGNED NOT NULL,
    invoice_id    INT UNSIGNED NOT NULL,
    method        ENUM('online','bank_transfer','cash','cheque','other') NOT NULL DEFAULT 'online',
    gateway_code  VARCHAR(20)  NULL,
    gateway_ref   VARCHAR(100) NULL COMMENT 'rujukan gateway atau rujukan manual',
    amount        INT UNSIGNED NOT NULL,
    status        ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    note          VARCHAR(255) NULL,
    raw_payload   MEDIUMTEXT   NULL,
    paid_at       DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_platform_payments_ref (gateway_ref),
    KEY idx_platform_payments (vendor_id, invoice_id),
    CONSTRAINT fk_platform_payments_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id),
    CONSTRAINT fk_platform_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_announcements (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title         VARCHAR(200) NOT NULL,
    body          TEXT         NOT NULL,
    target        ENUM('all','selected') NOT NULL DEFAULT 'all',
    published_at  DATETIME     NULL COMMENT 'NULL = draf',
    created_by    INT UNSIGNED NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_announcements_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendor sasaran bila target = 'selected'
CREATE TABLE vendor_announcement_targets (
    announcement_id  INT UNSIGNED NOT NULL,
    vendor_id        INT UNSIGNED NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (announcement_id, vendor_id),
    KEY idx_announcement_targets_vendor (vendor_id),
    CONSTRAINT fk_ann_targets_announcement FOREIGN KEY (announcement_id) REFERENCES vendor_announcements (id) ON DELETE CASCADE,
    CONSTRAINT fk_ann_targets_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- C. Vendor & koperasi
-- =====================================================================

CREATE TABLE vendor_users (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id            INT UNSIGNED NOT NULL,
    email                VARCHAR(191) NOT NULL,
    password_hash        VARCHAR(255) NOT NULL,
    full_name            VARCHAR(150) NOT NULL,
    phone                VARCHAR(20)  NULL,
    role                 ENUM('vendor_admin','coop_manager','cashier') NOT NULL DEFAULT 'cashier',
    pin_hash             VARCHAR(255) NULL COMMENT 'PIN POS (Argon2id)',
    pin_failed_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    totp_secret          VARCHAR(255) NULL COMMENT 'disulitkan (Crypto)',
    totp_last_step       INT UNSIGNED NULL COMMENT 'kod TOTP terakhir diterima (elak guna semula) — B2',
    language             VARCHAR(5)   NOT NULL DEFAULT 'ms',
    is_active            TINYINT(1)   NOT NULL DEFAULT 1,
    failed_attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until         DATETIME     NULL,
    last_login_at        DATETIME     NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vendor_users_email (email),
    KEY idx_vendor_users_vendor (vendor_id, role, is_active),
    CONSTRAINT fk_vendor_users_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token jemputan / reset kata laluan kakitangan vendor (hash SHA-256)
CREATE TABLE vendor_user_tokens (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    vendor_user_id  INT UNSIGNED NOT NULL,
    type            ENUM('invite','reset_password') NOT NULL,
    token_hash      CHAR(64)     NOT NULL,
    expires_at      DATETIME     NOT NULL,
    used_at         DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vendor_user_tokens_hash (token_hash),
    KEY idx_vendor_user_tokens (vendor_id, vendor_user_id),
    CONSTRAINT fk_vu_tokens_user FOREIGN KEY (vendor_user_id) REFERENCES vendor_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cooperatives (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id         INT UNSIGNED NOT NULL,
    slug              VARCHAR(80)  NOT NULL,
    school_name       VARCHAR(150) NOT NULL,
    school_code       VARCHAR(10)  NULL COMMENT 'kod KPM',
    school_type       ENUM('SK','SJKC','SJKT','SMK','SMKA','SBP','other') NOT NULL,
    state             VARCHAR(50)  NOT NULL,
    district          VARCHAR(80)  NOT NULL,
    address           VARCHAR(255) NULL,
    phone             VARCHAR(20)  NULL,
    logo_path         VARCHAR(255) NULL,
    template_id       INT UNSIGNED NOT NULL,
    default_language  VARCHAR(5)   NOT NULL DEFAULT 'ms',
    accept_cash       TINYINT(1)   NOT NULL DEFAULT 1,
    accept_online     TINYINT(1)   NOT NULL DEFAULT 0,
    status            ENUM('active','suspended','closed') NOT NULL DEFAULT 'active',
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cooperatives_slug (slug),
    KEY idx_cooperatives_vendor (vendor_id, status),
    CONSTRAINT fk_cooperatives_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id),
    CONSTRAINT fk_cooperatives_template FOREIGN KEY (template_id) REFERENCES templates (id),
    CONSTRAINT fk_cooperatives_language FOREIGN KEY (default_language) REFERENCES languages (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_user_cooperatives (
    vendor_id       INT UNSIGNED NOT NULL,
    vendor_user_id  INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (vendor_user_id, cooperative_id),
    KEY idx_vuc_vendor (vendor_id, cooperative_id),
    CONSTRAINT fk_vuc_user FOREIGN KEY (vendor_user_id) REFERENCES vendor_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_vuc_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_vuc_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cooperative_settings (
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    setting_key     VARCHAR(60)  NOT NULL,
    setting_value   TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cooperative_id, setting_key),
    KEY idx_coop_settings_vendor (vendor_id, cooperative_id),
    CONSTRAINT fk_coop_settings_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_coop_settings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cooperative_modules (
    vendor_id             INT UNSIGNED NOT NULL,
    cooperative_id        INT UNSIGNED NOT NULL,
    module_id             INT UNSIGNED NOT NULL,
    status                ENUM('active','expired') NOT NULL DEFAULT 'active',
    started_at            DATE         NOT NULL,
    ends_at               DATE         NULL,
    cancel_at_period_end  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cooperative_id, module_id),
    KEY idx_coop_modules_vendor (vendor_id, cooperative_id, status),
    CONSTRAINT fk_coop_modules_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_coop_modules_module FOREIGN KEY (module_id) REFERENCES modules (id),
    CONSTRAINT fk_coop_modules_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE school_classes (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id         INT UNSIGNED NOT NULL,
    cooperative_id    INT UNSIGNED NOT NULL,
    grade_level_code  VARCHAR(5)   NOT NULL,
    class_name        VARCHAR(50)  NOT NULL,
    sort_order        SMALLINT     NOT NULL DEFAULT 0,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_school_classes (cooperative_id, grade_level_code, class_name),
    KEY idx_school_classes_vendor (vendor_id, cooperative_id, grade_level_code),
    CONSTRAINT fk_classes_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_classes_grade FOREIGN KEY (grade_level_code) REFERENCES grade_levels (code),
    CONSTRAINT fk_classes_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NULL COMMENT 'NULL = peringkat vendor',
    vendor_user_id  INT UNSIGNED NULL,
    action          VARCHAR(80)  NOT NULL,
    entity          VARCHAR(50)  NULL,
    entity_id       INT UNSIGNED NULL,
    detail_json     TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_vendor (vendor_id, created_at),
    KEY idx_activity_coop (vendor_id, cooperative_id, created_at),
    KEY idx_activity_user (vendor_id, vendor_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- D. Katalog & inventori
-- =====================================================================

CREATE TABLE categories (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_categories_vendor (vendor_id, cooperative_id, sort_order),
    CONSTRAINT fk_categories_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_categories_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED NULL,
    name            VARCHAR(150) NOT NULL,
    description     TEXT         NULL,
    sku             VARCHAR(50)  NULL,
    barcode         VARCHAR(50)  NULL,
    price           INT UNSIGNED NOT NULL DEFAULT 0,
    cost_price      INT UNSIGNED NOT NULL DEFAULT 0,
    unit            VARCHAR(20)  NOT NULL DEFAULT 'unit',
    image_path      VARCHAR(255) NULL,
    max_per_order   INT UNSIGNED NULL COMMENT 'NULL = tiada had',
    has_variants    TINYINT(1)   NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_barcode (cooperative_id, barcode),
    UNIQUE KEY uq_products_sku (cooperative_id, sku),
    KEY idx_products_vendor (vendor_id, cooperative_id, category_id),
    KEY idx_products_active (vendor_id, cooperative_id, is_active),
    CONSTRAINT fk_products_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_products_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_translations (
    vendor_id      INT UNSIGNED NOT NULL,
    product_id     INT UNSIGNED NOT NULL,
    language_code  VARCHAR(5)   NOT NULL,
    name           VARCHAR(150) NOT NULL,
    description    TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, language_code),
    KEY idx_product_translations_vendor (vendor_id, product_id),
    CONSTRAINT fk_product_tr_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_tr_language FOREIGN KEY (language_code) REFERENCES languages (code),
    CONSTRAINT fk_product_tr_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cooperative_id disalin supaya barcode varian unik dalam koperasi (seperti produk)
CREATE TABLE product_variants (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    label           VARCHAR(100) NOT NULL,
    sku             VARCHAR(50)  NULL,
    barcode         VARCHAR(50)  NULL,
    price_delta     INT          NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variants_barcode (cooperative_id, barcode),
    UNIQUE KEY uq_variants_sku (cooperative_id, sku),
    KEY idx_variants_vendor (vendor_id, product_id),
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_variants_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_variants_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_grade_levels (
    vendor_id         INT UNSIGNED NOT NULL,
    product_id        INT UNSIGNED NOT NULL,
    grade_level_code  VARCHAR(5)   NOT NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, grade_level_code),
    KEY idx_product_grades_vendor (vendor_id, grade_level_code),
    CONSTRAINT fk_product_grades_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_grades_grade FOREIGN KEY (grade_level_code) REFERENCES grade_levels (code),
    CONSTRAINT fk_product_grades_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_levels (
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = tiada varian',
    qty_on_hand     INT          NOT NULL DEFAULT 0,
    qty_reserved    INT          NOT NULL DEFAULT 0,
    min_stock       INT          NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cooperative_id, product_id, variant_id),
    KEY idx_stock_vendor (vendor_id, cooperative_id),
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_stock_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_stock_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NOT NULL DEFAULT 0,
    type            ENUM('sale','pos_sale','reserve','release','collect','refund','restock','adjust','stocktake','po_receive') NOT NULL,
    qty_change      INT          NOT NULL,
    balance_after   INT          NOT NULL,
    ref_type        VARCHAR(30)  NULL COMMENT 'order | purchase_order | stock_take | refund',
    ref_id          INT UNSIGNED NULL,
    vendor_user_id  INT UNSIGNED NULL,
    note            VARCHAR(255) NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_movements_vendor (vendor_id, cooperative_id, created_at),
    KEY idx_movements_product (vendor_id, cooperative_id, product_id, variant_id),
    KEY idx_movements_ref (ref_type, ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id     INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    contact_name  VARCHAR(150) NULL,
    phone         VARCHAR(20)  NULL,
    email         VARCHAR(191) NULL,
    address       VARCHAR(255) NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_suppliers_vendor (vendor_id, is_active),
    CONSTRAINT fk_suppliers_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_orders (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    supplier_id     INT UNSIGNED NOT NULL,
    po_no           VARCHAR(30)  NOT NULL,
    status          ENUM('draft','ordered','partial','received','cancelled') NOT NULL DEFAULT 'draft',
    ordered_at      DATETIME     NULL,
    received_at     DATETIME     NULL,
    total           INT UNSIGNED NOT NULL DEFAULT 0,
    note            VARCHAR(255) NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_purchase_orders_no (cooperative_id, po_no),
    KEY idx_purchase_orders_vendor (vendor_id, cooperative_id, status),
    CONSTRAINT fk_po_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id),
    CONSTRAINT fk_po_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_order_items (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id          INT UNSIGNED NOT NULL,
    purchase_order_id  INT UNSIGNED NOT NULL,
    product_id         INT UNSIGNED NOT NULL,
    variant_id         INT UNSIGNED NOT NULL DEFAULT 0,
    qty_ordered        INT UNSIGNED NOT NULL,
    qty_received       INT UNSIGNED NOT NULL DEFAULT 0,
    unit_cost          INT UNSIGNED NOT NULL DEFAULT 0,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_po_items_vendor (vendor_id, purchase_order_id),
    CONSTRAINT fk_po_items_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_po_items_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_po_items_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_takes (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    status          ENUM('open','completed') NOT NULL DEFAULT 'open',
    started_by      INT UNSIGNED NULL,
    started_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_takes_vendor (vendor_id, cooperative_id, status),
    CONSTRAINT fk_stock_takes_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_stock_takes_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_take_items (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id      INT UNSIGNED NOT NULL,
    stock_take_id  INT UNSIGNED NOT NULL,
    product_id     INT UNSIGNED NOT NULL,
    variant_id     INT UNSIGNED NOT NULL DEFAULT 0,
    qty_system     INT          NOT NULL DEFAULT 0,
    qty_counted    INT          NULL COMMENT 'NULL = belum dikira',
    variance       INT          NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stock_take_items (stock_take_id, product_id, variant_id),
    KEY idx_stock_take_items_vendor (vendor_id, stock_take_id),
    CONSTRAINT fk_st_items_stock_take FOREIGN KEY (stock_take_id) REFERENCES stock_takes (id) ON DELETE CASCADE,
    CONSTRAINT fk_st_items_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_st_items_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promotions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    code            VARCHAR(20)  NULL COMMENT 'NULL = diskaun automatik',
    type            ENUM('percent','fixed') NOT NULL,
    value           INT UNSIGNED NOT NULL COMMENT '% atau sen',
    min_spend       INT UNSIGNED NOT NULL DEFAULT 0,
    starts_on       DATE         NOT NULL,
    ends_on         DATE         NOT NULL,
    max_uses        INT UNSIGNED NULL COMMENT 'NULL = tanpa had',
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_promotions_code (cooperative_id, code),
    KEY idx_promotions_vendor (vendor_id, cooperative_id, is_active),
    CONSTRAINT fk_promotions_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_promotions_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- E. Kit keperluan sekolah
-- =====================================================================

CREATE TABLE school_kits (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id         INT UNSIGNED NOT NULL,
    cooperative_id    INT UNSIGNED NOT NULL,
    grade_level_code  VARCHAR(5)   NOT NULL,
    school_year       SMALLINT UNSIGNED NOT NULL,
    name              VARCHAR(150) NOT NULL,
    note              TEXT         NULL,
    is_published      TINYINT(1)   NOT NULL DEFAULT 0,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_school_kits (cooperative_id, grade_level_code, school_year),
    KEY idx_school_kits_vendor (vendor_id, cooperative_id, school_year),
    CONSTRAINT fk_kits_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_kits_grade FOREIGN KEY (grade_level_code) REFERENCES grade_levels (code),
    CONSTRAINT fk_kits_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE school_kit_items (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id      INT UNSIGNED NOT NULL,
    school_kit_id  INT UNSIGNED NOT NULL,
    product_id     INT UNSIGNED NOT NULL,
    variant_id     INT UNSIGNED NULL COMMENT 'NULL = ibu bapa pilih varian',
    qty            INT UNSIGNED NOT NULL DEFAULT 1,
    is_mandatory   TINYINT(1)   NOT NULL DEFAULT 1,
    note           VARCHAR(255) NULL,
    sort_order     SMALLINT     NOT NULL DEFAULT 0,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kit_items_vendor (vendor_id, school_kit_id),
    CONSTRAINT fk_kit_items_kit FOREIGN KEY (school_kit_id) REFERENCES school_kits (id) ON DELETE CASCADE,
    CONSTRAINT fk_kit_items_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_kit_items_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- F. Pembeli (akaun global — tiada vendor_id pada customers)
-- =====================================================================

CREATE TABLE customers (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name          VARCHAR(150) NOT NULL,
    phone              VARCHAR(20)  NULL COMMENT 'NULL selepas padam akaun (PDPA)',
    email              VARCHAR(191) NULL,
    password_hash      VARCHAR(255) NOT NULL,
    relationship       ENUM('mother','father','guardian') NOT NULL DEFAULT 'guardian',
    language           VARCHAR(5)   NOT NULL DEFAULT 'ms',
    email_verified_at  DATETIME     NULL,
    pdpa_consent_at    DATETIME     NULL,
    is_active          TINYINT(1)   NOT NULL DEFAULT 1,
    failed_attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until       DATETIME     NULL,
    last_login_at      DATETIME     NULL,
    deleted_at         DATETIME     NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customers_email (email),
    UNIQUE KEY uq_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE children (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id       INT UNSIGNED NOT NULL,
    cooperative_id    INT UNSIGNED NOT NULL,
    vendor_id         INT UNSIGNED NOT NULL COMMENT 'salinan untuk tapisan vendor',
    full_name         VARCHAR(150) NOT NULL,
    grade_level_code  VARCHAR(5)   NOT NULL,
    school_class_id   INT UNSIGNED NULL,
    class_name        VARCHAR(50)  NULL,
    school_year       SMALLINT UNSIGNED NOT NULL,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_children_customer (customer_id, is_active),
    KEY idx_children_vendor (vendor_id, cooperative_id, grade_level_code, class_name),
    CONSTRAINT fk_children_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_children_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_children_grade FOREIGN KEY (grade_level_code) REFERENCES grade_levels (code),
    CONSTRAINT fk_children_class FOREIGN KEY (school_class_id) REFERENCES school_classes (id) ON DELETE SET NULL,
    CONSTRAINT fk_children_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_tokens (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id  INT UNSIGNED NOT NULL,
    type         ENUM('verify_email','reset_password') NOT NULL,
    token_hash   CHAR(64)     NOT NULL,
    expires_at   DATETIME     NOT NULL,
    used_at      DATETIME     NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customer_tokens_hash (token_hash),
    KEY idx_customer_tokens (customer_id, type),
    CONSTRAINT fk_customer_tokens_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_tokens (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id   INT UNSIGNED NOT NULL,
    token_hash    CHAR(64)     NOT NULL,
    device_name   VARCHAR(100) NULL,
    last_used_at  DATETIME     NULL,
    expires_at    DATETIME     NOT NULL,
    revoked_at    DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_tokens_hash (token_hash),
    KEY idx_api_tokens_customer (customer_id),
    CONSTRAINT fk_api_tokens_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_devices (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id  INT UNSIGNED NOT NULL,
    platform     ENUM('android','ios') NOT NULL,
    push_token   VARCHAR(255) NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_devices_token (push_token),
    KEY idx_push_devices_customer (customer_id),
    CONSTRAINT fk_push_devices_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota vendor tentang ibu bapa (vendor/customers/save-note) — setiap vendor nampak notanya sahaja
CREATE TABLE vendor_customer_notes (
    vendor_id    INT UNSIGNED NOT NULL,
    customer_id  INT UNSIGNED NOT NULL,
    note         TEXT         NOT NULL,
    updated_by   INT UNSIGNED NULL COMMENT 'vendor_users.id',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (vendor_id, customer_id),
    CONSTRAINT fk_customer_notes_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id),
    CONSTRAINT fk_customer_notes_customer FOREIGN KEY (customer_id) REFERENCES customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- G. Pesanan, ambil & bayaran
-- =====================================================================

CREATE TABLE carts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id     INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    vendor_id       INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carts (customer_id, cooperative_id),
    KEY idx_carts_vendor (vendor_id, cooperative_id),
    CONSTRAINT fk_carts_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_carts_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_carts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id             INT UNSIGNED NOT NULL,
    product_id          INT UNSIGNED NOT NULL,
    variant_id          INT UNSIGNED NOT NULL DEFAULT 0,
    child_id            INT UNSIGNED NOT NULL,
    qty                 INT UNSIGNED NOT NULL DEFAULT 1,
    source              ENUM('ala_carte','kit') NOT NULL DEFAULT 'ala_carte',
    school_kit_id       INT UNSIGNED NULL,
    school_kit_item_id  INT UNSIGNED NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cart_items_cart (cart_id, child_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_cart_items_child FOREIGN KEY (child_id) REFERENCES children (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pos_shifts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    vendor_user_id  INT UNSIGNED NOT NULL,
    opened_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opening_cash    INT UNSIGNED NOT NULL DEFAULT 0,
    closed_at       DATETIME     NULL,
    closing_cash    INT UNSIGNED NULL,
    expected_cash   INT UNSIGNED NULL,
    variance        INT          NULL,
    note            VARCHAR(255) NULL,
    status          ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pos_shifts_vendor (vendor_id, cooperative_id, vendor_user_id, status),
    CONSTRAINT fk_pos_shifts_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_pos_shifts_user FOREIGN KEY (vendor_user_id) REFERENCES vendor_users (id),
    CONSTRAINT fk_pos_shifts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id           INT UNSIGNED NOT NULL,
    cooperative_id      INT UNSIGNED NOT NULL,
    customer_id         INT UNSIGNED NULL COMMENT 'NULL = POS walk-in',
    order_no            VARCHAR(30)  NOT NULL,
    short_code          VARCHAR(6)   NOT NULL,
    channel             ENUM('online','pos') NOT NULL DEFAULT 'online',
    payment_method      ENUM('cash','online') NOT NULL,
    status              ENUM('pending_payment','paid','unpaid_cash','preparing','ready','partially_collected',
                             'collected','cancelled','expired','refunded') NOT NULL,
    subtotal            INT UNSIGNED NOT NULL DEFAULT 0,
    discount            INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'promotion_discount + manual_discount',
    promotion_discount  INT UNSIGNED NOT NULL DEFAULT 0,
    manual_discount     INT UNSIGNED NOT NULL DEFAULT 0,
    tax_rate            DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax                 INT UNSIGNED NOT NULL DEFAULT 0,
    total               INT UNSIGNED NOT NULL DEFAULT 0,
    promotion_id        INT UNSIGNED NULL,
    pos_shift_id        INT UNSIGNED NULL,
    cashier_id          INT UNSIGNED NULL COMMENT 'vendor_users.id (POS)',
    amount_received     INT UNSIGNED NULL,
    change_given        INT UNSIGNED NULL,
    pickup_by_type      ENUM('student','parent','proxy') NULL,
    pickup_by_name      VARCHAR(150) NULL,
    pickup_slot_id      INT UNSIGNED NULL COMMENT 'modul school_opening',
    pickup_slot_json    VARCHAR(255) NULL COMMENT 'salinan slot { slot_date, time_start, time_end, grade_level_code }',
    pickup_code_hash    VARCHAR(255) NULL,
    pickup_qr_token     CHAR(32)     NOT NULL,
    pickup_attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    customer_name       VARCHAR(150) NULL COMMENT 'snapshot',
    customer_phone      VARCHAR(20)  NULL,
    customer_email      VARCHAR(191) NULL,
    expires_at          DATETIME     NULL,
    paid_at             DATETIME     NULL,
    collected_at        DATETIME     NULL,
    cancelled_at        DATETIME     NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_no (cooperative_id, order_no),
    UNIQUE KEY uq_orders_short_code (cooperative_id, short_code),
    UNIQUE KEY uq_orders_qr_token (pickup_qr_token),
    KEY idx_orders_status (vendor_id, cooperative_id, status),
    KEY idx_orders_created (vendor_id, cooperative_id, created_at),
    KEY idx_orders_customer (customer_id, cooperative_id),
    KEY idx_orders_shift (vendor_id, pos_shift_id),
    KEY idx_orders_expiry (status, expires_at),
    CONSTRAINT fk_orders_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_orders_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id),
    CONSTRAINT fk_orders_shift FOREIGN KEY (pos_shift_id) REFERENCES pos_shifts (id),
    CONSTRAINT fk_orders_slot FOREIGN KEY (pickup_slot_id) REFERENCES pickup_slots (id),
    CONSTRAINT fk_orders_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id          INT UNSIGNED NOT NULL,
    order_id           INT UNSIGNED NOT NULL,
    product_id         INT UNSIGNED NOT NULL,
    variant_id         INT UNSIGNED NOT NULL DEFAULT 0,
    product_name       VARCHAR(150) NOT NULL COMMENT 'snapshot',
    variant_label      VARCHAR(100) NULL,
    sku                VARCHAR(50)  NULL,
    unit_price         INT UNSIGNED NOT NULL,
    qty                INT UNSIGNED NOT NULL,
    line_total         INT UNSIGNED NOT NULL,
    source             ENUM('ala_carte','kit','pos') NOT NULL DEFAULT 'ala_carte',
    school_kit_id      INT UNSIGNED NULL,
    clearance_item_id  INT UNSIGNED NULL,
    child_id           INT UNSIGNED NULL,
    child_name         VARCHAR(150) NULL COMMENT 'snapshot',
    grade_label        VARCHAR(5)   NULL COMMENT 'kod darjah snapshot',
    class_name         VARCHAR(50)  NULL,
    collected_qty      INT UNSIGNED NOT NULL DEFAULT 0,
    cancelled_qty      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'refund sebelum diambil',
    returned_qty       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'dipulang selepas diambil',
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_vendor (vendor_id, order_id),
    KEY idx_order_items_product (vendor_id, product_id),
    KEY idx_order_items_child (child_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id),
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_order_items_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_logs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id    INT UNSIGNED NOT NULL,
    order_id     INT UNSIGNED NOT NULL,
    from_status  VARCHAR(30)  NULL,
    to_status    VARCHAR(30)  NOT NULL,
    actor_type   ENUM('customer','vendor_user','system') NOT NULL,
    actor_id     INT UNSIGNED NULL,
    note         VARCHAR(255) NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_logs (vendor_id, order_id),
    CONSTRAINT fk_order_logs_order FOREIGN KEY (order_id) REFERENCES orders (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_pickups (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    order_id        INT UNSIGNED NOT NULL,
    method          ENUM('A','B','C','D','E') NOT NULL,
    picked_by_type  ENUM('student','parent','proxy') NOT NULL,
    picked_by_name  VARCHAR(150) NOT NULL,
    vendor_user_id  INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_pickups (vendor_id, order_id),
    CONSTRAINT fk_order_pickups_order FOREIGN KEY (order_id) REFERENCES orders (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_pickup_items (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id        INT UNSIGNED NOT NULL,
    order_pickup_id  INT UNSIGNED NOT NULL,
    order_item_id    INT UNSIGNED NOT NULL,
    qty              INT UNSIGNED NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_pickup_items (vendor_id, order_pickup_id),
    CONSTRAINT fk_pickup_items_pickup FOREIGN KEY (order_pickup_id) REFERENCES order_pickups (id),
    CONSTRAINT fk_pickup_items_item FOREIGN KEY (order_item_id) REFERENCES order_items (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_gateways (
    code                     VARCHAR(20)  NOT NULL,
    name                     VARCHAR(50)  NOT NULL,
    config_fields_json       TEXT         NOT NULL COMMENT '[{ name, secret }]',
    supports_refund          TINYINT(1)   NOT NULL DEFAULT 0,
    is_enabled_for_vendors   TINYINT(1)   NOT NULL DEFAULT 0,
    is_enabled_for_platform  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cooperative_gateways (
    id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id              INT UNSIGNED NOT NULL,
    cooperative_id         INT UNSIGNED NOT NULL,
    gateway_code           VARCHAR(20)  NOT NULL,
    config_json            TEXT         NULL COMMENT 'medan bukan rahsia',
    credentials_encrypted  TEXT         NULL COMMENT 'rahsia (Crypto)',
    mode                   ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
    is_active              TINYINT(1)   NOT NULL DEFAULT 0,
    created_at             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coop_gateways (cooperative_id, gateway_code),
    KEY idx_coop_gateways_vendor (vendor_id, cooperative_id, is_active),
    CONSTRAINT fk_coop_gateways_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_coop_gateways_gateway FOREIGN KEY (gateway_code) REFERENCES payment_gateways (code),
    CONSTRAINT fk_coop_gateways_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    order_id        INT UNSIGNED NOT NULL,
    method          ENUM('cash','online') NOT NULL,
    gateway_code    VARCHAR(20)  NULL,
    gateway_ref     VARCHAR(100) NULL,
    amount          INT UNSIGNED NOT NULL,
    status          ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    raw_payload     MEDIUMTEXT   NULL,
    verified_at     DATETIME     NULL,
    received_by     INT UNSIGNED NULL COMMENT 'juruwang (tunai)',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_gateway_ref (gateway_ref),
    KEY idx_payments_vendor (vendor_id, cooperative_id, status, created_at),
    KEY idx_payments_order (vendor_id, order_id),
    CONSTRAINT fk_payments_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id),
    CONSTRAINT fk_payments_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refunds (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    refund_no       VARCHAR(30)  NOT NULL,
    order_id        INT UNSIGNED NOT NULL,
    payment_id      INT UNSIGNED NULL,
    amount          INT UNSIGNED NOT NULL,
    reason          VARCHAR(255) NOT NULL,
    method          ENUM('cash','bank_transfer','gateway') NOT NULL,
    restock         TINYINT(1)   NOT NULL DEFAULT 0,
    status          ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed',
    vendor_user_id  INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_refunds_no (cooperative_id, refund_no),
    KEY idx_refunds_vendor (vendor_id, cooperative_id, created_at),
    KEY idx_refunds_order (vendor_id, order_id),
    CONSTRAINT fk_refunds_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_refunds_order FOREIGN KEY (order_id) REFERENCES orders (id),
    CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id) REFERENCES payments (id),
    CONSTRAINT fk_refunds_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refund_items (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id      INT UNSIGNED NOT NULL,
    refund_id      INT UNSIGNED NOT NULL,
    order_item_id  INT UNSIGNED NOT NULL,
    qty            INT UNSIGNED NOT NULL COMMENT 'jumlah unit (dibatal + dipulang)',
    cancelled_qty  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'unit belum diambil yang dibatalkan',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_refund_items (vendor_id, refund_id),
    CONSTRAINT fk_refund_items_refund FOREIGN KEY (refund_id) REFERENCES refunds (id),
    CONSTRAINT fk_refund_items_item FOREIGN KEY (order_item_id) REFERENCES order_items (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE webhook_logs (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    gateway_code     VARCHAR(20)  NOT NULL,
    cooperative_id   INT UNSIGNED NULL,
    payload          MEDIUMTEXT   NOT NULL,
    signature_valid  TINYINT(1)   NOT NULL DEFAULT 0,
    processed        TINYINT(1)   NOT NULL DEFAULT 0,
    result           VARCHAR(40)  NULL COMMENT 'B8: paid, already_paid, pending, failed, bad_signature, amount_mismatch, …',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_webhook_logs (gateway_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- H. Modul add-on
-- =====================================================================

CREATE TABLE news_posts (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id         INT UNSIGNED NOT NULL,
    cooperative_id    INT UNSIGNED NOT NULL,
    title             VARCHAR(150) NOT NULL,
    body              TEXT         NOT NULL,
    image_path        VARCHAR(255) NULL,
    published_at      DATETIME     NULL COMMENT 'NULL = draf; masa depan = berjadual',
    send_email        TINYINT(1)   NOT NULL DEFAULT 0,
    email_sent_at     DATETIME     NULL,
    email_recipients  INT UNSIGNED NOT NULL DEFAULT 0,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_news_vendor (vendor_id, cooperative_id, published_at),
    CONSTRAINT fk_news_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_news_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clearance_sales (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    starts_at       DATETIME     NOT NULL,
    ends_at         DATETIME     NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_clearance_vendor (vendor_id, cooperative_id, starts_at),
    CONSTRAINT fk_clearance_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_clearance_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clearance_items (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id          INT UNSIGNED NOT NULL,
    clearance_sale_id  INT UNSIGNED NOT NULL,
    product_id         INT UNSIGNED NOT NULL,
    variant_id         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = semua saiz',
    sale_price         INT UNSIGNED NOT NULL,
    qty_limit          INT UNSIGNED NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clearance_items (clearance_sale_id, product_id, variant_id),
    KEY idx_clearance_items_vendor (vendor_id, product_id),
    CONSTRAINT fk_clearance_items_sale FOREIGN KEY (clearance_sale_id) REFERENCES clearance_sales (id) ON DELETE CASCADE,
    CONSTRAINT fk_clearance_items_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_clearance_items_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE calendar_events (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,
    event_type      ENUM('school_holiday','coop_closed','school_opening','other') NOT NULL,
    starts_on       DATE         NOT NULL,
    ends_on         DATE         NOT NULL,
    blocks_orders   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_calendar_vendor (vendor_id, cooperative_id, starts_on, ends_on),
    CONSTRAINT fk_calendar_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_calendar_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preorder_seasons (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id         INT UNSIGNED NOT NULL,
    cooperative_id    INT UNSIGNED NOT NULL,
    school_year       SMALLINT UNSIGNED NOT NULL,
    opens_at          DATETIME     NOT NULL,
    closes_at         DATETIME     NOT NULL,
    pickup_starts_on  DATE         NOT NULL,
    note              VARCHAR(255) NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_preorder_seasons (cooperative_id, school_year),
    KEY idx_seasons_vendor (vendor_id, cooperative_id),
    CONSTRAINT fk_seasons_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_seasons_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pickup_slots (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id           INT UNSIGNED NOT NULL,
    cooperative_id      INT UNSIGNED NOT NULL,
    preorder_season_id  INT UNSIGNED NOT NULL,
    slot_date           DATE         NOT NULL,
    time_start          TIME         NOT NULL,
    time_end            TIME         NOT NULL,
    grade_level_code    VARCHAR(5)   NULL COMMENT 'NULL = semua darjah',
    capacity            INT UNSIGNED NOT NULL,
    booked              INT UNSIGNED NOT NULL DEFAULT 0,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_slots_vendor (vendor_id, cooperative_id, preorder_season_id, slot_date),
    CONSTRAINT fk_slots_season FOREIGN KEY (preorder_season_id) REFERENCES preorder_seasons (id),
    CONSTRAINT fk_slots_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_slots_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_queue (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NULL,
    channel         ENUM('push','whatsapp','sms') NOT NULL,
    event           VARCHAR(50)  NOT NULL,
    recipient       VARCHAR(255) NOT NULL,
    message         TEXT         NOT NULL,
    status          ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error      VARCHAR(255) NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at         DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_notifications_vendor (vendor_id, cooperative_id, created_at),
    KEY idx_notifications_pending (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messaging_settings (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id       INT UNSIGNED NOT NULL,
    cooperative_id  INT UNSIGNED NOT NULL,
    provider        ENUM('whatsapp_cloud','twilio','isms') NOT NULL,
    channel         ENUM('whatsapp','sms') NOT NULL,
    sender          VARCHAR(30)  NULL,
    api_key_enc     TEXT         NULL COMMENT 'disulitkan (Crypto)',
    is_enabled      TINYINT(1)   NOT NULL DEFAULT 0,
    events_json     TEXT         NULL,
    templates_json  TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_messaging_cooperative (cooperative_id),
    KEY idx_messaging_vendor (vendor_id),
    CONSTRAINT fk_messaging_cooperative FOREIGN KEY (cooperative_id) REFERENCES cooperatives (id),
    CONSTRAINT fk_messaging_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- I. Sistem
-- =====================================================================

CREATE TABLE email_queue (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id     INT UNSIGNED NULL,
    to_email      VARCHAR(191) NOT NULL,
    subject       VARCHAR(255) NOT NULL,
    body_html     MEDIUMTEXT   NOT NULL,
    template_key  VARCHAR(50)  NULL,
    status        ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error    VARCHAR(255) NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at       DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_email_queue_pending (status, created_at),
    KEY idx_email_queue_vendor (vendor_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
    bucket_key    VARCHAR(191) NOT NULL,
    hits          INT UNSIGNED NOT NULL DEFAULT 0,
    window_start  DATETIME     NOT NULL,
    PRIMARY KEY (bucket_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
