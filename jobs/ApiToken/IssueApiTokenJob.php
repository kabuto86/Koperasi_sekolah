<?php
// Keluarkan token apps selepas log masuk (store/auth/token — CustomerLoginJob dahulu). Token mentah dipulangkan SEKALI sahaja;
// DB menyimpan SHA-256. Had 10 token aktif setiap ibu bapa — yang paling lama digunakan dibatal.
class IssueApiTokenJob
{
    const DEVICE_NAME_MAX = 100;

    private $customerId;
    private $deviceName;

    public function __construct($customerId, $deviceName)
    {
        $this->customerId = (int) $customerId;
        $this->deviceName = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $deviceName));
    }

    public function run()
    {
        if (mb_strlen($this->deviceName, 'UTF-8') > self::DEVICE_NAME_MAX) {
            throw new ValidationException(t('error.check_form'), ['device_name' => t('error.max_length', ['max' => self::DEVICE_NAME_MAX])]);
        }
        $name = $this->deviceName === '' ? t('api.device_default') : $this->deviceName;
        $raw = Crypto::randomToken(32);

        Database::beginTransaction();
        try {
            // Kunci baris ibu bapa: dua log masuk serentak tidak melepasi had token aktif
            Database::fetchValue("SELECT id FROM customers WHERE id = ? FOR UPDATE", [$this->customerId]);
            $id = Database::insert(
                "INSERT INTO api_tokens (customer_id, token_hash, device_name, last_used_at, expires_at)
                 VALUES (?, ?, ?, NOW(), NOW() + INTERVAL " . ApiTokenAuth::LIFETIME_DAYS . " DAY)",
                [$this->customerId, Crypto::hashToken($raw), $name]
            );
            $this->revokeOverLimit();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        $expires = Database::fetchValue("SELECT expires_at FROM api_tokens WHERE id = ? AND customer_id = ?", [$id, $this->customerId]);
        return ['token' => $raw, 'token_id' => (int) $id, 'expires_at' => IsoDate::format($expires)];
    }

    private function revokeOverLimit()
    {
        $old = Database::fetchAll(
            "SELECT id FROM api_tokens WHERE customer_id = ? AND revoked_at IS NULL AND expires_at > NOW()
              ORDER BY last_used_at DESC, id DESC LIMIT 1000 OFFSET " . ApiTokenAuth::MAX_ACTIVE_PER_CUSTOMER,
            [$this->customerId]
        );
        foreach ($old as $row) {
            ApiTokens::revoke($this->customerId, $row['id']);
        }
    }
}
