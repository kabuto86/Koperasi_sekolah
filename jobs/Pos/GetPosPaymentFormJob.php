<?php
// Halaman bayaran awam QR kaunter (store/pay.php) untuk gateway borang POST (iPay88). Tiada sesi: akses dengan
// rujukan bil + token rawak dari QR (dibanding dengan hash dalam raw_payload, hash_equals). Hanya bil `pending`
// jualan POS yang masih `pending_payment` dan belum luput. Pulangkan [action, fields, school_name, order_no, amount] | null.
class GetPosPaymentFormJob
{
    private $gatewayRef;
    private $token;

    public function __construct($gatewayRef, $token)
    {
        $this->gatewayRef = (string) $gatewayRef;
        $this->token = (string) $token;
    }

    public function run()
    {
        if ($this->gatewayRef === '' || !preg_match('/^[a-f0-9]{32}$/', $this->token)) {
            return null;
        }
        $rows = Database::fetchAll(
            "SELECT p.raw_payload, p.amount, o.order_no, c.school_name
               FROM payments p
               JOIN orders o ON o.id = p.order_id AND o.vendor_id = p.vendor_id
               JOIN cooperatives c ON c.id = o.cooperative_id AND c.vendor_id = o.vendor_id
              WHERE p.gateway_ref = ? AND p.method = 'online' AND p.status = 'pending'
                AND o.channel = 'pos' AND o.status = 'pending_payment' AND o.expires_at > NOW()",
            [$this->gatewayRef]
        );
        $hash = Crypto::hashToken($this->token);
        foreach ($rows as $row) {
            $raw = json_decode((string) $row['raw_payload'], true);
            if (!is_array($raw) || !is_string($raw['pay_token_hash'] ?? null) || !hash_equals($raw['pay_token_hash'], $hash)) {
                continue;
            }
            $form = $raw['form'] ?? null;
            if (!is_array($form) || !is_string($form['action'] ?? null) || !is_array($form['fields'] ?? null)) {
                return null;
            }
            return [
                'action'      => $form['action'],
                'fields'      => $form['fields'],
                'school_name' => $row['school_name'],
                'order_no'    => $row['order_no'],
                'amount'      => (int) $row['amount'],
            ];
        }
        return null;
    }
}
