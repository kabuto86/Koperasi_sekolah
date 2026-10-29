<?php
// Selepas vendor kembali dari gateway (vendor/invoices.php?paid_return=) — pelayan bertanya SENDIRI ke gateway owner
// tentang bil pending terkini invois itu (PlatformPaymentSettlement). Parameter redirect tidak dibaca.
class CheckInvoicePaymentJob
{
    private $vendorId;
    private $invoiceId;

    public function __construct($vendorId, $invoiceId)
    {
        $this->vendorId = (int) $vendorId;
        $this->invoiceId = (int) $invoiceId;
    }

    public function run()
    {
        $invoice = Invoices::find($this->invoiceId, $this->vendorId);
        if ($invoice === null || $invoice['status'] === 'draft') {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $result = $invoice['status'] === 'paid' ? 'already_paid' : 'none';
        $payment = Database::fetchOne(
            "SELECT * FROM platform_payments WHERE vendor_id = ? AND invoice_id = ? AND method = 'online' AND status = 'pending'
              ORDER BY id DESC LIMIT 1",
            [$this->vendorId, (int) $invoice['id']]
        );
        $driver = $payment === null ? null : PlatformGateway::driver($payment['gateway_code']);
        if ($driver !== null) {
            try {
                $result = PlatformPaymentSettlement::recheck($payment, $driver);
            } catch (GatewayException $error) {
                error_log('[KoperasiKu] semak bil invois ' . $payment['gateway_ref'] . ': ' . $error->getMessage());
                $result = 'recheck_failed';
            }
        }
        return ['result' => $result, 'invoice' => Invoices::summary(Invoices::find($this->invoiceId, $this->vendorId))];
    }
}
