<?php
// Refund gateway yang tersangkut `pending` (proses mati selepas baris dicipta, sebelum / selepas gateway menerima) — W2.
// Dicuba semula oleh cron/retry-refunds.php: refund_no = kunci idempotensi gateway, jadi refund yang sudah diterima gateway
// tidak dibuat dua kali. Hanya refund berumur >= MIN_AGE_MINUTES (elak berlumba dengan permintaan yang masih berjalan) dan
// < MAX_AGE_HOURS (kunci idempotensi Stripe sah 24 jam) — yang lebih lama dikira "perlu semakan manual" (kekal pending).
// Gateway tiada lagi / tidak sokong refund → failed (unit & baki dilepaskan).
class RetryPendingRefundsJob
{
    const MIN_AGE_MINUTES = 10;
    const MAX_AGE_HOURS = 23;

    // [completed, failed, manual]
    public function run()
    {
        $counts = ['completed' => 0, 'failed' => 0, 'manual' => 0];
        $counts['manual'] = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM refunds WHERE method = 'gateway' AND status = 'pending' AND created_at < NOW() - INTERVAL " . self::MAX_AGE_HOURS . " HOUR"
        );
        $rows = Database::fetchAll(
            "SELECT id, vendor_id, cooperative_id, refund_no, order_id, payment_id, amount, reason, restock, vendor_user_id
               FROM refunds
              WHERE method = 'gateway' AND status = 'pending'
                AND created_at < NOW() - INTERVAL " . self::MIN_AGE_MINUTES . " MINUTE
                AND created_at >= NOW() - INTERVAL " . self::MAX_AGE_HOURS . " HOUR
              ORDER BY id LIMIT 100"
        );
        foreach ($rows as $refund) {
            $status = $this->retry($refund);
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        return $counts;
    }

    private function retry(array $refund)
    {
        $vendorId = (int) $refund['vendor_id'];
        $cooperative = Database::fetchOne("SELECT * FROM cooperatives WHERE id = ? AND vendor_id = ?", [(int) $refund['cooperative_id'], $vendorId]);
        $order = Database::fetchOne("SELECT * FROM orders WHERE id = ? AND vendor_id = ?", [(int) $refund['order_id'], $vendorId]);
        $payment = Database::fetchOne("SELECT * FROM payments WHERE id = ? AND vendor_id = ?", [(int) $refund['payment_id'], $vendorId]);
        $gateway = ($cooperative && $order && $payment) ? Refunds::gatewayFor($order, $payment) : null;
        if ($gateway === null) {
            Database::execute("UPDATE refunds SET status = 'failed' WHERE id = ? AND vendor_id = ? AND status = 'pending'", [(int) $refund['id'], $vendorId]);
            $this->log($refund, 'refund_failed');
            return 'failed';
        }
        try {
            $result = GatewayRefundSender::send($vendorId, $cooperative, $refund, $gateway, $refund['vendor_user_id']);
        } catch (Throwable $error) {
            error_log('[KoperasiKu] cuba semula refund ' . $refund['refund_no'] . ': ' . $error->getMessage());
            return 'error';
        }
        if (in_array($result['status'], ['completed', 'failed'], true)) {
            $this->log($refund, $result['status'] === 'completed' ? 'refund_create' : 'refund_failed');
        }
        return $result['status'];
    }

    private function log(array $refund, $action)
    {
        (new WriteActivityLogJob($refund['vendor_id'], (int) $refund['cooperative_id'], $refund['vendor_user_id'], $action, 'refund',
            (int) $refund['id'], $refund['refund_no'] . ' · ' . Money::format($refund['amount']) . ' · ' . t('refund.retried')))->run();
    }
}
