<?php
// Owner menerbitkan draf invois (draft → issued): tarikh akhir 7 hari, e-mel kepada vendor. Baris tidak lagi dikira semula.
class IssueInvoiceJob
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
            $row = Invoices::find($this->invoiceId, null, true);
            if ($row === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($row['status'] !== 'draft' || !Invoices::issue($row['id'])) {
                throw new ValidationException(t('invoice.cannot_issue'));
            }
            (new WriteAuditLogJob('owner', $this->actorId, (int) $row['vendor_id'], 'invoice.issue', 'invoice', (int) $row['id'], $row['invoice_no']))->run();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return Invoices::summary(Invoices::find($this->invoiceId));
    }
}
