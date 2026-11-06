<?php
// Kemas kini profil kakitangan vendor semasa (nama, e-mel, telefon, bahasa). Peranan & akses koperasi diurus pentadbir.
class SaveVendorProfileJob
{
    private $vendorId;
    private $userId;
    private $input;

    public function __construct($vendorId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $email = strtolower(trim((string) ($this->input['email'] ?? '')));
        $phone = trim((string) ($this->input['phone'] ?? ''));
        $v = new Validator($this->input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('email')->email('email')->maxLength('email', 191)
          ->maxLength('phone', 20)
          ->required('language')->inList('language', Config::get('languages'));
        if ($email !== '' && Database::fetchValue("SELECT 1 FROM vendor_users WHERE email = ? AND id <> ?", [$email, $this->userId])) {
            $v->addError('email', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        Database::execute(
            "UPDATE vendor_users SET full_name = ?, email = ?, phone = ?, language = ? WHERE id = ? AND vendor_id = ?",
            [
                trim($this->input['full_name']), $email, $phone === '' ? null : $phone,
                $this->input['language'], $this->userId, $this->vendorId,
            ]
        );
        (new WriteActivityLogJob($this->vendorId, null, $this->userId, 'profile_update', 'vendor_user', $this->userId, $email))->run();
        return self::profile((new LoadVendorSessionJob($this->userId, $this->vendorId))->run());
    }

    // Medan profil yang dipulangkan kepada pelayar
    public static function profile(array $user)
    {
        return [
            'id'           => $user['id'],
            'full_name'    => $user['full_name'],
            'email'        => $user['email'],
            'phone'        => $user['phone'],
            'language'     => $user['language'],
            'role'         => $user['role'],
            'company_name' => $user['company_name'],
            'totp_enabled' => $user['totp_enabled'],
        ];
    }
}
