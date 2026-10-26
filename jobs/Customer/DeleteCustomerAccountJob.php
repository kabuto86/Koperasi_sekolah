<?php
// Padam akaun ibu bapa (PDPA — PELAN 15): data peribadi dinyahnamakan, rekod jualan vendor kekal.
// Pesanan yang belum selesai menghalang pemadaman.
class DeleteCustomerAccountJob
{
    const OPEN_STATUSES = ['pending_payment', 'paid', 'unpaid_cash', 'preparing', 'ready', 'partially_collected'];

    private $customerId;
    private $password;

    public function __construct($customerId, $password)
    {
        $this->customerId = (int) $customerId;
        $this->password = (string) $password;
    }

    public function run()
    {
        $hash = Database::fetchValue("SELECT password_hash FROM customers WHERE id = ? AND deleted_at IS NULL", [$this->customerId]);
        if (!Password::verify($this->password, $hash)) {
            throw new ValidationException(t('store.profile.current_wrong'), ['password' => t('store.profile.current_wrong')]);
        }
        $placeholders = implode(',', array_fill(0, count(self::OPEN_STATUSES), '?'));
        $openOrders = Database::fetchValue(
            "SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status IN ($placeholders)",
            array_merge([$this->customerId], self::OPEN_STATUSES)
        );
        if ((int) $openOrders > 0) {
            throw new ValidationException(t('store.profile.delete_active_orders'));
        }

        Database::beginTransaction();
        try {
            $this->anonymize();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        return ['deleted' => true];
    }

    private function anonymize()
    {
        $id = $this->customerId;
        // Pesanan kekal untuk laporan vendor, tanpa maklumat hubungan
        Database::execute("UPDATE orders SET customer_phone = NULL, customer_email = NULL WHERE customer_id = ?", [$id]);
        Database::execute("UPDATE children SET is_active = 0, full_name = '-' WHERE customer_id = ?", [$id]);
        Database::execute("DELETE FROM carts WHERE customer_id = ?", [$id]);
        Database::execute("DELETE FROM customer_tokens WHERE customer_id = ?", [$id]);
        Database::execute("DELETE FROM api_tokens WHERE customer_id = ?", [$id]);
        Database::execute("DELETE FROM push_devices WHERE customer_id = ?", [$id]);
        Database::execute("DELETE FROM vendor_customer_notes WHERE customer_id = ?", [$id]);
        Database::execute(
            "UPDATE customers SET full_name = ?, email = NULL, phone = NULL, password_hash = ?, is_active = 0, deleted_at = NOW()
              WHERE id = ?",
            [t('store.profile.deleted_name'), Password::hash(Crypto::randomToken(32)), $id]
        );
    }
}
