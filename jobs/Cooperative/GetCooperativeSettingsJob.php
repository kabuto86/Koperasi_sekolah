<?php
// Tetapan koperasi (PELAN 18.10 CooperativeSettings — vendor/settings/get). default_language dari cooperatives,
// selebihnya cooperative_settings (CooperativeSettings::DEFAULTS bila belum disimpan).
// $full = false (juruwang / pengurus: POS & skrin serah) → tanpa alert_emails.
class GetCooperativeSettingsJob
{
    private $cooperative;
    private $full;

    public function __construct(array $cooperative, $full = true)
    {
        $this->cooperative = $cooperative;
        $this->full = $full;
    }

    public function run()
    {
        $vendorId = (int) $this->cooperative['vendor_id'];
        $cooperativeId = (int) $this->cooperative['id'];
        $settings = CooperativeSettings::all($vendorId, $cooperativeId) + CooperativeSettings::DEFAULTS;
        $pickup = VendorOrders::pickupSettings($vendorId, $cooperativeId);
        $data = [
            'default_language'           => Database::fetchValue("SELECT default_language FROM cooperatives WHERE id = ? AND vendor_id = ?",
                [$cooperativeId, $vendorId]),
            'receipt_header'             => (string) ($settings['receipt_header'] ?? ''),
            'receipt_footer'             => (string) $settings['receipt_footer'],
            'receipt_show_logo'          => $settings['receipt_show_logo'] === '1',
            'receipt_paper'              => $settings['receipt_paper'] === '58' ? '58' : '80',
            'tax_rate'                   => 0 + (float) $settings['tax_rate'],
            'pickup_methods'             => $pickup['methods'],
            'pickup_code_required_above' => $pickup['code_required_above'],
            'cash_order_expiry_days'     => (int) $settings['cash_order_expiry_days'],
            'low_stock_default'          => (int) $settings['low_stock_default'],
        ];
        if ($this->full) {
            $data = ['alert_emails' => OrderEmails::alertEmails($this->cooperative)] + $data;
        }
        return $data;
    }
}
