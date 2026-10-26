<?php
// Kebenaran seorang kakitangan untuk modal "Kebenaran" (PELAN 4.1).
//   owner:  new GetStaffPermissionsJob('owner', $actorId, null, $userId)
//   vendor: new GetStaffPermissionsJob('vendor', $actorId, $vendorIdDariSesi, $userId) — kakitangan vendor lain → 404
class GetStaffPermissionsJob
{
    private $panel;
    private $actorId;
    private $vendorId;
    private $userId;

    public function __construct($panel, $actorId, $vendorId, $userId)
    {
        $this->panel = $panel === 'owner' ? 'owner' : 'vendor';
        $this->actorId = (int) $actorId;
        $this->vendorId = (int) $vendorId;
        $this->userId = (int) $userId;
    }

    public function run()
    {
        $user = self::findUser($this->panel, $this->vendorId, $this->userId);
        if ($user === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $defaults = Permission::defaults($user['role']);
        $effective = Permission::effective($user['role'], LoadUserPermissionsJob::overrides($this->panel, $user['id']));
        $editable = Permission::isEditableRole($user['role']) && $user['id'] !== $this->actorId;

        $groups = [];
        foreach (Permission::CATALOG[$this->panel] as $group => $codes) {
            $items = [];
            foreach ($codes as $code) {
                $items[] = [
                    'code'    => $code,
                    'allowed' => in_array($code, $effective, true),
                    'default' => in_array($code, $defaults, true),
                    'locked'  => !Permission::isEditableRole($user['role']) || Permission::isLocked($this->panel, $code),
                ];
            }
            $groups[] = ['key' => $group, 'items' => $items];
        }
        return [
            'user'     => ['id' => $user['id'], 'full_name' => $user['full_name'], 'role' => $user['role']],
            'editable' => $editable,
            'groups'   => $groups,
        ];
    }

    // [id, full_name, role] atau null. Vendor: hanya kakitangan vendor sesi
    public static function findUser($panel, $vendorId, $userId)
    {
        $row = $panel === 'owner'
            ? Database::fetchOne("SELECT id, full_name, role FROM platform_users WHERE id = ?", [(int) $userId])
            : Database::fetchOne("SELECT id, full_name, role FROM vendor_users WHERE id = ? AND vendor_id = ?", [(int) $userId, (int) $vendorId]);
        if ($row === null) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        return $row;
    }
}
