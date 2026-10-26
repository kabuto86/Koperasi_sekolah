<?php
// Harga koperasi ikut peringkat plan_coop_tiers (PELAN 14). Koperasi ke-1 termasuk dalam harga pelan;
// koperasi ke-n (n ≥ 2) dicaj harga peringkat yang meliputi n. Digunakan oleh langganan & invois (B12).
//
//   (new CalculateCoopPriceJob($planId, 4, 'monthly'))->run()
//   → [ 'plan_price' => 9900, 'lines' => [ [coop_no 1, price 0], [2, 7900], [3, 7900], [4, 5900] ], 'extra_total' => 21700, 'total' => 31600 ]
class CalculateCoopPriceJob
{
    private $planId;
    private $coopCount;
    private $cycle;

    public function __construct($planId, $coopCount, $cycle)
    {
        $this->planId = (int) $planId;
        $this->coopCount = max(1, (int) $coopCount);
        $this->cycle = $cycle === 'yearly' ? 'yearly' : 'monthly';
    }

    public function run()
    {
        $column = 'price_' . $this->cycle;
        $planPrice = Database::fetchValue("SELECT $column FROM plans WHERE id = ?", [$this->planId]);
        if ($planPrice === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $tiers = GetPlanTiersJob::tiers($this->planId);

        $lines = [['coop_no' => 1, 'price' => 0]];
        $extraTotal = 0;
        for ($coopNo = 2; $coopNo <= $this->coopCount; $coopNo++) {
            $price = self::priceFor($tiers, $coopNo, $column, (int) $planPrice);
            $lines[] = ['coop_no' => $coopNo, 'price' => $price];
            $extraTotal += $price;
        }
        return [
            'plan_price'  => (int) $planPrice,
            'lines'       => $lines,
            'extra_total' => $extraTotal,
            'total'       => (int) $planPrice + $extraTotal,
        ];
    }

    // Tiada peringkat meliputi n (data lama tidak lengkap) → harga sama seperti pelan (lalai PELAN 14)
    private static function priceFor(array $tiers, $coopNo, $column, $planPrice)
    {
        foreach ($tiers as $tier) {
            if ($coopNo >= $tier['from_coop'] && ($tier['to_coop'] === null || $coopNo <= $tier['to_coop'])) {
                return $tier[$column];
            }
        }
        return $planPrice;
    }
}
