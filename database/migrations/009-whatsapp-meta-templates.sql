SET NAMES utf8mb4;
-- W3 — templat WhatsApp diluluskan Meta (WhatsApp Cloud): nama templat + kod bahasa setiap peristiwa (messaging_settings)
-- dan templat + parameter yang dihantar untuk setiap mesej (notification_queue). Tiada nama templat = teks biasa (seperti B13).
-- Pemasangan baharu: schema.sql sudah mengandungi perubahan ini.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/009-whatsapp-meta-templates.sql

ALTER TABLE messaging_settings
    ADD COLUMN meta_templates_json TEXT NULL COMMENT 'W3: {peristiwa: {name, language}} templat diluluskan Meta' AFTER templates_json;

ALTER TABLE notification_queue
    ADD COLUMN template_json TEXT NULL COMMENT 'W3: {name, language, params[]} — WhatsApp Cloud mesej templat' AFTER message;
