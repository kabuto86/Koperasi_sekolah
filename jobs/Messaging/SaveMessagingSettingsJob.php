<?php
// Simpan tetapan WhatsApp/SMS (vendor/messaging/save — PELAN 18.13). Dihidupkan → no. penghantar 60… dan kunci API wajib
// (kosong = kekalkan kunci lama). Kunci format ID:RAHSIA, disulitkan Crypto. iSMS = SMS sahaja.
// Templat wajib, ≤ 500 aksara, hanya pemegang tempat MessagingSettings::PLACEHOLDERS.
class SaveMessagingSettingsJob
{
    const MAX_TEMPLATE = 500;

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
        $current = MessagingSettings::find($this->vendorId, $this->cooperativeId);
        $data = $this->validate($current);
        $keyEnc = $data['api_key'] !== '' ? Crypto::encrypt($data['api_key']) : ($current['api_key_enc'] ?? null);
        Database::execute(
            "INSERT INTO messaging_settings (vendor_id, cooperative_id, provider, channel, sender, api_key_enc, is_enabled, events_json, templates_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE provider = VALUES(provider), channel = VALUES(channel), sender = VALUES(sender),
                 api_key_enc = VALUES(api_key_enc), is_enabled = VALUES(is_enabled), events_json = VALUES(events_json),
                 templates_json = VALUES(templates_json)",
            [
                $this->vendorId, $this->cooperativeId, $data['provider'], $data['channel'], $data['sender'], $keyEnc, $data['is_enabled'],
                json_encode($data['events']), json_encode($data['templates'], JSON_UNESCAPED_UNICODE),
            ]
        );
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'messaging_save', 'messaging', null, ''))->run();
        return MessagingSettings::publicShape(MessagingSettings::find($this->vendorId, $this->cooperativeId));
    }

    private function validate($current)
    {
        $input = $this->input;
        $v = new Validator($input);
        $v->required('provider')->inList('provider', MessagingSettings::PROVIDERS)
          ->required('channel')->inList('channel', MessagingSettings::CHANNELS);
        $enabled = !empty($input['is_enabled']);
        $sender = trim((string) ($input['sender'] ?? ''));
        $apiKey = trim((string) ($input['api_key'] ?? ''));
        if (($input['provider'] ?? '') === 'isms' && ($input['channel'] ?? '') === 'whatsapp') {
            $v->addError('channel', t('messaging.isms_sms_only'));
        }
        if (($enabled || $sender !== '') && !preg_match('/^60\d{8,11}$/', $sender)) {
            $v->addError('sender', t('messaging.sender_format'));
        }
        if ($apiKey !== '' && (mb_strlen($apiKey, 'UTF-8') > 500 || MessageSender::splitKey($apiKey)[0] === '')) {
            $v->addError('api_key', t('messaging.key_format'));
        } elseif ($enabled && $apiKey === '' && (string) ($current['api_key_enc'] ?? '') === '') {
            $v->addError('api_key', t('error.required'));
        }
        $events = [];
        $templates = [];
        foreach (MessagingSettings::EVENTS as $event) {
            $events[$event] = !empty($input['events'][$event]);
            $text = trim((string) ($input['templates'][$event] ?? ''));
            $templates[$event] = $text;
            preg_match_all('/\{([a-z_]+)\}/', $text, $matches);
            $unknown = array_diff($matches[1], MessagingSettings::PLACEHOLDERS);
            if ($text === '') {
                $v->addError('templates.' . $event, t('error.required'));
            } elseif (mb_strlen($text, 'UTF-8') > self::MAX_TEMPLATE) {
                $v->addError('templates.' . $event, t('error.max_length', ['max' => self::MAX_TEMPLATE]));
            } elseif ($unknown) {
                $tags = implode(', ', array_map(function ($tag) {
                    return '{' . $tag . '}';
                }, array_unique($unknown)));
                $v->addError('templates.' . $event, t('messaging.unknown_placeholder', ['tags' => $tags]));
            }
        }
        ValidationException::throwIfFails($v);
        return [
            'provider'   => $input['provider'],
            'channel'    => $input['channel'],
            'sender'     => $sender,
            'api_key'    => $apiKey,
            'is_enabled' => $enabled ? 1 : 0,
            'events'     => $events,
            'templates'  => $templates,
        ];
    }
}
