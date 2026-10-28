<?php
// Bil QR untuk jualan POS online (PELAN 18.11, 13). Sama seperti StartOnlinePaymentJob: bil di gateway koperasi
// (CooperativeGateways::driver → createBill) DI LUAR transaksi DB → baris payments `pending`. Pembeli mengimbas QR
// (pautan bayaran gateway) dengan telefon; status `paid` HANYA oleh PaymentSettlement (webhook / semakan semula).
// Pulangkan { reference, gateway_name, qr_payload, expires_in (saat) }.
class PosOnlinePaymentJob
{
    private $cooperative;
    private $order;
    private $gateway;

    public function __construct(array $cooperative, array $order, array $gateway)
    {
        $this->cooperative = $cooperative;
        $this->order = $order;
        $this->gateway = $gateway;
    }

    public function run()
    {
        $coop = $this->cooperative;
        $order = $this->order;
        $reference = $order['order_no'] . '-1';
        $settings = CooperativeSettings::all($coop['vendor_id'], $coop['id']);
        $emails = json_decode((string) ($settings['alert_emails'] ?? '[]'), true);
        $bill = [
            'reference'    => $reference,
            'amount'       => (int) $order['total'],
            'description'  => t('payment.bill_description', ['school' => $coop['school_name'], 'no' => $order['order_no']]),
            'name'         => (string) ($order['customer_name'] ?? '') !== '' ? $order['customer_name'] : $coop['school_name'],
            'email'        => is_array($emails) && $emails ? (string) $emails[0] : '',
            'phone'        => (string) $coop['phone'],
            'return_url'   => View::absoluteStoreUrl($coop['slug']),
            'callback_url' => CooperativeGateways::callbackUrl($this->gateway['gateway_code'], $coop['id']),
        ];
        try {
            $created = CooperativeGateways::driver($this->gateway)->createBill($bill);
        } catch (GatewayException $error) {
            error_log('[KoperasiKu] cipta bil POS ' . $reference . ': ' . $error->getMessage());
            throw new ValidationException(t('payment.gateway_error'));
        }
        if ($created['form'] !== null || (string) $created['redirect_url'] === '') {
            // Gateway borang POST (iPay88) perlu sesi ibu bapa (store/payment-redirect.php) — tiada pautan untuk QR kaunter
            throw new ValidationException(t('pos.online_qr_unsupported'));
        }
        $link = $created['redirect_url'];
        $raw = ['reference' => $reference, 'redirect_url' => $link, 'form' => null];
        Database::insert(
            "INSERT INTO payments (vendor_id, cooperative_id, order_id, method, gateway_code, gateway_ref, amount, status, raw_payload)
             VALUES (?, ?, ?, 'online', ?, ?, ?, 'pending', ?)",
            [(int) $order['vendor_id'], (int) $order['cooperative_id'], (int) $order['id'], $this->gateway['gateway_code'], $created['gateway_ref'],
                (int) $order['total'], json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
        return [
            'reference'    => $reference,
            'gateway_name' => $this->gateway['name'],
            'qr_payload'   => $link,
            'expires_in'   => max(0, strtotime($order['expires_at']) - time()),
        ];
    }
}
