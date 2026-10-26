<?php
// E-mel ujian tetapan SMTP — masuk email_queue; dihantar oleh cron send-emails (B7).
class SendTestEmailJob
{
    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $v = new Validator($this->input);
        $v->required('to')->email('to')->maxLength('to', 191);
        ValidationException::throwIfFails($v);

        $to = strtolower(trim($this->input['to']));
        (new QueueEmailJob(null, $to, 'test_email', Lang::code(), [
            'name' => PlatformSettings::get('platform_name', Config::get('name')),
        ]))->run();
        (new WriteAuditLogJob('owner', $this->actorId, null, 'settings.test_email', 'platform_settings', null, $to))->run();
        return ['queued' => true];
    }
}
