<?php
// Batal token apps ibu bapa: token semasa (log keluar apps) atau token lain dari senarai peranti (sesi web / apps).
// customer_id dari sesi / token; id token dari input disemak milik ibu bapa ini (404 jika tidak).
class RevokeApiTokenJob
{
    private $customerId;
    private $tokenId;

    public function __construct($customerId, $tokenId)
    {
        $this->customerId = (int) $customerId;
        $this->tokenId = (int) $tokenId;
    }

    public function run()
    {
        if ($this->tokenId <= 0 || ApiTokens::revoke($this->customerId, $this->tokenId) === 0) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return ['revoked' => true];
    }
}
