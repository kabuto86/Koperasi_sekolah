<?php
// Simpan kebenaran seorang kakitangan (PELAN 4.1). Input: permissions = senarai kod yang dibenarkan (keadaan akhir),
// atau reset = true (buang semua pengecualian). Hanya BEZA dengan lalai peranan disimpan; had & kod tidak dikenali diabaikan.
// Tidak boleh: sunting vendor_admin / owner (sentiasa semua), sunting diri sendiri.
class SaveStaffPermissionsJob
{
    private $panel;
    private $actorId;
    private $vendorId;
    private $input;

    public function __construct($panel, $actorId, $vendorId, array $input)
    {
        $this->panel = $panel === 'owner' ? 'owner' : 'vendor';
        $this->actorId = (int) $actorId;
        $this->vendorId = (int) $vendorId;
        $this->input = $input;
    }

    public function run()
    {
        $userId = (int) ($this->input['id'] ?? 0);
        $user = GetStaffPermissionsJob::findUser($this->panel, $this->vendorId, $userId);
        if ($user === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if (!Permission::isEditableRole($user['role']) || $user['id'] === $this->actorId) {
            throw new ValidationException(t('permission.not_editable'));
        }

        $overrides = empty($this->input['reset']) ? $this->overrides($user['role']) : [];
        Database::beginTransaction();
        $this->deleteOverrides($user['id']);
        foreach ($overrides as $code => $allowed) {
            $this->insertOverride($user['id'], $code, $allowed);
        }
        Database::commit();

        $this->log($user, $overrides);
        return (new GetStaffPermissionsJob($this->panel, $this->actorId, $this->vendorId, $user['id']))->run();
    }

    // [ kod => true|false ] — hanya yang berbeza dengan lalai peranan
    private function overrides($role)
    {
        $wanted = array_map('strval', array_filter((array) ($this->input['permissions'] ?? []), 'is_scalar'));
        $defaults = Permission::defaults($role);
        $result = [];
        foreach (Permission::all($this->panel) as $code) {
            if (Permission::isLocked($this->panel, $code)) {
                continue;
            }
            $allowed = in_array($code, $wanted, true);
            if ($allowed !== in_array($code, $defaults, true)) {
                $result[$code] = $allowed;
            }
        }
        return $result;
    }

    private function deleteOverrides($userId)
    {
        if ($this->panel === 'owner') {
            Database::execute("DELETE FROM platform_user_permissions WHERE platform_user_id = ?", [$userId]);
            return;
        }
        Database::execute("DELETE FROM vendor_user_permissions WHERE vendor_id = ? AND vendor_user_id = ?", [$this->vendorId, $userId]);
    }

    private function insertOverride($userId, $code, $allowed)
    {
        if ($this->panel === 'owner') {
            Database::execute(
                "INSERT INTO platform_user_permissions (platform_user_id, permission, allowed, updated_by) VALUES (?, ?, ?, ?)",
                [$userId, $code, $allowed ? 1 : 0, $this->actorId]
            );
            return;
        }
        Database::execute(
            "INSERT INTO vendor_user_permissions (vendor_id, vendor_user_id, permission, allowed, updated_by) VALUES (?, ?, ?, ?, ?)",
            [$this->vendorId, $userId, $code, $allowed ? 1 : 0, $this->actorId]
        );
    }

    // Ringkasan: "+refund.manage -report.view" (kosong = lalai peranan)
    private function log($user, array $overrides)
    {
        $parts = [];
        foreach ($overrides as $code => $allowed) {
            $parts[] = ($allowed ? '+' : '-') . $code;
        }
        $detail = $user['full_name'] . ': ' . ($parts ? implode(' ', $parts) : 'default');
        if ($this->panel === 'owner') {
            (new WriteAuditLogJob('owner', $this->actorId, null, 'staff.permissions', 'platform_user', $user['id'], $detail))->run();
            return;
        }
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'staff.permissions', 'vendor_user', $user['id'], $detail))->run();
    }
}
