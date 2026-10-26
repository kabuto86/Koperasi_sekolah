<?php
// Lulus / aktifkan semula (active) atau gantung (suspended) vendor. Sebab wajib untuk gantung dan disimpan
// sebagai nota dalaman. Langganan terkini mengikut status yang sama. Vendor digantung terus terhad kepada
// halaman bil (VendorAuth menyemak status setiap permintaan — B2).
class SetVendorStatusJob
{
    // status baharu => status semasa yang dibenarkan
    const TRANSITIONS = [
        'active'    => ['trial', 'past_due', 'suspended'],
        'suspended' => ['trial', 'active', 'past_due'],
    ];

    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $vendorId = (int) ($this->input['id'] ?? 0);
        $vendor = Database::fetchOne("SELECT id, code, status FROM vendors WHERE id = ?", [$vendorId]);
        if ($vendor === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $status = (string) ($this->input['status'] ?? '');
        $reason = trim((string) ($this->input['reason'] ?? ''));

        $v = new Validator($this->input);
        $v->required('status')->inList('status', array_keys(self::TRANSITIONS))->maxLength('reason', 1000);
        if ($status === 'suspended') {
            $v->required('reason');
        }
        ValidationException::throwIfFails($v);
        if (!in_array($vendor['status'], self::TRANSITIONS[$status], true)) {
            throw new ValidationException(t('error.invalid'));
        }

        Database::beginTransaction();
        Database::execute("UPDATE vendors SET status = ? WHERE id = ?", [$status, $vendorId]);
        Database::execute(
            "UPDATE subscriptions SET status = ? WHERE id = (SELECT id FROM (SELECT MAX(id) AS id FROM subscriptions WHERE vendor_id = ?) latest)
                AND status <> 'cancelled'",
            [$status, $vendorId]
        );
        if ($reason !== '') {
            Database::insert("INSERT INTO vendor_notes (vendor_id, body, created_by) VALUES (?, ?, ?)", [$vendorId, $reason, $this->actorId]);
        }
        Database::commit();

        $action = $status === 'active' ? 'vendor.activate' : 'vendor.suspend';
        (new WriteAuditLogJob('owner', $this->actorId, $vendorId, $action, 'vendor', $vendorId, $reason))->run();
        return ListVendorsJob::find($vendorId);
    }
}
