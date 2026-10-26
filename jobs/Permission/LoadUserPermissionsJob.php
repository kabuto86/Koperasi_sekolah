<?php
// Kebenaran sebenar seorang pengguna panel = lalai peranan ± pengecualian (PELAN 4.1).
//   new LoadUserPermissionsJob('vendor', $vendorUserId, 'cashier')  → ['handover.process', 'pos.sell', ...]
class LoadUserPermissionsJob
{
    private $panel;
    private $userId;
    private $role;

    public function __construct($panel, $userId, $role)
    {
        $this->panel = $panel === 'owner' ? 'owner' : 'vendor';
        $this->userId = (int) $userId;
        $this->role = (string) $role;
    }

    public function run()
    {
        return Permission::effective($this->role, self::overrides($this->panel, $this->userId));
    }

    // [ kod => true|false ]
    public static function overrides($panel, $userId)
    {
        $rows = $panel === 'owner'
            ? Database::fetchAll("SELECT permission, allowed FROM platform_user_permissions WHERE platform_user_id = ?", [(int) $userId])
            : Database::fetchAll("SELECT permission, allowed FROM vendor_user_permissions WHERE vendor_user_id = ?", [(int) $userId]);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['permission']] = (bool) $row['allowed'];
        }
        return $result;
    }
}
