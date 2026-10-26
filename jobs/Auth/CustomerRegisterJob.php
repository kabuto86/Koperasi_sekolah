<?php
// Daftar akaun ibu bapa (PELAN 18.12 store/auth/register). E-mel & telefon unik seluruh platform.
// Selepas daftar: log masuk terus (pemanggil), e-mel pengesahan melalui email_queue.
class CustomerRegisterJob
{
    const PASSWORD_MIN = 8;

    private $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    // Baris pelanggan baharu
    public function run()
    {
        $data = $this->validate();
        try {
            $customerId = Database::insert(
                "INSERT INTO customers (full_name, phone, email, password_hash, relationship, language, pdpa_consent_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$data['full_name'], $data['phone'], $data['email'], Password::hash($data['password']), $data['relationship'], $data['language']]
            );
        } catch (PDOException $exception) {
            // Dua pendaftaran serentak dengan e-mel / telefon sama → UNIQUE
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new ValidationException(t('error.check_form'), ['email' => t('error.duplicate')]);
            }
            throw $exception;
        }

        $send = new SendVerificationEmailJob($customerId);
        $send->run();
        return (new LoadCustomerSessionJob($customerId))->run();
    }

    private function validate()
    {
        $v = new Validator($this->input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('phone')
          ->required('email')->email('email')->maxLength('email', 191)
          ->required('password')
          ->required('relationship')->inList('relationship', ['mother', 'father', 'guardian'])
          ->required('language')->inList('language', Config::get('languages'));

        $email = strtolower(trim((string) ($this->input['email'] ?? '')));
        $phone = PhoneNumber::normalize($this->input['phone'] ?? '');
        $password = (string) ($this->input['password'] ?? '');

        if (trim((string) ($this->input['phone'] ?? '')) !== '' && $phone === null) {
            $v->addError('phone', t('store.auth.phone_invalid'));
        }
        if ($password !== '' && mb_strlen($password) < self::PASSWORD_MIN) {
            $v->addError('password', t('store.auth.password_min'));
        }
        if ($password !== (string) ($this->input['password_confirm'] ?? '')) {
            $v->addError('password_confirm', t('store.auth.password_mismatch'));
        }
        if (($this->input['pdpa'] ?? false) !== true) {
            $v->addError('pdpa', t('store.auth.pdpa_required'));
        }
        // Mesej umum — tidak dedah butiran akaun lain
        if ($email !== '' && Database::fetchValue("SELECT 1 FROM customers WHERE email = ?", [$email])) {
            $v->addError('email', t('error.duplicate'));
        }
        if ($phone !== null && Database::fetchValue("SELECT 1 FROM customers WHERE phone = ?", [$phone])) {
            $v->addError('phone', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        return [
            'full_name'    => trim((string) $this->input['full_name']),
            'phone'        => $phone,
            'email'        => $email,
            'password'     => $password,
            'relationship' => $this->input['relationship'],
            'language'     => $this->input['language'],
        ];
    }
}
