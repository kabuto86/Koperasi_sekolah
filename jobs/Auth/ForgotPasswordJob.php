<?php
// Lupa kata laluan (ibu bapa / vendor / owner): token reset 1 jam melalui e-mel.
// Jawapan SENTIASA sama sama ada e-mel berdaftar atau tidak (tidak dedah akaun).
class ForgotPasswordJob
{
    const TTL_SECONDS = 3600;

    // kawasan → [jadual, pautan reset]
    const AREAS = [
        'customer' => ['customers', 'store/index.php?page=forgot-password&token='],
        'vendor'   => ['vendor_users', 'vendor/reset-password.php?token='],
        'owner'    => ['platform_users', 'owner/reset-password.php?token='],
    ];

    private $area;
    private $email;

    public function __construct($area, $email)
    {
        $this->area = $area;
        $this->email = strtolower(trim((string) $email));
    }

    public function run()
    {
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException(t('error.check_form'), ['email' => t('auth.email_invalid')]);
        }
        // Maksimum 3 e-mel sejam untuk satu alamat — selebihnya diabaikan secara senyap
        if (!RateLimit::attempt('forgot:' . $this->area . ':' . $this->email, 3, 3600)) {
            return ['sent' => true];
        }

        list($table, $linkPath) = self::AREAS[$this->area];
        $extra = $table === 'customers' ? ' AND deleted_at IS NULL' : '';
        $vendorColumn = $table === 'vendor_users' ? ', vendor_id' : ', NULL AS vendor_id';
        $user = Database::fetchOne(
            "SELECT id, full_name, language $vendorColumn FROM $table WHERE email = ? AND is_active = 1" . $extra,
            [$this->email]
        );
        if ($user !== null) {
            $token = AuthToken::issue($this->area, $user['id'], 'reset_password', self::TTL_SECONDS, $user['vendor_id']);
            $email = new QueueEmailJob($user['vendor_id'], $this->email, 'reset_password', $user['language'], [
                'name' => $user['full_name'],
                'link' => View::absoluteUrl($linkPath . $token),
            ]);
            $email->run();
        }
        return ['sent' => true];
    }
}
