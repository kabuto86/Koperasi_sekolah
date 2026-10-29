<?php
// Anggaran harga prorata modul / template hingga hujung tempoh langganan semasa (PELAN 18.10 vendor/addons/quote).
class GetAddonQuoteJob
{
    private $vendorId;
    private $cooperative;
    private $input;

    public function __construct($vendorId, array $cooperative, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->input = $input;
    }

    public function run()
    {
        $subscription = AddonItems::requireSubscription($this->vendorId);
        $item = AddonItems::resolve($this->vendorId, $this->cooperative, $this->input['item_type'] ?? '', $this->input['code'] ?? '',
            $subscription['billing_cycle']);
        if ($item === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $prorata = (new CalculateProrataJob($subscription, $item['price']))->run();
        return [
            'item_name'        => $item['name'],
            'cooperative_name' => $this->cooperative['school_name'],
            'billing_cycle'    => $subscription['billing_cycle'],
            'unit_price'       => $item['price'],
            'period_start'     => $prorata['period_start'],
            'period_end'       => $prorata['period_end'],
            'days_left'        => $prorata['days_left'],
            'period_days'      => $prorata['period_days'],
            'amount'           => $prorata['amount'],
            'tax'              => $prorata['tax'],
            'total'            => $prorata['total'],
            'sst_rate'         => $prorata['sst_rate'],
            'is_trial'         => $prorata['is_trial'],
            'owned'            => $item['owned'],
        ];
    }
}
