<?php
// Padam pengumuman (draf atau yang sudah terbit — vendor tidak lagi melihatnya).
class DeleteAnnouncementJob
{
    private $actorId;
    private $announcementId;

    public function __construct($actorId, $announcementId)
    {
        $this->actorId = (int) $actorId;
        $this->announcementId = (int) $announcementId;
    }

    public function run()
    {
        $title = Database::fetchValue("SELECT title FROM vendor_announcements WHERE id = ?", [$this->announcementId]);
        if ($title === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute("DELETE FROM vendor_announcements WHERE id = ?", [$this->announcementId]);
        (new WriteAuditLogJob('owner', $this->actorId, null, 'announcement.delete', 'announcement', $this->announcementId, $title))->run();
        return ['id' => $this->announcementId];
    }
}
