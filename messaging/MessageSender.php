<?php
// Asas pembekal WhatsApp/SMS (modul whatsapp_sms — B13). Driver TIDAK menyentuh DB — hanya bercakap dengan API pembekal (curl).
// Kunci API satu rentetan "ID:RAHSIA" (disimpan disulitkan dalam messaging_settings.api_key_enc):
//   whatsapp_cloud = phone_number_id:access_token · twilio = account_sid:auth_token · isms = nama_pengguna:kata_laluan
// Ujian: pemboleh ubah persekitaran KOPERASI_MESSAGING_TEST_URL menghala SEMUA permintaan ke tools/fake-messaging.php ({url}/{kod}).
//
//   $sender = MessageSender::make('twilio', 'sms', '60123456789', 'ACxxx:token');
//   $sender->send('60123456789', 'Teks mesej');   // gagal → MessagingException
abstract class MessageSender
{
    const CODE = '';
    const BASE_URL = '';
    const TIMEOUT = 15;

    const PROVIDERS = ['whatsapp_cloud' => 'WhatsappCloudSender', 'twilio' => 'TwilioSender', 'isms' => 'IsmsSender'];

    protected $channel;
    protected $sender;
    protected $keyId;
    protected $keySecret;

    public function __construct($channel, $sender, $apiKey)
    {
        $this->channel = (string) $channel;
        $this->sender = (string) $sender;
        list($this->keyId, $this->keySecret) = self::splitKey($apiKey);
    }

    public static function make($provider, $channel, $sender, $apiKey)
    {
        if (!isset(self::PROVIDERS[$provider])) {
            throw new MessagingException('Pembekal tidak dikenali: ' . $provider);
        }
        $class = self::PROVIDERS[$provider];
        return new $class($channel, $sender, $apiKey);
    }

    // "ID:RAHSIA" → [ID, RAHSIA]; format salah → ['', '']
    public static function splitKey($apiKey)
    {
        $parts = explode(':', (string) $apiKey, 2);
        if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            return ['', ''];
        }
        return [trim($parts[0]), trim($parts[1])];
    }

    // $phone = nombor antarabangsa tanpa + (60123456789)
    abstract public function send($phone, $text);

    protected function baseUrl()
    {
        $testUrl = getenv('KOPERASI_MESSAGING_TEST_URL');
        return $testUrl ? rtrim($testUrl, '/') . '/' . static::CODE : static::BASE_URL;
    }

    // Pulangkan ['status' => kod HTTP, 'body' => teks, 'json' => array | null]. Rangkaian gagal → MessagingException
    protected function http($method, $url, $body = null, array $headers = [], $basicAuth = null)
    {
        $curl = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = is_array($body) ? http_build_query($body) : $body;
        }
        if ($basicAuth !== null) {
            $options[CURLOPT_USERPWD] = $basicAuth;
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($response === false) {
            throw new MessagingException(static::CODE . ': ' . $error);
        }
        return ['status' => $status, 'body' => (string) $response, 'json' => json_decode((string) $response, true)];
    }

    protected function requireKey()
    {
        if ($this->keyId === '' || $this->keySecret === '') {
            throw new MessagingException(static::CODE . ': kunci API tidak lengkap (ID:RAHSIA)');
        }
    }
}
