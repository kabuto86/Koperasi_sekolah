<?php
// Simpan tetapan platform. smtp_password kosong = kekalkan kata laluan lama; diisi = disulitkan (Crypto).
// Kadar SST baharu hanya untuk invois baharu (invois menyimpan sst_rate sendiri).
class SavePlatformSettingsJob
{
    const TEXT_FIELDS = ['platform_name', 'support_email', 'support_phone', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username',
        'mail_from_address', 'mail_from_name', 'sst_rate', 'sst_registration_no', 'trial_days', 'grace_days', 'default_language'];

    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $v = new Validator($this->input);
        $v->required('platform_name')->maxLength('platform_name', 100)
          ->required('support_email')->email('support_email')->maxLength('support_email', 191)
          ->maxLength('support_phone', 20)
          ->required('smtp_host')->maxLength('smtp_host', 191)
          ->required('smtp_port')->integer('smtp_port', 1, 65535)
          ->inList('smtp_encryption', ['tls', 'ssl', 'none'])
          ->maxLength('smtp_username', 191)->maxLength('smtp_password', 255)
          ->required('mail_from_address')->email('mail_from_address')->maxLength('mail_from_name', 100)
          ->maxLength('sst_registration_no', 50)
          ->required('trial_days')->integer('trial_days', 0, 365)
          ->required('grace_days')->integer('grace_days', 0, 90)
          ->inList('default_language', Config::get('languages'));
        $sst = $this->input['sst_rate'] ?? null;
        if ($sst !== null && $sst !== '' && (!is_numeric($sst) || $sst < 0 || $sst > 100)) {
            $v->addError('sst_rate', t('error.invalid'));
        }
        ValidationException::throwIfFails($v);

        Database::beginTransaction();
        foreach (self::TEXT_FIELDS as $field) {
            $value = $this->input[$field] ?? '';
            if ($field === 'sst_rate') {
                $value = ($value === null || $value === '') ? 0 : round((float) $value, 2);
            }
            if ($field === 'smtp_encryption' && $value === '') {
                $value = 'tls';
            }
            if ($field === 'default_language' && $value === '') {
                $value = Config::get('default_language');
            }
            PlatformSettings::set($field, is_string($value) ? trim($value) : $value, $this->actorId);
        }
        $password = (string) ($this->input['smtp_password'] ?? '');
        if ($password !== '') {
            PlatformSettings::set('smtp_password_enc', Crypto::encrypt($password), $this->actorId);
        }
        Database::commit();

        (new WriteAuditLogJob('owner', $this->actorId, null, 'settings.update', 'platform_settings', null,
            $password !== '' ? 'smtp_password' : ''))->run();
        return (new GetPlatformSettingsJob())->run();
    }
}
