<?php
// Tarikh tempoh langganan (PELAN 14). Tarikh 'YYYY-MM-DD' (zon Asia/Kuala_Lumpur). Tiada SQL.
//
//   BillingPeriod::endFor('2026-01-31', 'monthly')  → '2026-02-27' (tempoh baharu bermula 2026-02-28)
//   BillingPeriod::days('2026-10-11', '2026-11-10') → 31 (termasuk kedua-dua hari)
class BillingPeriod
{
    const CYCLES = ['monthly', 'yearly'];

    // Hari terakhir tempoh yang bermula $start. Hujung bulan dikekalkan (31 Jan + 1 bulan = 28 Feb, bukan 3 Mac)
    public static function endFor($start, $cycle)
    {
        $months = $cycle === 'yearly' ? 12 : 1;
        return self::addDays(self::addMonths($start, $months), -1);
    }

    // Bilangan hari dari $from hingga $to (termasuk kedua-dua hari)
    public static function days($from, $to)
    {
        $a = new DateTimeImmutable($from);
        $b = new DateTimeImmutable($to);
        return (int) $a->diff($b)->format('%r%a') + 1;
    }

    public static function addDays($date, $days)
    {
        return (new DateTimeImmutable($date))->modify(($days >= 0 ? '+' : '') . (int) $days . ' day')->format('Y-m-d');
    }

    public static function addMonths($date, $months)
    {
        list($year, $month, $day) = array_map('intval', explode('-', $date));
        $index = $year * 12 + ($month - 1) + (int) $months;
        $year = intdiv($index, 12);
        $month = $index % 12 + 1;
        $last = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        return sprintf('%04d-%02d-%02d', $year, $month, min($day, $last));
    }

    public static function today()
    {
        return date('Y-m-d');
    }

    // Prorata = harga kitaran × baki hari / hari dalam tempoh, dari $from hingga $periodEnd (PELAN 14)
    public static function prorate($price, $from, $periodStart, $periodEnd)
    {
        $periodDays = max(1, self::days($periodStart, $periodEnd));
        $daysLeft = min($periodDays, max(1, self::days($from, $periodEnd)));
        return [
            'days_left'   => $daysLeft,
            'period_days' => $periodDays,
            'amount'      => (int) round($price * $daysLeft / $periodDays),
        ];
    }
}
