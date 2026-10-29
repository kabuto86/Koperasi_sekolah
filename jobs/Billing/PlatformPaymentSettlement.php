<?php
// Putuskan status bayaran yuran platform (PELAN 13 — "jangan percaya redirect"). `paid` HANYA bila status ditanya terus ke API
// gateway owner DAN amaun sama dengan platform_payments.amount DAN invoices.total. Idempotent: baris bayaran dikunci (FOR UPDATE).
// Invois dibayar → vendor pulih jika tiada tunggakan lain. Wang diterima untuk invois yang sudah dibayar / dibatalkan → paid_late
// (owner memulangkan secara manual).
class PlatformPaymentSettlement
{
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
        $peek = Database::fetchOne("SELECT vendor_id FROM platform_payments WHERE id = ?", [(int) $paymentId]);
        if ($peek === null) {
            return 'unknown_reference';
        }
        Subscriptions::lockVendor($peek['vendor_id']);
        $payment = Database::fetchOne("SELECT * FROM platform_payments WHERE id = ? FOR UPDATE", [(int) $paymentId]);
        if ($payment['status'] === 'paid') {
            return 'already_paid';
        }
        $vendorId = (int) $payment['vendor_id'];
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id = ? AND vendor_id = ? FOR UPDATE", [(int) $payment['invoice_id'], $vendorId]);
        if ($status['status'] === 'failed') {
            Database::execute("UPDATE platform_payments SET status = 'failed' WHERE id = ? AND status = 'pending'", [(int) $payment['id']]);
            return 'failed';
        }
        if ($status['status'] !== 'paid') {
            return 'pending';
        }
        $amount = $status['amount'];
        if (($amount === null && !$amountLocked) || ($amount !== null && (int) $amount !== (int) $payment['amount'])
            || (int) $payment['amount'] !== (int) $invoice['total']) {
            return 'amount_mismatch';
        }
        if ($status['currency'] !== null && $status['currency'] !== 'MYR') {
            return 'currency_mismatch';
        }

        $raw = json_decode((string) $payment['raw_payload'], true);
        $raw = is_array($raw) ? $raw : [];
        $raw['verified'] = $status['raw'] ?? null;
        Database::execute("UPDATE platform_payments SET status = 'paid', paid_at = NOW(), raw_payload = ? WHERE id = ?",
            [json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (int) $payment['id']]);
        if ($invoice['status'] !== 'issued') {
            (new WriteAuditLogJob('system', null, $vendorId, 'invoice.paid_late', 'invoice', (int) $invoice['id'],
                $invoice['invoice_no'] . ' · ' . $payment['gateway_ref']))->run();
            return 'paid_late';
        }
        Database::execute("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?", [(int) $invoice['id']]);
        (new WriteAuditLogJob('system', null, $vendorId, 'invoice.paid_online', 'invoice', (int) $invoice['id'],
            $invoice['invoice_no'] . ' · ' . $payment['gateway_ref']))->run();
        VendorBillingStatus::restore($vendorId);
        return 'paid';
    }
}
