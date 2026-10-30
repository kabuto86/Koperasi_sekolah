<?php
// Tetapkan kata laluan baharu dari pautan e-mel (reset, atau jemputan kakitangan vendor / owner).
class ResetPasswordJob
{
    // kawasan → [jadual, panjang minimum, jenis token]
    const AREAS = [
        'customer' => ['customers', 8, ['reset_password']],
        'vendor'   => ['vendor_users', 10, ['reset_password', 'invite']],
        'owner'    => ['platform_users', 10, ['reset_password', 'invite']],
    ];

    private $area;
    private $input;

    public function __construct($area, array $input)
    {
        $this->area = $area;
        $this->input = $input;
    }

    public function run()
    {
        list($table, $minLength, $types) = self::AREAS[$this->area];
        $password = (string) ($this->input['password'] ?? '');

        $v = new Validator($this->input);
        $v->required('password');
        if ($password !== '' && mb_strlen($password) < $minLength) {
            $v->addError('password', $this->area === 'customer' ? t('store.auth.password_min') : t('profile.password_too_short'));
        }
        if ($password !== (string) ($this->input['password_confirm'] ?? '')) {
            $v->addError('password_confirm', t('store.auth.password_mismatch'));
        }
        ValidationException::throwIfFails($v);

        $row = AuthToken::consume($this->area, (string) ($this->input['token'] ?? ''), $types);
        if ($row === null) {
            throw new ValidationException(t('store.auth.token_invalid'));
        }
        Database::execute(
            "UPDATE $table SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?",
            [Password::hash($password), $row['user_id']]
        );
        // B15: kata laluan baharu → semua token apps ibu bapa dibatal
        if ($this->area === 'customer') {
            ApiTokens::revokeAll($row['user_id']);
        }
        return ['reset' => true];
    }
}
