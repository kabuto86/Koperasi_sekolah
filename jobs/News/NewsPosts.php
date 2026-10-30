<?php
// Pembantu modul berita (news — PELAN 18.13): cari ikut vendor sesi + koperasi, bentuk NewsPost, penerima & e-mel.
// E-mel berita dimasukkan ke email_queue SEKALI sahaja (email_sent_at ditanda secara atom) — bila berita sudah terbit;
// berita berjadual → QueueDueNewsEmailsJob (cron/send-emails.php) pada masa terbit.
class NewsPosts
{
    // draft = tidak aktif / tiada tarikh · scheduled = masa depan · published
    const STATE_SQL = "(CASE WHEN is_active = 0 OR published_at IS NULL THEN 'draft' WHEN published_at > NOW() THEN 'scheduled' ELSE 'published' END)";

    public static function find($vendorId, $cooperativeId, $id)
    {
        return Database::fetchOne(
            "SELECT *, " . self::STATE_SQL . " AS state FROM news_posts WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
            [(int) $id, (int) $vendorId, (int) $cooperativeId]
        );
    }

    public static function shape(array $row)
    {
        return [
            'id'               => (int) $row['id'],
            'title'            => $row['title'],
            'body'             => $row['body'],
            'image_url'        => Uploader::url($row['image_path']),
            'published_at'     => IsoDate::format($row['published_at']),
            'is_active'        => (bool) $row['is_active'],
            'send_email'       => (bool) $row['send_email'],
            'email_sent_at'    => IsoDate::format($row['email_sent_at']),
            'email_recipients' => (int) $row['email_recipients'],
            'push_sent_at'     => IsoDate::format($row['push_sent_at']),
            'push_recipients'  => (int) $row['push_recipients'],
            'state'            => $row['state'],
        ];
    }

    // Ibu bapa yang mempunyai anak aktif di koperasi ini (akaun aktif, e-mel disahkan, belum dipadam)
    public static function recipients($vendorId, $cooperativeId)
    {
        return Database::fetchAll(
            "SELECT DISTINCT c.id, c.email, c.language FROM customers c
               JOIN children ch ON ch.customer_id = c.id
              WHERE ch.vendor_id = ? AND ch.cooperative_id = ? AND ch.is_active = 1
                AND c.is_active = 1 AND c.deleted_at IS NULL AND c.email IS NOT NULL AND c.email_verified_at IS NOT NULL
              ORDER BY c.id",
            [(int) $vendorId, (int) $cooperativeId]
        );
    }

    // Masukkan e-mel berita ke baris gilir jika perlu. Pulangkan bilangan e-mel (0 = tiada / sudah dihantar).
    // Dipanggil dalam transaksi: UPDATE … WHERE email_sent_at IS NULL memastikan hanya satu panggilan menang.
    public static function queueEmail(array $post)
    {
        if ((int) $post['send_email'] !== 1 || $post['email_sent_at'] !== null || $post['state'] !== 'published') {
            return 0;
        }
        $vendorId = (int) $post['vendor_id'];
        $cooperativeId = (int) $post['cooperative_id'];
        $claimed = Database::execute(
            "UPDATE news_posts SET email_sent_at = NOW() WHERE id = ? AND vendor_id = ? AND email_sent_at IS NULL",
            [(int) $post['id'], $vendorId]
        );
        if ($claimed !== 1) {
            return 0;
        }
        $cooperative = Database::fetchOne("SELECT slug, school_name FROM cooperatives WHERE id = ? AND vendor_id = ?", [$cooperativeId, $vendorId]);
        $recipients = self::recipients($vendorId, $cooperativeId);
        foreach ($recipients as $customer) {
            $vars = [
                'title'       => $post['title'],
                'body'        => $post['body'],
                'school_name' => $cooperative['school_name'],
                'link'        => View::absoluteStoreUrl($cooperative['slug'], 'news'),
            ];
            (new QueueEmailJob($vendorId, $customer['email'], 'news_post', $customer['language'], $vars))->run();
        }
        Database::execute("UPDATE news_posts SET email_recipients = ? WHERE id = ? AND vendor_id = ?", [count($recipients), (int) $post['id'], $vendorId]);
        return count($recipients);
    }
}
