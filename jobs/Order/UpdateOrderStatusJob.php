<?php
// Tukar status pesanan dari panel (PELAN 18.9 vendor/orders/set-status, bulk-status). Peralihan: VendorOrders::STATUS_FLOW.
// Batal hanya pending_payment / unpaid_cash dengan sebab (lepas tahanan stok, slot & promosi; bil online pending → failed).
// → ready: e-mel "Sedia diambil" kepada ibu bapa (email_queue). Pesanan dikunci (FOR UPDATE) dalam transaksi.
class UpdateOrderStatusJob
{
    private $vendorId;
    private $cooperative;
    private $userId;
    private $orderId;
    private $status;
    private $note;
    private $allowCancel;

    public function __construct($vendorId, array $cooperative, $userId, $orderId, $status, $note, $allowCancel = true)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->orderId = (int) $orderId;
        $this->status = (string) $status;
        $this->note = trim((string) $note);
        $this->allowCancel = $allowCancel;
    }

    public function run()
    {
        if ($this->status === 'cancelled' && $this->note === '') {
            throw new ValidationException(t('error.required'), ['note' => t('error.required')]);
        }
        Database::beginTransaction();
        try {
            $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], $this->orderId, true);
            $allowed = VendorOrders::STATUS_FLOW[$order['status']] ?? [];
            if ($order['channel'] !== 'online' || !in_array($this->status, $allowed, true)
                || ($this->status === 'cancelled' && !$this->allowCancel)) {
                throw new ValidationException(t('order.status_not_allowed'));
            }
            $this->apply($order);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return (new GetVendorOrderJob($this->vendorId, $this->cooperative['id'], $this->orderId))->run();
    }

    private function apply(array $order)
    {
        $args = [$this->status, (int) $order['id'], $this->vendorId];
        if ($this->status === 'cancelled') {
            CustomerOrders::releaseHolds($order);
            Database::execute("UPDATE orders SET status = ?, cancelled_at = NOW() WHERE id = ? AND vendor_id = ?", $args);
            Database::execute(
                "UPDATE payments SET status = 'failed' WHERE vendor_id = ? AND order_id = ? AND status = 'pending'",
                [$this->vendorId, (int) $order['id']]
            );
        } else {
            Database::execute("UPDATE orders SET status = ? WHERE id = ? AND vendor_id = ?", $args);
        }
        CustomerOrders::logStatus($order, $this->status, 'vendor_user', $this->userId, $this->note === '' ? null : $this->note);
        // E-mel hanya kali pertama sedia (ready → preparing → ready tidak menghantar semula)
        if ($this->status === 'ready' && !$this->wasReadyBefore($order)) {
            OrderEmails::queueReady($this->cooperative, $order);
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperative['id'], $this->userId, 'order_status', 'order', (int) $order['id'],
            $order['order_no'] . ': ' . $order['status'] . ' → ' . $this->status))->run();
    }

    private function wasReadyBefore(array $order)
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM order_status_logs WHERE vendor_id = ? AND order_id = ? AND to_status = 'ready'",
            [$this->vendorId, (int) $order['id']]
        ) > 1;
    }
}
