<?php
// Log masuk ibu bapa dengan e-mel ATAU no. telefon + kata laluan (PELAN 11).
class CustomerLoginJob
{
    private $login;
    private $password;

    public function __construct(array $input)
    {
        $this->login = strtolower(trim((string) ($input['login'] ?? '')));
        $this->password = (string) ($input['password'] ?? '');
    }

    // Baris pelanggan (LoadCustomerSessionJob::publicShape untuk jawapan)
    public function run()
    {
        $errors = [];
        if ($this->login === '') {
            $errors['login'] = t('error.required');
        }
        if ($this->password === '') {
            $errors['password'] = t('error.required');
        }
        if ($errors) {
            throw new ValidationException(t('error.check_form'), $errors);
        }

        // Pengecam kunci: telefon dinormalkan supaya 0123456701 dan 012-3456701 dikira sama
        $phone = PhoneNumber::normalize($this->login);
        $identifier = $phone !== null ? $phone : $this->login;
        if (LoginLockout::isLocked('customer', $identifier)) {
            LoginLockout::record('customer', $identifier, false);
            throw new ValidationException(t('store.auth.locked'), [], 429);
        }

        $customer = Database::fetchOne(
            "SELECT id, password_hash FROM customers
              WHERE " . ($phone !== null ? "phone = ?" : "email = ?") . " AND is_active = 1 AND deleted_at IS NULL",
            [$identifier]
        );
        $valid = Password::verify($this->password, $customer['password_hash'] ?? null) && $customer !== null;
        LoginLockout::record('customer', $identifier, $valid);
        if (!$valid) {
            if ($customer !== null) {
                LoginLockout::markUser('customers', $customer['id'], false);
            }
            throw new ValidationException(t('store.auth.invalid'));
        }

        LoginLockout::markUser('customers', $customer['id'], true);
        if (Password::needsRehash($customer['password_hash'])) {
            Database::execute("UPDATE customers SET password_hash = ? WHERE id = ?", [Password::hash($this->password), $customer['id']]);
        }
        return (new LoadCustomerSessionJob($customer['id']))->run();
    }
}
