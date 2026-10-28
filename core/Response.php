<?php
// Jawapan JSON ikut kontrak PELAN 18.1:
//   { ok: true, data }  atau  { ok: false, error, errors }
class Response
{
    public static function ok($data = null)
    {
        self::json(['ok' => true, 'data' => $data], 200);
    }

    // $error sudah diterjemah (t()). $errors = [ 'medan' => 'mesej' ]
    public static function fail($error, $errors = [], $status = 422, $extra = [])
    {
        $payload = ['ok' => false, 'error' => $error, 'errors' => (object) $errors];
        foreach ($extra as $key => $value) {
            $payload[$key] = $value;
        }
        self::json($payload, $status);
    }

    // Rekod tiada ATAU milik vendor lain — mesej sama, tidak dedah kewujudan
    public static function notFound()
    {
        self::fail(t('error.not_found'), [], 404);
    }

    public static function forbidden()
    {
        self::fail(t('error.forbidden'), [], 403);
    }

    public static function methodNotAllowed()
    {
        self::fail(t('error.method_not_allowed'), [], 405);
    }

    // Jawapan teks biasa (panggilan balik gateway: "OK", "RECEIVEOK")
    public static function text($body, $status = 200)
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo $body;
        exit;
    }

    public static function json($payload, $status = 200)
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
