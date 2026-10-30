<?php
// Sahkan token Bearer apps (ApiTokenAuth). Token = hash SHA-256 sepadan, belum dibatal, belum luput.
// Luput bergerak: digunakan → expires_at dilanjut 90 hari (ditulis paling kerap sekali setiap 5 minit).
class AuthenticateApiTokenJob
{
    private $hash;

    public function __construct($rawToken)
    {
        $this->hash = Crypto::hashToken((string) $rawToken);
    }

    // [id, customer_id, device_name, expires_at] atau null
    public function run()
    {
        $row = Database::fetchOne(
            "SELECT id, customer_id, device_name, expires_at FROM api_tokens
              WHERE token_hash = ? AND revoked_at IS NULL AND expires_at > NOW()",
            [$this->hash]
        );
        if ($row === null) {
            return null;
        }
        Database::execute(
            "UPDATE api_tokens SET last_used_at = NOW(), expires_at = NOW() + INTERVAL " . ApiTokenAuth::LIFETIME_DAYS . " DAY
              WHERE id = ? AND (last_used_at IS NULL OR last_used_at < NOW() - INTERVAL 5 MINUTE)",
            [(int) $row['id']]
        );
        $row['id'] = (int) $row['id'];
        $row['customer_id'] = (int) $row['customer_id'];
        return $row;
    }
}
