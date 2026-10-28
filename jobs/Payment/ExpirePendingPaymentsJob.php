<?php
// Cron (B8): pesanan online `pending_payment` yang lepas expires_at (config online_payment_expiry_minutes).
// Sebelum diluputkan, bil pending ditanya semula ke gateway — bayaran yang panggilan baliknya hilang tetap direkod `paid`.
// Gateway tidak dapat dihubungi → pesanan dicuba lagi larian seterusnya (sehingga GRACE_HOURS, kemudian diluputkan juga).
// Luput: tahanan stok, slot & promosi dilepas, bil pending → failed, status `expired` (dalam transaksi, pesanan dikunci).
class ExpirePendingPaymentsJob
{
    const GRACE_HOURS = 24;

    private $limit;

    public function __construct($limit = 100)
    {
        $this->limit = max(1, (int) $limit);
    }

    // Pulangkan ['expired' => n, 'paid' => n, 'retry' => n]
    public function run()
    {
        $orders = Database::fetchAll(
            "SELECT * FROM orders WHERE status = 'pending_payment' AND expires_at < NOW() ORDER BY expires_at LIMIT " . $this->limit
        );
        $counts = ['expired' => 0, 'paid' => 0, 'retry' => 0];
        foreach ($orders as $order) {
            $result = $this->settle($order);
            if ($result === 'paid') {
                $counts['paid']++;
            } elseif ($result === 'retry') {
                $counts['retry']++;
            } elseif ($this->expire((int) $order['id'])) {
                $counts['expired']++;
            }
        }
        return $counts;
    }

    // Tanya gateway untuk setiap bil pending: paid | retry | none
    private function settle(array $order)
    {
        $payments = Database::fetchAll(
            "SELECT * FROM payments WHERE vendor_id = ? AND order_id = ? AND method = 'online' AND status = 'pending' ORDER BY id DESC",
            [(int) $order['vendor_id'], (int) $order['id']]
        );
        $retry = false;
        foreach ($payments as $payment) {
            $gateway = CooperativeGateways::find($order['vendor_id'], $order['cooperative_id'], $payment['gateway_code']);
            if ($gateway === null) {
                continue;
            }
            try {
                if (PaymentSettlement::recheck($payment, CooperativeGateways::driver($gateway)) === 'paid') {
                    return 'paid';
                }
            } catch (GatewayException $error) {
                error_log('[KoperasiKu] cron semak ' . $payment['gateway_ref'] . ': ' . $error->getMessage());
                $retry = true;
            }
        }
        $tooOld = strtotime($order['expires_at']) < time() - self::GRACE_HOURS * 3600;
        return $retry && !$tooOld ? 'retry' : 'none';
    }

    private function expire($orderId)
    {
        Database::beginTransaction();
        try {
            $order = Database::fetchOne("SELECT * FROM orders WHERE id = ? FOR UPDATE", [$orderId]);
            if ($order === null || $order['status'] !== 'pending_payment' || strtotime($order['expires_at']) >= time()) {
                Database::rollBack();
                return false;
            }
            CustomerOrders::releaseHolds($order);
            Database::execute("UPDATE orders SET status = 'expired' WHERE id = ? AND vendor_id = ?", [$orderId, (int) $order['vendor_id']]);
            Database::execute(
                "UPDATE payments SET status = 'failed' WHERE vendor_id = ? AND order_id = ? AND status = 'pending'",
                [(int) $order['vendor_id'], $orderId]
            );
            CustomerOrders::logStatus($order, 'expired', 'system', null);
            Database::commit();
            return true;
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
    }
}
