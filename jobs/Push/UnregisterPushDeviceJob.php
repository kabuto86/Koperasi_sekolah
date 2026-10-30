<?php
// Matikan push untuk peranti apps semasa (token apps kekal sah — B15).
class UnregisterPushDeviceJob
{
    private $customerId;
    private $apiTokenId;

    public function __construct($customerId, $apiTokenId)
    {
        $this->customerId = (int) $customerId;
        $this->apiTokenId = (int) $apiTokenId;
    }

    public function run()
    {
        $count = Database::execute(
            "DELETE FROM push_devices WHERE customer_id = ? AND api_token_id = ?",
            [$this->customerId, $this->apiTokenId]
        );
        return ['removed' => $count];
    }
}
