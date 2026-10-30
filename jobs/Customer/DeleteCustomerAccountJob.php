<?php
// Padam akaun ibu bapa (PDPA — PELAN 15): data peribadi dinyahnamakan, rekod jualan vendor kekal.
// Pesanan yang belum selesai menghalang pemadaman.
// Sejak B14 nyahnama juga snapshot dalam pesanan (nama pelanggan, nama anak, pengambil, nota status yang memuat nama itu),
// baris gilir e-mel / WhatsApp / SMS, dan cubaan log masuk (e-mel / telefon). Jumlah, produk, darjah & kelas kekal untuk laporan.
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
            $this->anonymizeOrders();
            $this->anonymize();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        (new WriteAuditLogJob('customer', $this->customerId, null, 'customer.delete_account', 'customer', $this->customerId, ''))->run();
        return ['deleted' => true];
    }

    // Snapshot pesanan + mesej berkaitan. Dipanggil SEBELUM nama/e-mel pelanggan dibuang (nilai lama diperlukan untuk dicari)
    private function anonymizeOrders()
    {
        $id = $this->customerId;
        $customer = Database::fetchOne("SELECT full_name, email, phone FROM customers WHERE id = ? FOR UPDATE", [$id]);
        $names = array_merge(
            [$customer['full_name']],
            array_column(Database::fetchAll("SELECT full_name FROM children WHERE customer_id = ?", [$id]), 'full_name'),
            array_column(Database::fetchAll(
                "SELECT DISTINCT oi.child_name FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.customer_id = ?", [$id]
            ), 'child_name'),
            array_column(Database::fetchAll("SELECT DISTINCT pickup_by_name FROM orders WHERE customer_id = ?", [$id]), 'pickup_by_name'),
            array_column(Database::fetchAll(
                "SELECT DISTINCT p.picked_by_name FROM order_pickups p JOIN orders o ON o.id = p.order_id WHERE o.customer_id = ?", [$id]
            ), 'picked_by_name')
        );
        $names = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $names)), function ($name) {
            return mb_strlen($name, 'UTF-8') >= 2 && $name !== '-';
        })));

        // Nota status (cth. serah barang "Diambil oleh Aiman") — gantikan nama sahaja, nota lain (refund, bayaran) kekal
        foreach ($names as $name) {
            Database::execute(
                "UPDATE order_status_logs l JOIN orders o ON o.id = l.order_id AND o.vendor_id = l.vendor_id
                    SET l.note = REPLACE(l.note, ?, '-')
                  WHERE o.customer_id = ? AND l.note LIKE ?",
                [$name, $id, Pagination::like($name)]
            );
        }
        Database::execute(
            "UPDATE order_items oi JOIN orders o ON o.id = oi.order_id SET oi.child_name = '-' WHERE o.customer_id = ? AND oi.child_name IS NOT NULL",
            [$id]
        );
        Database::execute("UPDATE order_pickups p JOIN orders o ON o.id = p.order_id SET p.picked_by_name = '-' WHERE o.customer_id = ?", [$id]);

        // Mesej WhatsApp/SMS pesanan ini: yang belum dihantar dibuang; log yang sudah dihantar tanpa nombor & teks
        Database::execute(
            "DELETE q FROM notification_queue q JOIN orders o ON o.id = q.order_id AND o.vendor_id = q.vendor_id
              WHERE o.customer_id = ? AND q.status = 'pending'",
            [$id]
        );
        Database::execute(
            "UPDATE notification_queue q JOIN orders o ON o.id = q.order_id AND o.vendor_id = q.vendor_id
                SET q.recipient = '-', q.message = '-'
              WHERE o.customer_id = ?",
            [$id]
        );
        $email = (string) $customer['email'];
        if ($email !== '') {
            Database::execute("DELETE FROM email_queue WHERE to_email = ? AND status = 'pending'", [$email]);
            Database::execute("UPDATE email_queue SET to_email = '-', subject = '-', body_html = '' WHERE to_email = ?", [$email]);
        }
        $identifiers = array_values(array_filter([$email, (string) $customer['phone']]));
        if (count($identifiers) > 0) {
            Database::execute(
                "DELETE FROM login_attempts WHERE login_type = 'customer' AND identifier IN (" . implode(',', array_fill(0, count($identifiers), '?')) . ")",
                $identifiers
            );
        }
        Database::execute(
            "UPDATE orders SET customer_name = ?, customer_phone = NULL, customer_email = NULL, pickup_by_name = NULL WHERE customer_id = ?",
            [t('store.profile.deleted_name'), $id]
        );
    }

    private function anonymize()
    {
        $id = $this->customerId;
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
