<?php
// Mulakan bayaran online pesanan ibu bapa (selepas checkout, atau "Bayar" semula — store/orders/pay).
// Cipta bil di gateway koperasi → baris payments `pending` (gateway_ref UNIQUE) → { order_id, payment }.
// Panggilan ke gateway berlaku DI LUAR transaksi DB (pesanan sudah disimpan). Status `paid` hanya diputuskan oleh
// PaymentSettlement selepas panggilan balik / semakan semula — redirect ibu bapa tidak pernah dipercayai.
class StartOnlinePaymentJob
{
    // Bil `pending` yang sama digunakan semula dalam tempoh ini (elak bil berganda bila ibu bapa tekan "Bayar" lagi)
    const REUSE_MINUTES = 30;

    private $customerId;
    private $cooperative;
    private $orderId;

    public function __construct($customerId, array $cooperative, $orderId)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->orderId = (int) $orderId;
    }

    public function run()
    {
        $order = CustomerOrders::require($this->customerId, $this->cooperative, $this->orderId);
        if ($order['status'] !== 'pending_payment') {
            throw new ValidationException(t('store.order.not_payable'));
        }
        if ($order['expires_at'] !== null && strtotime($order['expires_at']) <= time()) {
            throw new ValidationException(t('store.order.payment_expired'));
        }
        $gateway = (int) $this->cooperative['accept_online'] === 1
            ? CooperativeGateways::active($this->cooperative['vendor_id'], $this->cooperative['id']) : null;
        if ($gateway === null) {
            throw new ValidationException(t('store.checkout.method_unavailable'));
        }

        $payment = $this->reusable($order, $gateway);
        if ($payment === null) {
            $payment = $this->createBill($order, $gateway);
        }
        $raw = json_decode((string) $payment['raw_payload'], true);
        return [
            'order_id' => (int) $order['id'],
            'payment'  => [
                'reference'    => $raw['reference'] ?? $payment['gateway_ref'],
                'gateway_name' => $gateway['name'],
                'amount'       => (int) $payment['amount'],
                'mode'         => $gateway['mode'],
                'redirect_url' => $raw['redirect_url'] ?? null,
            ],
        ];
    }

    // Bil pending terkini gateway yang sama, amaun sama, belum lama
    private function reusable(array $order, array $gateway)
    {
        return Database::fetchOne(
            "SELECT * FROM payments WHERE vendor_id = ? AND order_id = ? AND method = 'online' AND status = 'pending'
                AND gateway_code = ? AND amount = ? AND created_at > NOW() - INTERVAL " . self::REUSE_MINUTES . " MINUTE
              ORDER BY id DESC LIMIT 1",
            [(int) $order['vendor_id'], (int) $order['id'], $gateway['gateway_code'], (int) $order['total']]
        );
    }

    private function createBill(array $order, array $gateway)
    {
        $vendorId = (int) $order['vendor_id'];
        $count = (int) Database::fetchValue("SELECT COUNT(*) FROM payments WHERE vendor_id = ? AND order_id = ?", [$vendorId, (int) $order['id']]);
        $reference = $order['order_no'] . '-' . ($count + 1);
        $bill = [
            'reference'    => $reference,
            'amount'       => (int) $order['total'],
            'description'  => t('payment.bill_description', ['school' => $this->cooperative['school_name'], 'no' => $order['order_no']]),
            'name'         => (string) $order['customer_name'],
            'email'        => (string) $order['customer_email'],
            'phone'        => (string) $order['customer_phone'],
            'return_url'   => View::absoluteUrl('store/payment-return.php') . '?' . http_build_query([
                'school' => $this->cooperative['slug'], 'order' => (int) $order['id'],
            ]),
            'callback_url' => CooperativeGateways::callbackUrl($gateway['gateway_code'], $this->cooperative['id']),
        ];
        try {
            $created = CooperativeGateways::driver($gateway)->createBill($bill);
        } catch (GatewayException $error) {
            error_log('[KoperasiKu] cipta bil ' . $reference . ': ' . $error->getMessage());
            throw new ValidationException(t('payment.gateway_error'));
        }
        $raw = ['reference' => $reference, 'redirect_url' => $created['redirect_url'], 'form' => $created['form']];
        if ($created['form'] !== null) {
            // Gateway borang POST (iPay88) → halaman store yang menghantar borang secara automatik
            $raw['redirect_url'] = View::absoluteUrl('store/payment-redirect.php') . '?ref=' . rawurlencode($created['gateway_ref']);
        }
        $id = Database::insert(
            "INSERT INTO payments (vendor_id, cooperative_id, order_id, method, gateway_code, gateway_ref, amount, status, raw_payload)
             VALUES (?, ?, ?, 'online', ?, ?, ?, 'pending', ?)",
            [$vendorId, (int) $order['cooperative_id'], (int) $order['id'], $gateway['gateway_code'], $created['gateway_ref'],
                (int) $order['total'], json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
        return Database::fetchOne("SELECT * FROM payments WHERE id = ?", [$id]);
    }
}
