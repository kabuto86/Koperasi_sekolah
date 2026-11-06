<?php
// Pembantu modul WhatsApp/SMS (whatsapp_sms — PELAN 18.13): tetapan per koperasi, templat & nombor telefon.
// Kunci API disulitkan (Crypto) dan TIDAK PERNAH dipulangkan — hanya api_key_set.
class MessagingSettings
{
    const EVENTS = ['order_ready', 'order_collected', 'cash_reminder'];
    const PROVIDERS = ['whatsapp_cloud', 'twilio', 'isms'];
    const CHANNELS = ['whatsapp', 'sms'];
    const PLACEHOLDERS = ['parent_name', 'child_name', 'short_code', 'school_name', 'picked_by', 'time', 'expires_on'];
    const DEFAULT_EVENTS = ['order_ready' => true, 'order_collected' => true, 'cash_reminder' => false];

    // W3: parameter badan templat Meta ikut tertib {{1}}, {{2}}, … (vendor mencipta templat di Meta dengan tertib yang sama)
    const META_PARAMS = [
        'order_ready'     => ['parent_name', 'child_name', 'short_code', 'school_name'],
        'order_collected' => ['parent_name', 'child_name', 'picked_by', 'time', 'school_name'],
        'cash_reminder'   => ['parent_name', 'child_name', 'short_code', 'expires_on', 'school_name'],
    ];
    const META_NAME_PATTERN = '/^[a-z0-9_]{1,512}$/';
    const META_LANGUAGE_PATTERN = '/^[a-z]{2,3}(_[A-Z]{2})?$/';

    public static function find($vendorId, $cooperativeId)
    {
        return Database::fetchOne(
            "SELECT * FROM messaging_settings WHERE vendor_id = ? AND cooperative_id = ?",
            [(int) $vendorId, (int) $cooperativeId]
        );
    }

    // Bentuk awam (vendor/messaging/get & save). Tiada baris → nilai lalai (dimatikan)
    public static function publicShape($row)
    {
        $events = self::DEFAULT_EVENTS;
        $templates = [];
        foreach (self::EVENTS as $event) {
            $templates[$event] = t('messaging.default.' . $event);
        }
        if ($row !== null) {
            $events = array_merge($events, array_intersect_key((array) json_decode((string) $row['events_json'], true), $events));
            $templates = array_merge($templates, array_intersect_key((array) json_decode((string) $row['templates_json'], true), $templates));
        }
        $meta = self::metaTemplates($row);
        return [
            'provider'    => $row['provider'] ?? 'whatsapp_cloud',
            'channel'     => $row['channel'] ?? 'whatsapp',
            'sender'      => (string) ($row['sender'] ?? ''),
            'api_key_set' => $row !== null && (string) $row['api_key_enc'] !== '',
            'is_enabled'  => $row !== null && (int) $row['is_enabled'] === 1,
            'events'      => array_map('boolval', $events),
            'templates'   => $templates,
            'meta_templates' => $meta,
            'meta_params' => self::META_PARAMS,
        ];
    }

    // {peristiwa: {name, language}} — nama kosong = mesej teks biasa
    public static function metaTemplates($row)
    {
        $saved = $row === null ? [] : (array) json_decode((string) ($row['meta_templates_json'] ?? ''), true);
        $result = [];
        foreach (self::EVENTS as $event) {
            $item = is_array($saved[$event] ?? null) ? $saved[$event] : [];
            $result[$event] = [
                'name'     => (string) ($item['name'] ?? ''),
                'language' => (string) ($item['language'] ?? 'ms'),
            ];
        }
        return $result;
    }

    // Templat Meta untuk peristiwa ini jika WhatsApp Cloud + saluran WhatsApp + nama diisi: {name, language, params[]}; selain itu null
    public static function metaMessage($row, $event, array $vars)
    {
        if ($row === null || $row['provider'] !== 'whatsapp_cloud' || $row['channel'] !== 'whatsapp' || !isset(self::META_PARAMS[$event])) {
            return null;
        }
        $template = self::metaTemplates($row)[$event];
        if ($template['name'] === '') {
            return null;
        }
        $params = [];
        foreach (self::META_PARAMS[$event] as $name) {
            $value = trim((string) ($vars[$name] ?? ''));
            $params[] = $value === '' ? '-' : $value;
        }
        return ['name' => $template['name'], 'language' => $template['language'], 'params' => $params];
    }

    // {parent_name} → nilai; pemegang tempat lain dibiarkan
    public static function render($template, array $vars)
    {
        foreach ($vars as $name => $value) {
            $template = str_replace('{' . $name . '}', (string) $value, $template);
        }
        return $template;
    }

    // Nombor Malaysia → antarabangsa tanpa + (012-345 6789 → 60123456789). Tidak sah → null
    public static function phone($raw)
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strpos($digits, '0') === 0) {
            $digits = '6' . $digits;
        }
        return preg_match('/^601\d{8,9}$/', $digits) ? $digits : null;
    }

    // 60123344556 → 012-***4556
    public static function mask($phone)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strpos($digits, '60') === 0) {
            $digits = substr($digits, 1);
        }
        return substr($digits, 0, 3) . '-***' . substr($digits, -4);
    }
}
