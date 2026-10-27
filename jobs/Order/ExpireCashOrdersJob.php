<?php
// Cron (PELAN 6, 16 perkara 4): pesanan tunai yang tidak diambil sehingga expires_at → expired, tahanan stok,
// slot & promosi dilepaskan. Setiap pesanan dalam transaksi sendiri (dikunci & disemak semula).
class ExpireCashOrdersJob
{
    private $limit;

    public function __construct($limit = 200)
    {
        $this->limit = max(1, (int) $limit);
    }

    // Pulangkan bilangan pesanan yang diluputkan
    public function run()
    {
        $ids = array_column(Database::fetchAll(
            "SELECT id FROM orders WHERE status = 'unpaid_cash' AND expires_at < NOW() ORDER BY expires_at LIMIT " . $this->limit
        ), 'id');
        $count = 0;
        foreach ($ids as $id) {
            $count += $this->expire((int) $id) ? 1 : 0;
        }
        return $count;
    }

    private function expire($orderId)
    {
        Database::beginTransaction();
        try {
            $order = Database::fetchOne("SELECT * FROM orders WHERE id = ? FOR UPDATE", [$orderId]);
            if ($order === null || $order['status'] !== 'unpaid_cash' || strtotime($order['expires_at']) >= time()) {
                Database::rollBack();
                return false;
            }
            CustomerOrders::releaseHolds($order);
            Database::execute("UPDATE orders SET status = 'expired' WHERE id = ? AND vendor_id = ?", [$orderId, (int) $order['vendor_id']]);
            CustomerOrders::logStatus($order, 'expired', 'system', null);
            Database::commit();
            return true;
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
    }
}
