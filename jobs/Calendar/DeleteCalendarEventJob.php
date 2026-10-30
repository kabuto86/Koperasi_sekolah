<?php
// Padam acara kalendar (vendor/calendar/delete).
class DeleteCalendarEventJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $id;

    public function __construct($vendorId, $cooperativeId, $userId, $id)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->id = (int) $id;
    }

    public function run()
    {
        $event = CalendarEvents::find($this->vendorId, $this->cooperativeId, $this->id);
        if ($event === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute("DELETE FROM calendar_events WHERE id = ? AND vendor_id = ? AND cooperative_id = ?", [$this->id, $this->vendorId, $this->cooperativeId]);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'calendar_delete', 'calendar_event', $this->id, $event['title']))->run();
        return ['id' => $this->id];
    }
}
