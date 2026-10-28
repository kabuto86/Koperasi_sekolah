<?php
// Buka kunci kod 4 digit selepas 5 cubaan salah (PELAN 7 "perlu pengurus") — pentadbir / pengurus koperasi sahaja.
// pickup_attempts → 0, direkod dalam log status & log aktiviti.
class UnlockPickupCodeJob
{
    private $vendorId;
    private $cooperative;
    private $userId;
    private $orderId;

    public function __construct($vendorId, array $cooperative, $userId, $orderId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->orderId = (int) $orderId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], $this->orderId, true);
            if ($order['pickup_code_hash'] === null || !VendorOrders::isLocked($order)) {
                throw new ValidationException(t('order.code_not_locked'));
            }
            Database::execute("UPDATE orders SET pickup_attempts = 0 WHERE id = ? AND vendor_id = ?", [(int) $order['id'], $this->vendorId]);
            CustomerOrders::logStatus($order, $order['status'], 'vendor_user', $this->userId,
                Lang::getFor($this->cooperative['default_language'], 'order.code_unlocked_log'));
            (new WriteActivityLogJob($this->vendorId, $this->cooperative['id'], $this->userId, 'pickup_unlock', 'order', (int) $order['id'],
                $order['order_no']))->run();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return (new GetVendorOrderJob($this->vendorId, $this->cooperative['id'], $this->orderId))->run();
    }
}
