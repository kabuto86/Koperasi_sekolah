<?php
// Sahkan cooperative_id (dari input) milik vendor sesi — PELAN 8.4.
// vendor_id = sempadan keselamatan (dari sesi), cooperative_id = skop perniagaan (dari input).
//
//   $vendorId = VendorAuth::vendorId();                       // dari sesi, BUKAN input
//   $job = new ValidateCooperativeJob($vendorId, Request::int('cooperative_id'));
//   $cooperative = $job->run();
//   if ($cooperative === null) { Response::notFound(); }     // tiada ATAU milik vendor lain — jawapan sama
class ValidateCooperativeJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    // Pulangkan baris koperasi, atau null jika tiada / bukan milik vendor ini.
    // Status (active/suspended/closed) dipulangkan — pemanggil memutuskan sama ada dibenarkan.
    public function run()
    {
        if ($this->vendorId <= 0 || $this->cooperativeId <= 0) {
            return null;
        }

        return Database::fetchOne(
            "SELECT id, vendor_id, slug, school_name, school_code, school_type, state, district,
                    template_id, default_language, accept_cash, accept_online, status
               FROM cooperatives
              WHERE vendor_id = ?
                AND id = ?",
            [$this->vendorId, $this->cooperativeId]
        );
    }
}
