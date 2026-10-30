<?php
// Tambah / sunting acara kalendar (vendor/calendar/save): jenis dari senarai tetap, tamat ≥ mula, julat maks 366 hari.
class SaveCalendarEventJob
{
    const MAX_DAYS = 366;

    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        if ($id > 0 && CalendarEvents::find($this->vendorId, $this->cooperativeId, $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('title')->maxLength('title', 150)
          ->required('event_type')->inList('event_type', CalendarEvents::TYPES)
          ->required('starts_on')->date('starts_on')
          ->required('ends_on')->date('ends_on');
        $starts = trim((string) ($this->input['starts_on'] ?? ''));
        $ends = trim((string) ($this->input['ends_on'] ?? ''));
        if (!$v->fails() && $ends < $starts) {
            $v->addError('ends_on', t('addon.end_before_start'));
        } elseif (!$v->fails() && (strtotime($ends) - strtotime($starts)) / 86400 >= self::MAX_DAYS) {
            $v->addError('ends_on', t('error.invalid'));
        }
        ValidationException::throwIfFails($v);

        $title = trim((string) $this->input['title']);
        $values = [$title, $this->input['event_type'], $starts, $ends, !empty($this->input['blocks_orders']) ? 1 : 0];
        if ($id > 0) {
            Database::execute(
                "UPDATE calendar_events SET title = ?, event_type = ?, starts_on = ?, ends_on = ?, blocks_orders = ?
                  WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                array_merge($values, [$id, $this->vendorId, $this->cooperativeId])
            );
        } else {
            $id = Database::insert(
                "INSERT INTO calendar_events (vendor_id, cooperative_id, title, event_type, starts_on, ends_on, blocks_orders) VALUES (?, ?, ?, ?, ?, ?, ?)",
                array_merge([$this->vendorId, $this->cooperativeId], $values)
            );
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'calendar_save', 'calendar_event', $id, $title))->run();
        return CalendarEvents::shape(CalendarEvents::find($this->vendorId, $this->cooperativeId, $id));
    }
}
