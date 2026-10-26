<?php
// Aktif / nyahaktif kakitangan vendor sesi. Akaun sendiri tidak boleh dinyahaktifkan; aktifkan semula ikut had pelan.
class SetVendorStaffActiveJob
{
    private $vendorId;
    private $actorId;
    private $input;

    public function __construct($vendorId, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $isActive = ($this->input['is_active'] ?? false) === true;
        $user = ListVendorStaffJob::find($this->vendorId, $id, $this->actorId);
        if ($user === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($user['is_self']) {
            throw new ValidationException(t('staff.cannot_disable_self'));
        }
        if ($isActive && !$user['is_active']) {
            $limit = ListVendorStaffJob::limit($this->vendorId);
            if ($limit['max'] !== null && $limit['used'] >= $limit['max']) {
                throw new ValidationException(t('staff_v.limit_reached', ['max' => $limit['max']]));
            }
        }
        Database::execute("UPDATE vendor_users SET is_active = ? WHERE id = ? AND vendor_id = ?", [$isActive ? 1 : 0, $id, $this->vendorId]);
        $detail = $user['full_name'] . ' · ' . t($isActive ? 'common.active' : 'common.inactive');
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'staff_status', 'vendor_user', $id, $detail))->run();
        return ListVendorStaffJob::find($this->vendorId, $id, $this->actorId);
    }
}
