<?php
// Asas driver gateway: kunci API, mod sandbox/live, URL asas & permintaan HTTP (curl).
// Ujian (tools/verify-b8.php): pemboleh ubah persekitaran KOPERASI_GATEWAY_TEST_URL menghala mod SANDBOX sahaja ke
// tools/fake-gateway.php ({url}/{kod}). Mod live tidak pernah terjejas.
abstract class BaseGateway implements GatewayInterface
{
    const CODE = '';
    const URLS = ['sandbox' => '', 'live' => ''];
    const TIMEOUT = 20;

    protected $config;
    protected $mode;

    public function __construct(array $config, $mode)
    {
        $this->config = $config;
        $this->mode = $mode;
    }

    public function amountLocked()
    {
        return false;
    }

    public function supportsRefund()
    {
        return false;
    }

    public function refund($gatewayRef, $amount, $reference)
    {
        throw new GatewayException(static::CODE . ': refund tidak disokong');
    }

    public function callbackResponse()
    {
        return 'OK';
    }

    protected function value($name)
    {
        return trim((string) ($this->config[$name] ?? ''));
    }

    protected function baseUrl()
    {
        $testUrl = getenv('KOPERASI_GATEWAY_TEST_URL');
        if ($this->mode === 'sandbox' && $testUrl) {
            return rtrim($testUrl, '/') . '/' . static::CODE;
        }
        return static::URLS[$this->mode];
    }

    // Permintaan HTTP. $fields = array (borang x-www-form-urlencoded) atau null. $headers = ['Nama: nilai'].
    // Pulangkan ['status' => kod HTTP, 'body' => teks, 'json' => array | null]. Rangkaian gagal → GatewayException
    protected function http($method, $url, $fields = null, array $headers = [], $basicAuth = null)
    {
        $curl = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($fields !== null) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }
        if ($basicAuth !== null) {
            $options[CURLOPT_USERPWD] = $basicAuth;
        }
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false) {
            throw new GatewayException(static::CODE . ': ' . $error);
        }
        return ['status' => $status, 'body' => (string) $body, 'json' => json_decode((string) $body, true)];
    }

    // Sen → "12.50"
    protected static function ringgit($amount)
    {
        return number_format((int) $amount / 100, 2, '.', '');
    }

    // "12.50" / "1,250.00" → sen
    protected static function sen($value)
    {
        return (int) round((float) str_replace(',', '', (string) $value) * 100);
    }

    // Teks selamat untuk gateway yang hanya menerima huruf, nombor, ruang & "_" (ToyyibPay, SenangPay). "-" → "_"
    protected static function plainText($text, $max)
    {
        $clean = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 _]/', ' ', str_replace('-', '_', (string) $text))));
        return substr($clean, 0, $max);
    }
}
