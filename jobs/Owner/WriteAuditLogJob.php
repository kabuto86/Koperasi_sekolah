<?php
// Log audit platform untuk tindakan sensitif (PELAN 15). detail = teks ringkas (disimpan dalam detail_json).
class WriteAuditLogJob
{
    private $actorType;
    private $actorId;
    private $vendorId;
    private $action;
    private $entity;
    private $entityId;
    private $detail;

    public function __construct($actorType, $actorId, $vendorId, $action, $entity = null, $entityId = null, $detail = '')
    {
        $this->actorType = $actorType;
        $this->actorId = $actorId;
        $this->vendorId = $vendorId;
        $this->action = $action;
        $this->entity = $entity;
        $this->entityId = $entityId;
        $this->detail = $detail;
    }

    public function run()
    {
        return Database::insert(
            "INSERT INTO audit_logs (actor_type, actor_id, vendor_id, action, entity, entity_id, detail_json, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->actorType, $this->actorId, $this->vendorId, $this->action, $this->entity, $this->entityId,
                json_encode(['detail' => (string) $this->detail], JSON_UNESCAPED_UNICODE), Request::ip(),
            ]
        );
    }
}
