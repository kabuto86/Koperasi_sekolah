<?php
// Owner membatalkan invois draf / belum dibayar (→ void). Tunggakan tamat → vendor pulih (VendorBillingStatus).
// Draf pembaharuan yang dibatalkan tidak dijana semula untuk tempoh itu. Langganan / modul tidak berubah.
class VoidInvoiceJob
{
    private $actorId;
    private $invoiceId;

    public function __construct($actorId, $invoiceId)
    {
        $this->actorId = (int) $actorId;
        $this->invoiceId = (int) $invoiceId;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $peek = Invoices::find($this->invoiceId);
            if ($peek === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            Subscriptions::lockVendor($peek['vendor_id']);
            $row = Invoices::find($this->invoiceId, null, true);
            if (!in_array($row['status'], ['draft', 'issued'], true)) {
                throw new ValidationException(t('invoice.cannot_void'));
            }
            Database::execute("UPDATE invoices SET status = 'void' WHERE id = ?", [(int) $row['id']]);
            (new WriteAuditLogJob('owner', $this->actorId, (int) $row['vendor_id'], 'invoice.void', 'invoice', (int) $row['id'], $row['invoice_no']))->run();
            VendorBillingStatus::restore($row['vendor_id'], 'owner', $this->actorId);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return Invoices::summary(Invoices::find($this->invoiceId));
    }
}
