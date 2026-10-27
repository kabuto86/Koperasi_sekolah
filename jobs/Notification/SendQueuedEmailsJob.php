<?php
// Cron: hantar e-mel dari email_queue (PELAN 9 I). Gagal → cuba semula pada larian seterusnya; selepas
// MAX_ATTEMPTS kali → status failed + last_error. SMTP belum ditetapkan → tiada baris disentuh (kekal pending).
class SendQueuedEmailsJob
{
    const MAX_ATTEMPTS = 5;

    private $mailer;
    private $batch;

    // $mailer = null → dibina dari tetapan platform (owner) + config mail_driver
    public function __construct($mailer = null, $batch = 50)
    {
        $this->mailer = $mailer;
        $this->batch = max(1, (int) $batch);
    }

    // Pulangkan ['sent' => n, 'failed' => n, 'retry' => n, 'configured' => bool]
    public function run()
    {
        $mailer = $this->mailer ?: self::platformMailer();
        $result = ['sent' => 0, 'failed' => 0, 'retry' => 0, 'configured' => $mailer->isConfigured()];
        if (!$result['configured']) {
            return $result;
        }
        $rows = Database::fetchAll(
            "SELECT id, to_email, subject, body_html, attempts FROM email_queue
              WHERE status = 'pending' AND attempts < ? ORDER BY id LIMIT " . $this->batch,
            [self::MAX_ATTEMPTS]
        );
        foreach ($rows as $row) {
            $attempts = (int) $row['attempts'] + 1;
            try {
                $mailer->send($row['to_email'], $row['subject'], $row['body_html']);
                Database::execute(
                    "UPDATE email_queue SET status = 'sent', attempts = ?, last_error = NULL, sent_at = NOW() WHERE id = ?",
                    [$attempts, (int) $row['id']]
                );
                $result['sent']++;
            } catch (RuntimeException $error) {
                $status = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending';
                Database::execute(
                    "UPDATE email_queue SET status = ?, attempts = ?, last_error = ? WHERE id = ?",
                    [$status, $attempts, mb_substr($error->getMessage(), 0, 255, 'UTF-8'), (int) $row['id']]
                );
                $result[$status === 'failed' ? 'failed' : 'retry']++;
            }
        }
        return $result;
    }

    // Tetapan SMTP owner (platform_settings; kata laluan disulitkan dengan Crypto)
    public static function platformMailer()
    {
        $settings = PlatformSettings::all();
        $password = (string) ($settings['smtp_password_enc'] ?? '');
        return new Mailer([
            'driver'       => Config::get('mail_driver', 'smtp'),
            'log_file'     => APP_ROOT . '/' . Config::get('mail_log_file', 'storage/mail.log'),
            'host'         => (string) ($settings['smtp_host'] ?? ''),
            'port'         => (int) ($settings['smtp_port'] ?? 587),
            'encryption'   => (string) ($settings['smtp_encryption'] ?? 'tls'),
            'username'     => (string) ($settings['smtp_username'] ?? ''),
            'password'     => $password !== '' ? (string) Crypto::decrypt($password) : '',
            'from_address' => (string) ($settings['mail_from_address'] ?? ''),
            'from_name'    => (string) ($settings['mail_from_name'] ?? Config::get('name')),
        ]);
    }
}
