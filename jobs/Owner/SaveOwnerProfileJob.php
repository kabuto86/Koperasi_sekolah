<?php
// Kemas kini profil pengguna owner semasa (nama, e-mel, bahasa).
class SaveOwnerProfileJob
{
    private $userId;
    private $input;

    public function __construct($userId, array $input)
    {
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $email = strtolower(trim((string) ($this->input['email'] ?? '')));
        $v = new Validator($this->input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('email')->email('email')->maxLength('email', 191)
          ->required('language')->inList('language', Config::get('languages'));
        if ($email !== '' && Database::fetchValue("SELECT 1 FROM platform_users WHERE email = ? AND id <> ?", [$email, $this->userId])) {
            $v->addError('email', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        Database::execute(
            "UPDATE platform_users SET full_name = ?, email = ?, language = ? WHERE id = ?",
            [trim($this->input['full_name']), $email, $this->input['language'], $this->userId]
        );
        return (new LoadOwnerSessionJob($this->userId))->run();
    }
}
