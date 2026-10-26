<?php
// Kira permintaan dalam tetingkap masa (jadual rate_limits). run() = true jika masih dalam had.
class ThrottleJob
{
    private $bucket;
    private $maxHits;
    private $windowSeconds;

    public function __construct($bucket, $maxHits, $windowSeconds)
    {
        $this->bucket = substr((string) $bucket, 0, 191);
        $this->maxHits = (int) $maxHits;
        $this->windowSeconds = (int) $windowSeconds;
    }

    public function run()
    {
        // Tetingkap tamat → mula semula; jika tidak → tambah satu
        Database::execute(
            "INSERT INTO rate_limits (bucket_key, hits, window_start) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                hits = IF(window_start < NOW() - INTERVAL ? SECOND, 1, hits + 1),
                window_start = IF(window_start < NOW() - INTERVAL ? SECOND, NOW(), window_start)",
            [$this->bucket, $this->windowSeconds, $this->windowSeconds]
        );
        $hits = (int) Database::fetchValue("SELECT hits FROM rate_limits WHERE bucket_key = ?", [$this->bucket]);
        return $hits <= $this->maxHits;
    }
}
