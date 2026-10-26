<?php
// Log aktiviti vendor (vendor/activity-log). cooperative_id NULL = tindakan peringkat vendor.
class WriteActivityLogJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $action;
    private $entity;
    private $entityId;
    private $detail;

    public function __construct($vendorId, $cooperativeId, $userId, $action, $entity = null, $entityId = null, $detail = '')
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = $cooperativeId;
        $this->userId = $userId;
        $this->action = $action;
        $this->entity = $entity;
        $this->entityId = $entityId;
        $this->detail = $detail;
    }

    public function run()
    {
        return Database::insert(
            "INSERT INTO activity_logs (vendor_id, cooperative_id, vendor_user_id, action, entity, entity_id, detail_json)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $this->vendorId, $this->cooperativeId, $this->userId, $this->action, $this->entity, $this->entityId,
                json_encode(['detail' => (string) $this->detail], JSON_UNESCAPED_UNICODE),
            ]
        );
    }
}
