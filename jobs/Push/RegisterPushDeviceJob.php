<?php
// Daftar token FCM peranti (apps — B15). Satu peranti push setiap token apps: token FCM lama token ini dibuang.
// Token FCM yang sama dari akaun lain (telefon bertukar pengguna) dipindah ke ibu bapa & token apps semasa.
class RegisterPushDeviceJob
{
    const PLATFORMS = ['android', 'ios'];

    private $customerId;
    private $apiTokenId;
    private $input;

    public function __construct($customerId, $apiTokenId, array $input)
    {
        $this->customerId = (int) $customerId;
        $this->apiTokenId = (int) $apiTokenId;
        $this->input = $input;
    }

    public function run()
    {
        $v = new Validator($this->input);
        $v->required('platform')->inList('platform', self::PLATFORMS);
        $v->required('push_token')->pattern('push_token', '/^[A-Za-z0-9:_\-.]{20,255}$/');
        ValidationException::throwIfFails($v);
        $platform = (string) $this->input['platform'];
        $pushToken = (string) $this->input['push_token'];

        Database::beginTransaction();
        try {
            Database::execute(
                "DELETE FROM push_devices WHERE customer_id = ? AND api_token_id = ? AND push_token <> ?",
                [$this->customerId, $this->apiTokenId, $pushToken]
            );
            Database::execute(
                "INSERT INTO push_devices (customer_id, api_token_id, platform, push_token) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), api_token_id = VALUES(api_token_id),
                                         platform = VALUES(platform), updated_at = NOW()",
                [$this->customerId, $this->apiTokenId, $platform, $pushToken]
            );
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return ['registered' => true, 'platform' => $platform];
    }
}
