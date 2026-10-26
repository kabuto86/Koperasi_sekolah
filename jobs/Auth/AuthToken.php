<?php
// Token sekali guna (sahkan e-mel, reset kata laluan, jemputan kakitangan).
// Token mentah hanya dalam e-mel; DB menyimpan hash SHA-256 + tarikh luput (PELAN 15).
class AuthToken
{
    // kawasan → [jadual, lajur pengguna]
    const TABLES = [
        'customer' => ['customer_tokens', 'customer_id'],
        'vendor'   => ['vendor_user_tokens', 'vendor_user_id'],
        'owner'    => ['platform_user_tokens', 'platform_user_id'],
    ];

    // Pulangkan token mentah. Token lama jenis sama untuk pengguna ini dibatalkan
    public static function issue($area, $userId, $type, $ttlSeconds, $vendorId = null)
    {
        list($table, $column) = self::TABLES[$area];
        $token = Crypto::randomToken(32);
        Database::execute("UPDATE $table SET used_at = NOW() WHERE $column = ? AND type = ? AND used_at IS NULL", [$userId, $type]);

        if ($area === 'vendor') {
            Database::insert(
                "INSERT INTO vendor_user_tokens (vendor_id, vendor_user_id, type, token_hash, expires_at)
                 VALUES (?, ?, ?, ?, NOW() + INTERVAL ? SECOND)",
                [$vendorId, $userId, $type, Crypto::hashToken($token), $ttlSeconds]
            );
        } else {
            Database::insert(
                "INSERT INTO $table ($column, type, token_hash, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ? SECOND)",
                [$userId, $type, Crypto::hashToken($token), $ttlSeconds]
            );
        }
        return $token;
    }

    // Guna token (sekali sahaja). Pulangkan [id pengguna, type] atau null jika tiada / luput / sudah diguna
    public static function consume($area, $token, array $types)
    {
        list($table, $column) = self::TABLES[$area];
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = Database::fetchOne(
            "SELECT id, $column AS user_id, type FROM $table
              WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()",
            [Crypto::hashToken($token)]
        );
        if ($row === null || !in_array($row['type'], $types, true)) {
            return null;
        }
        // Syarat used_at IS NULL sekali lagi — dua permintaan serentak hanya satu berjaya
        $updated = Database::execute("UPDATE $table SET used_at = NOW() WHERE id = ? AND used_at IS NULL", [$row['id']]);
        if ($updated !== 1) {
            return null;
        }
        return ['user_id' => (int) $row['user_id'], 'type' => $row['type']];
    }
}
