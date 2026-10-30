<?php
// Cron (cron/send-messages.php): hantar WhatsApp/SMS dari notification_queue melalui pembekal koperasi (messaging/).
// Gagal → cuba semula pada larian seterusnya; selepas MAX_ATTEMPTS → failed + last_error. Tetapan dimatikan / modul tamat → failed.
// Baris push (B15) tidak disentuh.
class SendQueuedMessagesJob
{
    const MAX_ATTEMPTS = 3;

    private $batch;
    private $senders = [];

    public function __construct($batch = 50)
    {
        $this->batch = max(1, (int) $batch);
    }

    // Pulangkan ['sent' => n, 'failed' => n, 'retry' => n]
    public function run()
    {
        $result = ['sent' => 0, 'failed' => 0, 'retry' => 0];
        $rows = Database::fetchAll(
            "SELECT id, vendor_id, cooperative_id, channel, recipient, message, attempts FROM notification_queue
              WHERE status = 'pending' AND channel IN ('whatsapp', 'sms') AND attempts < ? ORDER BY id LIMIT " . $this->batch,
            [self::MAX_ATTEMPTS]
        );
        foreach ($rows as $row) {
            $attempts = (int) $row['attempts'] + 1;
            try {
                $this->sender($row)->send($row['recipient'], $row['message']);
                Database::execute(
                    "UPDATE notification_queue SET status = 'sent', attempts = ?, last_error = NULL, sent_at = NOW() WHERE id = ?",
                    [$attempts, (int) $row['id']]
                );
                $result['sent']++;
            } catch (MessagingException $error) {
                $status = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending';
                Database::execute(
                    "UPDATE notification_queue SET status = ?, attempts = ?, last_error = ? WHERE id = ?",
                    [$status, $attempts, mb_substr($error->getMessage(), 0, 255, 'UTF-8'), (int) $row['id']]
                );
                $result[$status === 'failed' ? 'failed' : 'retry']++;
            }
        }
        return $result;
    }

    // Driver pembekal ikut koperasi (dicache satu larian). Tetapan tiada / dimatikan / modul tamat → MessagingException
    private function sender(array $row)
    {
        $key = (int) $row['cooperative_id'];
        if (!array_key_exists($key, $this->senders)) {
            $settings = MessagingSettings::find($row['vendor_id'], $row['cooperative_id']);
            $active = $settings !== null && (int) $settings['is_enabled'] === 1
                && ModuleAccess::has($row['vendor_id'], $row['cooperative_id'], 'whatsapp_sms');
            $this->senders[$key] = $active
                ? MessageSender::make($settings['provider'], $row['channel'], $settings['sender'], (string) Crypto::decrypt((string) $settings['api_key_enc']))
                : null;
        }
        if ($this->senders[$key] === null) {
            throw new MessagingException('Notifikasi dimatikan atau modul whatsapp_sms tidak aktif');
        }
        return $this->senders[$key];
    }
}
