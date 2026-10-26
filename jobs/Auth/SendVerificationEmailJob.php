<?php
// Token pengesahan e-mel (24 jam) + e-mel ke email_queue. Token lama dibatalkan.
class SendVerificationEmailJob
{
    const TTL_SECONDS = 86400;

    private $customerId;

    public function __construct($customerId)
    {
        $this->customerId = (int) $customerId;
    }

    // [email, verified]
    public function run()
    {
        $customer = Database::fetchOne(
            "SELECT id, full_name, email, language, email_verified_at FROM customers WHERE id = ? AND deleted_at IS NULL",
            [$this->customerId]
        );
        if ($customer['email_verified_at'] !== null) {
            return ['email' => $customer['email'], 'verified' => true];
        }
        $token = AuthToken::issue('customer', $customer['id'], 'verify_email', self::TTL_SECONDS);
        $email = new QueueEmailJob(null, $customer['email'], 'verify_email', $customer['language'], [
            'name' => $customer['full_name'],
            'link' => View::absoluteUrl('store/index.php?' . http_build_query(['verify_token' => $token])),
        ]);
        $email->run();
        return ['email' => $customer['email'], 'verified' => false];
    }
}
