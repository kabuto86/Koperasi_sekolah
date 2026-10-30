<?php
// Mesej ujian (vendor/messaging/test): tetapan mesti dihidupkan; no. telefon Malaysia 01x. Masuk baris gilir (status pending).
class SendTestMessageJob
{
    private $cooperative;
    private $phone;

    // $cooperative = baris VendorAuth::requireCooperative (milik vendor sesi)
    public function __construct(array $cooperative, $phone)
    {
        $this->cooperative = $cooperative;
        $this->phone = (string) $phone;
    }

    public function run()
    {
        $settings = MessagingSettings::find($this->cooperative['vendor_id'], $this->cooperative['id']);
        if ($settings === null || (int) $settings['is_enabled'] !== 1) {
            throw new ValidationException(t('messaging.enable_first'));
        }
        $phone = MessagingSettings::phone($this->phone);
        if ($phone === null) {
            throw new ValidationException(t('error.check_form'), ['phone' => t('store.auth.phone_invalid')]);
        }
        return ['id' => (int) MessageNotifications::test($this->cooperative, $phone)];
    }
}
