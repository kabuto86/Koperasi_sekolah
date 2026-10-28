<?php
// Putuskan status bayaran online (PELAN 13 — "jangan percaya redirect"). Dipanggil oleh panggilan balik gateway,
// semakan semula selepas ibu bapa kembali dari gateway, dan cron luput. `paid` HANYA bila:
//   1. status ditanya terus ke API gateway (fetchStatus, pelayan ke pelayan), DAN
//   2. amaun (sen) & mata wang MYR sama dengan payments.amount DAN orders.total.
// Idempotent: baris payments dikunci (FOR UPDATE); yang sudah `paid` tidak diproses lagi → satu bayaran sahaja.
class PaymentSettlement
{
    // Tanya gateway, kemudian simpan keputusan. Pulangkan kod hasil (paid, already_paid, pending, failed, amount_mismatch, …).
    // GatewayException (rangkaian) dibiarkan naik — pemanggil memutuskan sama ada cuba semula
    public static function recheck(array $payment, GatewayInterface $driver)
    {
        $status = $driver->fetchStatus($payment['gateway_ref'], (int) $payment['amount']);
        return self::apply((int) $payment['id'], $status, $driver->amountLocked());
    }

    public static function apply($paymentId, array $status, $amountLocked)
    {
        Database::beginTransaction();
        try {
            $result = self::applyLocked($paymentId, $status, $amountLocked);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return $result;
    }

    private static function applyLocked($paymentId, array $status, $amountLocked)
    {
        $payment = Database::fetchOne("SELECT * FROM payments WHERE id = ? FOR UPDATE", [(int) $paymentId]);
        if ($payment === null) {
            return 'unknown_reference';
        }
        if ($payment['status'] === 'paid' || $payment['status'] === 'refunded') {
            return 'already_paid';
        }
        $vendorId = (int) $payment['vendor_id'];
        $order = Database::fetchOne("SELECT * FROM orders WHERE id = ? AND vendor_id = ? FOR UPDATE", [(int) $payment['order_id'], $vendorId]);

        if ($status['status'] === 'failed') {
            Database::execute("UPDATE payments SET status = 'failed' WHERE id = ? AND status = 'pending'", [(int) $payment['id']]);
            return 'failed';
        }
        if ($status['status'] !== 'paid') {
            return 'pending';
        }
        $amount = $status['amount'];
        if (($amount === null && !$amountLocked) || ($amount !== null && (int) $amount !== (int) $payment['amount'])
            || (int) $payment['amount'] !== (int) $order['total']) {
            return 'amount_mismatch';
        }
        if ($status['currency'] !== null && $status['currency'] !== 'MYR') {
            return 'currency_mismatch';
        }

        $raw = json_decode((string) $payment['raw_payload'], true);
        $raw = is_array($raw) ? $raw : [];
        $raw['verified'] = $status['raw'] ?? null;
        Database::execute(
            "UPDATE payments SET status = 'paid', verified_at = NOW(), raw_payload = ? WHERE id = ?",
            [json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (int) $payment['id']]
        );

        if ($order['status'] === 'pending_payment') {
            Database::execute(
                "UPDATE orders SET status = 'paid', paid_at = NOW(), expires_at = NULL WHERE id = ? AND vendor_id = ?",
                [(int) $order['id'], $vendorId]
            );
            CustomerOrders::logStatus($order, 'paid', 'system', null);
            // B9: e-mel "bayaran diterima" kepada ibu bapa (sekali sahaja — baris payments sudah dikunci)
            OrderEmails::queuePaid($order, $payment['gateway_ref']);
            return 'paid';
        }
        // Wang diterima selepas pesanan dibatal / luput, atau bil kedua untuk pesanan yang sudah dibayar → vendor perlu refund (B11)
        $language = Database::fetchValue("SELECT default_language FROM cooperatives WHERE id = ? AND vendor_id = ?",
            [(int) $order['cooperative_id'], $vendorId]);
        CustomerOrders::logStatus($order, $order['status'], 'system', null,
            Lang::getFor((string) $language, 'payment.paid_after_close', ['ref' => $payment['gateway_ref']]));
        return 'paid_late';
    }
}
