<?php
// Kemas kini profil ibu bapa sesi (nama, telefon, hubungan, bahasa). E-mel tidak boleh ditukar di sini.
class SaveCustomerProfileJob
{
    private $customerId;
    private $input;

    public function __construct($customerId, array $input)
    {
        $this->customerId = (int) $customerId;
        $this->input = $input;
    }

    public function run()
    {
        $phone = PhoneNumber::normalize($this->input['phone'] ?? '');
        $v = new Validator($this->input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('phone')
          ->required('relationship')->inList('relationship', ['mother', 'father', 'guardian'])
          ->required('language')->inList('language', Config::get('languages'));
        if (trim((string) ($this->input['phone'] ?? '')) !== '' && $phone === null) {
            $v->addError('phone', t('store.auth.phone_invalid'));
        }
        if ($phone !== null && Database::fetchValue("SELECT 1 FROM customers WHERE phone = ? AND id <> ?", [$phone, $this->customerId])) {
            $v->addError('phone', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        Database::execute(
            "UPDATE customers SET full_name = ?, phone = ?, relationship = ?, language = ? WHERE id = ? AND deleted_at IS NULL",
            [trim($this->input['full_name']), $phone, $this->input['relationship'], $this->input['language'], $this->customerId]
        );
        return (new LoadCustomerSessionJob($this->customerId))->run();
    }
}
