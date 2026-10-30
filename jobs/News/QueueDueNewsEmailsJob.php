<?php
// Cron (cron/send-emails.php): berita berjadual yang kini sudah terbit dan menanti e-mel → email_queue.
// Hanya koperasi yang masih melanggan modul news. Setiap berita dalam transaksi sendiri (NewsPosts::queueEmail atom).
class QueueDueNewsEmailsJob
{
    private $limit;

    public function __construct($limit = 50)
    {
        $this->limit = max(1, (int) $limit);
    }

    // Pulangkan bilangan e-mel yang dimasukkan ke baris gilir
    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT id, vendor_id, cooperative_id FROM news_posts
              WHERE send_email = 1 AND email_sent_at IS NULL AND is_active = 1 AND published_at IS NOT NULL AND published_at <= NOW()
              ORDER BY published_at LIMIT " . $this->limit
        );
        $count = 0;
        foreach ($rows as $row) {
            if (!ModuleAccess::has($row['vendor_id'], $row['cooperative_id'], 'news')) {
                continue;
            }
            Database::beginTransaction();
            try {
                $post = NewsPosts::find($row['vendor_id'], $row['cooperative_id'], $row['id']);
                $count += $post === null ? 0 : NewsPosts::queueEmail($post);
                Database::commit();
            } catch (Throwable $error) {
                Database::rollBack();
                throw $error;
            }
        }
        return $count;
    }
}
