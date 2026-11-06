<?php
// Borang POST gateway owner (iPay88) untuk vendor/invoice-pay.php. Hanya bil platform `pending` milik vendor sesi
// (vendor_id dari sesi) untuk invois yang masih `issued`. Pulangkan [action, fields, invoice_no, amount] | null.
class GetInvoicePaymentFormJob
{
    private $vendorId;
    private $gatewayRef;

    public function __construct($vendorId, $gatewayRef)
    {
        $this->vendorId = (int) $vendorId;
        $this->gatewayRef = (string) $gatewayRef;
    }

    public function run()
    {
        $row = Database::fetchOne(
            "SELECT pp.raw_payload, pp.amount, i.invoice_no
               FROM platform_payments pp
               JOIN invoices i ON i.id = pp.invoice_id AND i.vendor_id = pp.vendor_id
              WHERE pp.vendor_id = ? AND pp.gateway_ref = ? AND pp.method = 'online' AND pp.status = 'pending' AND i.status = 'issued'",
            [$this->vendorId, $this->gatewayRef]
        );
        $raw = $row === null ? null : json_decode((string) $row['raw_payload'], true);
        $form = is_array($raw) ? ($raw['form'] ?? null) : null;
        if (!is_array($form) || !is_string($form['action'] ?? null) || !is_array($form['fields'] ?? null)) {
            return null;
        }
        return ['action' => $form['action'], 'fields' => $form['fields'], 'invoice_no' => $row['invoice_no'], 'amount' => (int) $row['amount']];
    }
}
