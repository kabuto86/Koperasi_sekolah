SET NAMES utf8mb4;
-- W5 — gambar pratonton sebenar Template 1–3 (tangkap layar halaman utama store, assets/img/templates/). Folder templates/template2
-- & template3 kini lengkap. Template 3 kekal tidak aktif sehingga owner mengaktifkannya (Owner → Template frontend).
-- Pemasangan baharu: seed.sql sudah mengandungi nilai ini.
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/011-template-previews.sql

UPDATE templates SET preview_image = CONCAT('assets/img/templates/', code, '.jpg')
 WHERE code IN ('template1', 'template2', 'template3') AND preview_image IS NULL;
