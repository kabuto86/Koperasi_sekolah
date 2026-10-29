<?php
// Harga prorata item yang ditambah di tengah kitaran (PELAN 14): harga kitaran × baki hari / hari dalam tempoh,
// dari hari ini hingga current_period_end langganan semasa. Trial → 0 (item dicaj bermula invois pertama selepas trial).
//
//   (new CalculateProrataJob($subscription, 2000))->run()
//   → [ period_start, period_end, days_left, period_days, amount, tax, total, sst_rate, is_prorated, is_trial ]
class CalculateProrataJob
{
    private $subscription;
    private $unitPrice;

    // $subscription = baris Subscriptions::current() (vendor sesi)
    public function __construct(array $subscription, $unitPrice)
    {
        $this->subscription = $subscription;
        $this->unitPrice = (int) $unitPrice;
    }

    public function run()
    {
        $sub = $this->subscription;
        $today = BillingPeriod::today();
        $from = $today < $sub['current_period_start'] ? $sub['current_period_start'] : $today;
        $prorate = BillingPeriod::prorate($this->unitPrice, $from, $sub['current_period_start'], $sub['current_period_end']);
        $isTrial = $sub['status'] === 'trial';
        $amount = $isTrial ? 0 : $prorate['amount'];
        $rate = Subscriptions::sstRate();
        $tax = Subscriptions::tax($amount, $rate);
        return [
            'period_start' => $from,
            'period_end'   => $sub['current_period_end'],
            'days_left'    => $prorate['days_left'],
            'period_days'  => $prorate['period_days'],
            'amount'       => $amount,
            'tax'          => $tax,
            'total'        => $amount + $tax,
            'sst_rate'     => $rate,
            'is_prorated'  => $prorate['days_left'] < $prorate['period_days'],
            'is_trial'     => $isTrial,
        ];
    }

    // Invois prorata `issued` untuk satu item baharu (dalam transaksi pemanggil). Jumlah 0 (trial) → tiada invois, null
    public static function invoice($vendorId, array $prorata, $itemType, $cooperativeId, $description)
    {
        if ($prorata['amount'] <= 0) {
            return null;
        }
        $id = Invoices::create($vendorId, $prorata['period_start'], $prorata['period_end'], [[
            'description'    => $description,
            'item_type'      => $itemType,
            'cooperative_id' => $cooperativeId,
            'qty'            => 1,
            'unit_price'     => $prorata['amount'],
            'amount'         => $prorata['amount'],
            'is_prorated'    => $prorata['is_prorated'],
        ]], 'issued');
        return Database::fetchOne("SELECT id, invoice_no, total FROM invoices WHERE id = ?", [$id]);
    }
}
