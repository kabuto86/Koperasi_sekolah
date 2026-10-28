<?php
// Borang POST gateway (iPay88) untuk store/payment-redirect.php. Hanya bil `pending` milik ibu bapa sesi
// (customer_id dari sesi) dan pesanan yang masih pending_payment. Pulangkan ['action', 'fields'] | null.
class GetPaymentFormJob
{
    private $customerId;
    private $gatewayRef;

    public function __construct($customerId, $gatewayRef)
    {
        $this->customerId = (int) $customerId;
        $this->gatewayRef = (string) $gatewayRef;
    }

    public function run()
    {
        $row = Database::fetchOne(
            "SELECT p.raw_payload FROM payments p
               JOIN orders o ON o.id = p.order_id AND o.vendor_id = p.vendor_id
              WHERE p.gateway_ref = ? AND p.method = 'online' AND p.status = 'pending'
                AND o.customer_id = ? AND o.channel = 'online' AND o.status = 'pending_payment'",
            [$this->gatewayRef, $this->customerId]
        );
        $raw = $row === null ? null : json_decode((string) $row['raw_payload'], true);
        $form = is_array($raw) ? ($raw['form'] ?? null) : null;
        return is_array($form) && is_array($form['fields'] ?? null) ? $form : null;
    }
}
