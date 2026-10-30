<?php
// Pembantu token apps (B15): bentuk awam & batal token. Setiap pertanyaan menapis customer_id (dari sesi / token).
// Token dibatal → peranti push yang didaftar oleh token itu dibuang (tiada push ke telefon yang sudah log keluar).
class ApiTokens
{
    // Batal semua token aktif ibu bapa (tukar / reset kata laluan), kecuali $keepId (token apps yang membuat perubahan)
    public static function revokeAll($customerId, $keepId = 0)
    {
        Database::execute(
            "DELETE FROM push_devices WHERE customer_id = ? AND api_token_id IS NOT NULL AND api_token_id <> ?",
            [(int) $customerId, (int) $keepId]
        );
        return Database::execute(
            "UPDATE api_tokens SET revoked_at = NOW() WHERE customer_id = ? AND id <> ? AND revoked_at IS NULL",
            [(int) $customerId, (int) $keepId]
        );
    }

    // Batal satu token milik ibu bapa ini. Pulangkan bilangan baris (0 = tiada / milik orang lain / sudah dibatal)
    public static function revoke($customerId, $tokenId)
    {
        $count = Database::execute(
            "UPDATE api_tokens SET revoked_at = NOW() WHERE id = ? AND customer_id = ? AND revoked_at IS NULL",
            [(int) $tokenId, (int) $customerId]
        );
        Database::execute("DELETE FROM push_devices WHERE api_token_id = ? AND customer_id = ?", [(int) $tokenId, (int) $customerId]);
        return $count;
    }

    public static function shape(array $row, $currentId)
    {
        return [
            'id'           => (int) $row['id'],
            'device_name'  => (string) $row['device_name'],
            'created_at'   => IsoDate::format($row['created_at']),
            'last_used_at' => IsoDate::format($row['last_used_at']),
            'expires_at'   => IsoDate::format($row['expires_at']),
            'push_enabled' => (int) $row['push_devices'] > 0,
            'current'      => (int) $row['id'] === (int) $currentId,
        ];
    }
}
