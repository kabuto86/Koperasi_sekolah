<?php
// Semak kod 4 digit ambil barang (PELAN 7 cara E). Transaksi SENDIRI supaya cubaan salah kekal walaupun serahan gagal:
// salah → pickup_attempts + 1; ke-5 → dikunci (perlu pengurus: UnlockPickupCodeJob). Kod dibanding dengan password_verify
// (orders.pickup_code_hash, Argon2id) — kod tidak pernah disimpan atau dipulangkan.
class VerifyPickupCodeJob
{
    private $vendorId;
    private $cooperativeId;
    private $orderId;
    private $code;

    public function __construct($vendorId, $cooperativeId, $orderId, $code)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->orderId = (int) $orderId;
        $this->code = trim((string) $code);
    }

    // true = sah atau tidak perlu kod; selain itu ValidationException (kod salah / dikunci)
    public function run()
    {
        Database::beginTransaction();
        try {
            $order = VendorOrders::require($this->vendorId, $this->cooperativeId, $this->orderId, true);
            $error = $this->check($order);
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        if ($error !== null) {
            throw new ValidationException($error, ['code' => $error]);
        }
        return true;
    }

    // Pulangkan mesej ralat atau null. Cubaan salah dikemas kini dalam transaksi run()
    private function check(array $order)
    {
        if ($order['pickup_code_hash'] === null) {
            return null;
        }
        if (VendorOrders::isLocked($order)) {
            return t('handover.locked');
        }
        if (preg_match('/^\d{4}$/', $this->code) && password_verify($this->code, $order['pickup_code_hash'])) {
            return null;
        }
        $attempts = (int) $order['pickup_attempts'] + 1;
        Database::execute("UPDATE orders SET pickup_attempts = ? WHERE id = ? AND vendor_id = ?", [$attempts, (int) $order['id'], $this->vendorId]);
        if ($attempts >= VendorOrders::MAX_CODE_ATTEMPTS) {
            return t('handover.locked');
        }
        return t('handover.code_wrong', ['left' => VendorOrders::MAX_CODE_ATTEMPTS - $attempts]);
    }
}
