<?php
// Owner menanda invois dibayar secara manual (pindahan bank / tunai / cek / lain-lain) — PELAN 14.
// platform_payments: method, rujukan (gateway_ref UNIQUE → rujukan yang sama tidak boleh dipakai dua kali), nota, tarikh bayar.
// Hanya invois `issued`. Tunggakan tamat → vendor pulih.
class MarkInvoicePaidJob
{
    const METHODS = ['bank_transfer', 'cash', 'cheque', 'other'];

    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $v = new Validator($this->input);
        $v->required('method')->inList('method', self::METHODS)
          ->required('reference')->maxLength('reference', 100)
          ->required('paid_at')->date('paid_at')
          ->maxLength('note', 255);
        if (!$v->fails() && $this->input['paid_at'] > BillingPeriod::today()) {
            $v->addError('paid_at', t('invoice.paid_future'));
        }
        ValidationException::throwIfFails($v);
        $reference = trim((string) $this->input['reference']);
        $invoiceId = (int) ($this->input['id'] ?? 0);

        Database::beginTransaction();
        try {
            $peek = Invoices::find($invoiceId);
            if ($peek === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            Subscriptions::lockVendor($peek['vendor_id']);
            $row = Invoices::find($invoiceId, null, true);
            if ($row['status'] !== 'issued') {
                throw new ValidationException(t('invoice.cannot_mark_paid'));
            }
            if (Database::fetchValue("SELECT 1 FROM platform_payments WHERE gateway_ref = ?", [$reference])) {
                throw new ValidationException(t('error.check_form'), ['reference' => t('invoice.reference_used')]);
            }
            $paidAt = $this->input['paid_at'] . ' 12:00:00';
            Database::insert(
                "INSERT INTO platform_payments (vendor_id, invoice_id, method, gateway_code, gateway_ref, amount, status, note, paid_at)
                 VALUES (?, ?, ?, NULL, ?, ?, 'paid', ?, ?)",
                [(int) $row['vendor_id'], (int) $row['id'], $this->input['method'], $reference, (int) $row['total'],
                    trim((string) ($this->input['note'] ?? '')) ?: null, $paidAt]
            );
            Database::execute("UPDATE invoices SET status = 'paid', paid_at = ? WHERE id = ?", [$paidAt, (int) $row['id']]);
            (new WriteAuditLogJob('owner', $this->actorId, (int) $row['vendor_id'], 'invoice.mark_paid', 'invoice', (int) $row['id'],
                $row['invoice_no'] . ' · ' . $reference))->run();
            VendorBillingStatus::restore($row['vendor_id'], 'owner', $this->actorId);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return Invoices::summary(Invoices::find($invoiceId));
    }
}
