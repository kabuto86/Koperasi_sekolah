<?php
// Sambungan PDO tunggal ke koperasi_saas.
// utf8mb4 pada SAMBUNGAN (bukan hanya jadual) — PELAN 16 perkara 9.
// Hanya job class memanggil kelas ini. Fail api/ tidak menulis SQL.
class Database
{
    private static $pdo = null;

    public static function connection()
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
        }
        return self::$pdo;
    }

    private static function connect()
    {
        $config = self::loadConfig();
        $dsn = 'mysql:host=' . $config['host']
             . ';port=' . (int) $config['port']
             . ';dbname=' . $config['database']
             . ';charset=utf8mb4';

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);

        // Pastikan pelanggan & sambungan sama-sama utf8mb4, zon waktu Malaysia
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("SET time_zone = '+08:00'");

        return $pdo;
    }

    private static function loadConfig()
    {
        $file = APP_ROOT . '/config/database.php';
        if (!is_file($file)) {
            throw new RuntimeException('config/database.php tidak dijumpai. Salin dari config/database.example.php.');
        }
        return require $file;
    }

    // Satu baris atau null
    public static function fetchOne($sql, $params = [])
    {
        $statement = self::run($sql, $params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    // Semua baris (array kosong jika tiada)
    public static function fetchAll($sql, $params = [])
    {
        return self::run($sql, $params)->fetchAll();
    }

    // Nilai lajur pertama baris pertama, atau null
    public static function fetchValue($sql, $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    // INSERT / UPDATE / DELETE — pulangkan bilangan baris terlibat
    public static function execute($sql, $params = [])
    {
        return self::run($sql, $params)->rowCount();
    }

    // INSERT — pulangkan id baharu
    public static function insert($sql, $params = [])
    {
        self::run($sql, $params);
        return (int) self::connection()->lastInsertId();
    }

    public static function beginTransaction()
    {
        self::connection()->beginTransaction();
    }

    public static function commit()
    {
        self::connection()->commit();
    }

    public static function rollBack()
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }

    private static function run($sql, $params)
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}
