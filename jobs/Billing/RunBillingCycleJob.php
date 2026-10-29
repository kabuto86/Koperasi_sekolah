<?php
// Kitaran bil harian (cron/generate-invoices.php): draf pembaharuan 7 hari lebih awal, kemudian pusingan tempoh yang tamat.
// Hanya langganan terkini setiap vendor. Satu langganan gagal tidak menghentikan yang lain (dilog).
class RunBillingCycleJob
{
    public function run()
    {
        $marks = implode(',', array_fill(0, count(Subscriptions::RENEWING_STATUSES), '?'));
        $latest = "s.id = (SELECT MAX(s2.id) FROM subscriptions s2 WHERE s2.vendor_id = s.vendor_id) AND s.status IN ($marks)";

        $drafts = 0;
        $upcoming = Database::fetchAll(
            "SELECT s.id FROM subscriptions s WHERE $latest
                AND s.current_period_end BETWEEN CURDATE() AND CURDATE() + INTERVAL " . GenerateInvoiceJob::LEAD_DAYS . " DAY",
            Subscriptions::RENEWING_STATUSES
        );
        foreach ($upcoming as $row) {
            $drafts += self::attempt(function () use ($row) {
                return (new GenerateInvoiceJob($row['id']))->run() !== null;
            }, $row['id']);
        }

        $renewed = 0;
        $ended = Database::fetchAll("SELECT s.id FROM subscriptions s WHERE $latest AND s.current_period_end < CURDATE()",
            Subscriptions::RENEWING_STATUSES);
        foreach ($ended as $row) {
            $renewed += self::attempt(function () use ($row) {
                return (new RenewSubscriptionJob($row['id']))->run() !== null;
            }, $row['id']);
        }
        return ['drafts' => $drafts, 'renewed' => $renewed];
    }

    private static function attempt(callable $fn, $subscriptionId)
    {
        try {
            return $fn() ? 1 : 0;
        } catch (Throwable $error) {
            error_log('[KoperasiKu] bil langganan ' . $subscriptionId . ': ' . $error->getMessage());
            return 0;
        }
    }
}
