<?php
// Tetapan platform (PELAN 18.7 PlatformSettings). Kata laluan SMTP tidak pernah dipulangkan — hanya smtp_password_set.
class GetPlatformSettingsJob
{
    public function run()
    {
        $s = PlatformSettings::all();
        return [
            'platform_name'       => $s['platform_name'] ?? '',
            'support_email'       => $s['support_email'] ?? '',
            'support_phone'       => $s['support_phone'] ?? '',
            'smtp_host'           => $s['smtp_host'] ?? '',
            'smtp_port'           => (int) ($s['smtp_port'] ?? 587),
            'smtp_encryption'     => $s['smtp_encryption'] ?? 'tls',
            'smtp_username'       => $s['smtp_username'] ?? '',
            'smtp_password_set'   => ($s['smtp_password_enc'] ?? '') !== '',
            'mail_from_address'   => $s['mail_from_address'] ?? '',
            'mail_from_name'      => $s['mail_from_name'] ?? '',
            'sst_rate'            => (float) ($s['sst_rate'] ?? 0),
            'sst_registration_no' => $s['sst_registration_no'] ?? '',
            'trial_days'          => (int) ($s['trial_days'] ?? 14),
            'grace_days'          => (int) ($s['grace_days'] ?? 7),
            'default_language'    => $s['default_language'] ?? Config::get('default_language'),
        ];
    }
}
