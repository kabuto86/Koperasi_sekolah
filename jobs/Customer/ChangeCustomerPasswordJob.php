<?php
// Tukar kata laluan ibu bapa sesi (kata laluan semasa wajib, baharu ≥ 8 aksara).
// B15: token apps lain dibatal (kecuali token apps yang membuat perubahan ini — $keepTokenId).
class ChangeCustomerPasswordJob
{
    const PASSWORD_MIN = 8;

    private $customerId;
    private $input;
    private $keepTokenId;

    public function __construct($customerId, array $input, $keepTokenId = 0)
    {
        $this->customerId = (int) $customerId;
        $this->input = $input;
        $this->keepTokenId = (int) $keepTokenId;
    }

    public function run()
    {
        $password = (string) ($this->input['password'] ?? '');
        $hash = Database::fetchValue("SELECT password_hash FROM customers WHERE id = ?", [$this->customerId]);

        $v = new Validator($this->input);
        if (!Password::verify((string) ($this->input['current_password'] ?? ''), $hash)) {
            $v->addError('current_password', t('store.profile.current_wrong'));
        }
        if (mb_strlen($password) < self::PASSWORD_MIN) {
            $v->addError('password', t('store.auth.password_min'));
        }
        if ($password !== (string) ($this->input['password_confirm'] ?? '')) {
            $v->addError('password_confirm', t('store.auth.password_mismatch'));
        }
        ValidationException::throwIfFails($v);

        Database::execute("UPDATE customers SET password_hash = ? WHERE id = ?", [Password::hash($password), $this->customerId]);
        ApiTokens::revokeAll($this->customerId, $this->keepTokenId);
        return ['changed' => true];
    }
}
