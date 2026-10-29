<?php
// Bayar invois secara online melalui gateway owner (PELAN 14, 18.10 vendor/invoices/pay) → { paid: false, redirect_url }.
// Bil gateway dicipta DI LUAR transaksi DB; baris platform_payments `pending` (gateway_ref UNIQUE). Status `paid` HANYA
// selepas panggilan balik / semakan semula ke API gateway + amaun sama (PlatformPaymentSettlement) — redirect tidak dipercayai.
class PayInvoiceJob
{
    // Bil pending yang sama diguna semula dalam tempoh ini (vendor tekan "Bayar" berulang kali)
    const REUSE_MINUTES = 30;

    private $vendorId;
    private $actorId;
    private $invoiceId;

    public function __construct($vendorId, $actorId, $invoiceId)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->invoiceId = (int) $invoiceId;
    }

    public function run()
    {
        $invoice = Invoices::find($this->invoiceId, $this->vendorId);
        if ($invoice === null || $invoice['status'] === 'draft') {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($invoice['status'] !== 'issued') {
            throw new ValidationException(t('billing.cannot_pay'));
        }
        $gateway = PlatformGateway::settings();
        if ($gateway === null) {
            throw new ValidationException(t('billing.gateway_unavailable'));
        }
        $payment = $this->reusable($invoice, $gateway) ?? $this->createBill($invoice, $gateway);
        $raw = json_decode((string) $payment['raw_payload'], true);
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'invoice_pay', 'invoice', (int) $invoice['id'],
            $invoice['invoice_no'] . ' · ' . $gateway['name']))->run();
        return [
            'paid'         => false,
            'redirect_url' => $raw['redirect_url'] ?? null,
            'gateway_name' => $gateway['name'],
            'invoice'      => Invoices::summary($invoice),
        ];
    }

    private function reusable(array $invoice, array $gateway)
    {
        return Database::fetchOne(
            "SELECT * FROM platform_payments WHERE vendor_id = ? AND invoice_id = ? AND method = 'online' AND status = 'pending'
                AND gateway_code = ? AND amount = ? AND created_at > NOW() - INTERVAL " . self::REUSE_MINUTES . " MINUTE
              ORDER BY id DESC LIMIT 1",
            [$this->vendorId, (int) $invoice['id'], $gateway['code'], (int) $invoice['total']]
        );
    }

    private function createBill(array $invoice, array $gateway)
    {
        $count = (int) Database::fetchValue("SELECT COUNT(*) FROM platform_payments WHERE vendor_id = ? AND invoice_id = ?",
            [$this->vendorId, (int) $invoice['id']]);
        $reference = $invoice['invoice_no'] . '-' . ($count + 1);
        $vendor = Database::fetchOne("SELECT company_name, email, phone FROM vendors WHERE id = ?", [$this->vendorId]);
        try {
            $created = PlatformGateway::driver($gateway['code'])->createBill([
                'reference'    => $reference,
                'amount'       => (int) $invoice['total'],
                'description'  => t('billing.bill_description', ['no' => $invoice['invoice_no']]),
                'name'         => (string) $vendor['company_name'],
                'email'        => (string) $vendor['email'],
                'phone'        => (string) $vendor['phone'],
                'return_url'   => View::absoluteUrl('vendor/invoices.php') . '?paid_return=' . (int) $invoice['id'],
                'callback_url' => PlatformGateway::callbackUrl($gateway['code']),
            ]);
        } catch (GatewayException $error) {
            error_log('[KoperasiKu] bil invois ' . $reference . ': ' . $error->getMessage());
            throw new ValidationException(t('payment.gateway_error'));
        }
        if ($created['form'] !== null || empty($created['redirect_url'])) {
            // Gateway borang POST (iPay88) belum disokong untuk yuran platform
            throw new ValidationException(t('billing.gateway_unavailable'));
        }
        $raw = ['reference' => $reference, 'redirect_url' => $created['redirect_url']];
        $id = Database::insert(
            "INSERT INTO platform_payments (vendor_id, invoice_id, method, gateway_code, gateway_ref, amount, status, raw_payload)
             VALUES (?, ?, 'online', ?, ?, ?, 'pending', ?)",
            [$this->vendorId, (int) $invoice['id'], $gateway['code'], $created['gateway_ref'], (int) $invoice['total'],
                json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
        return Database::fetchOne("SELECT * FROM platform_payments WHERE id = ?", [$id]);
    }
}
