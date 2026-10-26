<?php
// Pautan dari e-mel pengesahan: token sekali guna → email_verified_at.
class VerifyEmailJob
{
    private $token;

    public function __construct($token)
    {
        $this->token = trim((string) $token);
    }

    public function run()
    {
        $row = AuthToken::consume('customer', $this->token, ['verify_email']);
        if ($row === null) {
            throw new ValidationException(t('store.auth.token_invalid'));
        }
        Database::execute(
            "UPDATE customers SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ? AND deleted_at IS NULL",
            [$row['user_id']]
        );
        return ['verified' => true];
    }
}
