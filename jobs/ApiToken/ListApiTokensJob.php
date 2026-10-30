<?php
// Peranti apps yang sedang log masuk (token aktif) untuk ibu bapa sesi / token. Tiada hash dipulangkan.
class ListApiTokensJob
{
    private $customerId;
    private $currentId;

    public function __construct($customerId, $currentId)
    {
        $this->customerId = (int) $customerId;
        $this->currentId = (int) $currentId;
    }

    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT t.id, t.device_name, t.created_at, t.last_used_at, t.expires_at,
                    (SELECT COUNT(*) FROM push_devices d WHERE d.api_token_id = t.id AND d.customer_id = t.customer_id) AS push_devices
               FROM api_tokens t
              WHERE t.customer_id = ? AND t.revoked_at IS NULL AND t.expires_at > NOW()
              ORDER BY t.last_used_at DESC, t.id DESC",
            [$this->customerId]
        );
        $currentId = $this->currentId;
        return ['items' => array_map(function ($row) use ($currentId) {
            return ApiTokens::shape($row, $currentId);
        }, $rows)];
    }
}
