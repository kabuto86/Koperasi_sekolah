SET NAMES utf8mb4;
-- Cipta pangkalan data & pengguna MySQL untuk KoperasiKu (jalankan SEKALI sebagai root).
-- Tukar GANTI_KATA_LALUAN_KUAT dahulu, kemudian salin nilai yang sama ke config/database.php.
--   mysql --default-character-set=utf8mb4 -u root -p < database/create-database.sql
-- Pangkalan data tuisyen & pos_saas milik projek lain — fail ini tidak menyentuhnya.

CREATE DATABASE IF NOT EXISTS koperasi_saas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'koperasi_user'@'localhost' IDENTIFIED BY 'GANTI_KATA_LALUAN_KUAT';

-- Akses koperasi_saas SAHAJA (bukan root, bukan *.*)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES
    ON koperasi_saas.* TO 'koperasi_user'@'localhost';

FLUSH PRIVILEGES;
