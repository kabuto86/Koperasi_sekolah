<?php
// Butiran invois (PELAN 18.7 InvoiceDetail). Owner: + vendor { id, code, company_name, registration_no, address, email }.
// Vendor: invois sendiri sahaja (bukan draf) + vendor { code, company_name }; milik vendor lain → 404 (jawapan sama seperti tiada).
class GetInvoiceJob
{
    private $vendorId;
    private $invoiceId;

    // $vendorId null = owner
    public function __construct($vendorId, $invoiceId)
    {
        $this->vendorId = $vendorId === null ? null : (int) $vendorId;
        $this->invoiceId = (int) $invoiceId;
    }

    public function run()
    {
        $row = Invoices::find($this->invoiceId, $this->vendorId);
        if ($row === null || ($this->vendorId !== null && $row['status'] === 'draft')) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = Invoices::detail($row);
        if ($this->vendorId === null) {
            $data['vendor'] = [
                'id'              => (int) $row['vendor_id'],
                'code'            => $row['vendor_code'],
                'company_name'    => $row['vendor_name'],
                'registration_no' => $row['registration_no'],
                'address'         => $row['address'],
                'email'           => $row['email'],
            ];
        } else {
            unset($data['vendor_id']);
            $data['vendor'] = ['code' => $row['vendor_code'], 'company_name' => $row['vendor_name']];
        }
        return $data;
    }
}
