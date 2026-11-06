<?php
// Langkah 2 & 3 refund gateway (dikongsi RefundOrderJob & RetryPendingRefundsJob — W2):
//   (2) driver->refund() DI LUAR transaksi (refund_no sebagai kunci idempotensi — Stripe Idempotency-Key, cuba semula selamat);
//   (3) transaksi: baris refund dikunci & masih `pending` → completed + gateway_ref + Refunds::apply (stok & status pesanan);
//       gateway menolak → failed (tiada kesan stok / status). Pulangkan $refund dengan status completed | failed | (status semasa
//       jika proses lain sudah menyelesaikannya).
class GatewayRefundSender
{
    // $gateway = [baris cooperative_gateways, driver] dari Refunds::gatewayFor
    public static function send($vendorId, array $cooperative, array $refund, array $gateway, $userId)
    {
        $vendorId = (int) $vendorId;
        list($row, $driver) = $gateway;
        $payment = Database::fetchOne("SELECT p.* FROM payments p JOIN refunds r ON r.payment_id = p.id AND r.vendor_id = p.vendor_id
             WHERE r.id = ? AND r.vendor_id = ?", [(int) $refund['id'], $vendorId]);
        try {
            $result = $driver->refund($payment['gateway_ref'], $refund['amount'], $refund['refund_no']);
        } catch (GatewayException $error) {
            error_log('refund gateway ' . $row['gateway_code'] . ' ' . $refund['refund_no'] . ': ' . $error->getMessage());
            Database::execute("UPDATE refunds SET status = 'failed' WHERE id = ? AND vendor_id = ? AND status = 'pending'",
                [(int) $refund['id'], $vendorId]);
            $refund['status'] = 'failed';
            return $refund;
        }

        Database::beginTransaction();
        try {
            $current = Database::fetchValue("SELECT status FROM refunds WHERE id = ? AND vendor_id = ? FOR UPDATE", [(int) $refund['id'], $vendorId]);
            if ($current !== 'pending') {
                Database::commit();
                $refund['status'] = $current;
                return $refund;
            }
            $order = VendorOrders::require($vendorId, $cooperative['id'], (int) $payment['order_id'], true);
            Database::execute("UPDATE refunds SET status = 'completed', gateway_ref = ? WHERE id = ? AND vendor_id = ?",
                [mb_substr((string) $result['refund_ref'], 0, 100, 'UTF-8'), (int) $refund['id'], $vendorId]);
            $lines = array_map('intval', array_column(Database::fetchAll(
                "SELECT order_item_id, qty FROM refund_items WHERE vendor_id = ? AND refund_id = ? ORDER BY order_item_id",
                [$vendorId, (int) $refund['id']]
            ), 'qty', 'order_item_id'));
            Refunds::apply($order, $refund, $lines, $userId, $cooperative['default_language']);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        $refund['status'] = 'completed';
        return $refund;
    }
}
