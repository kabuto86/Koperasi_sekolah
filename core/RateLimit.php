<?php
// Had kadar permintaan (log masuk, daftar, lupa kata laluan, kod ambil, API Flutter — PELAN 15).
// Kiraan disimpan dalam jadual rate_limits melalui ThrottleJob.
//
//   RateLimit::enforce('store_register:' . Request::ip(), 10, 3600);   // 10 kali sejam per IP
class RateLimit
{
    // true = dibenarkan (dan dikira)
    public static function attempt($bucket, $maxHits, $windowSeconds)
    {
        $job = new ThrottleJob($bucket, $maxHits, $windowSeconds);
        return $job->run();
    }

    // Lebih had → HTTP 429 dan berhenti
    public static function enforce($bucket, $maxHits, $windowSeconds)
    {
        if (!self::attempt($bucket, $maxHits, $windowSeconds)) {
            Response::fail(t('error.too_many_requests'), [], 429);
        }
    }
}
