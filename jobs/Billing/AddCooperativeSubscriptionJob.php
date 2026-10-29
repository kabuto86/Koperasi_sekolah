<?php
// Koperasi tambahan (ke-2 dan seterusnya) — PELAN 14: harga peringkat plan_coop_tiers kitaran semasa disalin ke subscription_items
// (snapshot) + invois prorata `issued` hingga hujung tempoh. Dipanggil oleh SaveCooperativeJob DALAM transaksinya (vendor sudah dikunci).
class AddCooperativeSubscriptionJob
{
    private $vendorId;
    private $cooperativeId;
    private $number;
    private $schoolName;

    public function __construct($vendorId, $cooperativeId, $number, $schoolName)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->number = (int) $number;
        $this->schoolName = (string) $schoolName;
    }

    // Pulangkan invois prorata [id, invoice_no, total], atau null (koperasi pertama / tiada langganan / trial)
    public function run()
    {
        $subscription = Subscriptions::current($this->vendorId, true);
        if ($subscription === null || $this->number < 2) {
            return null;
        }
        $price = GetCooperativePricingJob::priceFor($subscription['plan_id'], $this->number, $subscription['billing_cycle']);
        Database::insert(
            "INSERT INTO subscription_items (vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at)
             VALUES (?, ?, 'cooperative', ?, NULL, ?, CURDATE())",
            [$this->vendorId, (int) $subscription['id'], $this->cooperativeId, $price]
        );
        $prorata = (new CalculateProrataJob($subscription, $price))->run();
        return CalculateProrataJob::invoice($this->vendorId, $prorata, 'cooperative', $this->cooperativeId,
            Invoices::text('billing.line_coop', ['school' => $this->schoolName, 'days' => $prorata['days_left']]));
    }
}
