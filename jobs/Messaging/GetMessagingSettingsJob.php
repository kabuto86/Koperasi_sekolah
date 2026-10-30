<?php
// Tetapan WhatsApp/SMS koperasi (vendor/messaging/get) + pemegang tempat yang dibenarkan. Kunci API tidak dipulangkan.
class GetMessagingSettingsJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $data = MessagingSettings::publicShape(MessagingSettings::find($this->vendorId, $this->cooperativeId));
        $data['placeholders'] = MessagingSettings::PLACEHOLDERS;
        return $data;
    }
}
