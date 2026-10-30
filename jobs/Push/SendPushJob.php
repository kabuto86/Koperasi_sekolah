<?php
// Cron (cron/send-push.php): hantar push apps dari notification_queue (channel 'push') melalui FCM (messaging/FcmSender).
// Akaun perkhidmatan Firebase belum dipasang → baris kekal pending (seperti e-mel tanpa SMTP). Gagal → cuba semula;
// selepas MAX_ATTEMPTS → failed + last_error. Token ditolak FCM → peranti dibuang. Peranti sudah tiada / modul mobile_app
// tamat → failed tanpa dihantar. Baris WhatsApp/SMS tidak disentuh (SendQueuedMessagesJob).
class SendPushJob
{
    const MAX_ATTEMPTS = 3;

    private $batch;
    private $accountFile;
    private $modules = [];

    // $accountFile = laluan fail akaun perkhidmatan (lalai: config fcm_service_account_file)
    public function __construct($batch = 100, $accountFile = null)
    {
        $this->batch = max(1, (int) $batch);
        $this->accountFile = $accountFile !== null ? (string) $accountFile : APP_ROOT . '/' . Config::get('fcm_service_account_file');
    }

    // Pulangkan ['configured' => bool, 'sent' => n, 'failed' => n, 'retry' => n]
    public function run()
    {
        $result = ['configured' => true, 'sent' => 0, 'failed' => 0, 'retry' => 0];
        $rows = Database::fetchAll(
            "SELECT id, vendor_id, cooperative_id, recipient, message, attempts FROM notification_queue
              WHERE status = 'pending' AND channel = 'push' AND attempts < ? ORDER BY id LIMIT " . $this->batch,
            [self::MAX_ATTEMPTS]
        );
        if (count($rows) === 0) {
            return $result;
        }
        try {
            $sender = FcmSender::fromFile($this->accountFile);
        } catch (MessagingException $error) {
            $result['configured'] = false;
            return $result;
        }
        foreach ($rows as $row) {
            $status = $this->sendOne($sender, $row);
            $result[$status]++;
        }
        return $result;
    }

    // 'sent' | 'retry' | 'failed'
    private function sendOne(FcmSender $sender, array $row)
    {
        $attempts = (int) $row['attempts'] + 1;
        $message = json_decode((string) $row['message'], true);
        $device = Database::fetchValue("SELECT id FROM push_devices WHERE push_token = ?", [$row['recipient']]);
        if ($device === null || !is_array($message) || !$this->moduleActive($row)) {
            return $this->finish($row, 'failed', $attempts, 'Peranti tiada lagi atau modul mobile_app tidak aktif');
        }
        try {
            $sender->send($row['recipient'], $message['title'] ?? '', $message['body'] ?? '', (array) ($message['data'] ?? []));
            return $this->finish($row, 'sent', $attempts, null);
        } catch (PushTokenInvalidException $error) {
            Database::execute("DELETE FROM push_devices WHERE push_token = ?", [$row['recipient']]);
            return $this->finish($row, 'failed', $attempts, $error->getMessage());
        } catch (MessagingException $error) {
            return $this->finish($row, $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'retry', $attempts, $error->getMessage());
        }
    }

    private function finish(array $row, $status, $attempts, $error)
    {
        Database::execute(
            "UPDATE notification_queue SET status = ?, attempts = ?, last_error = ?, sent_at = IF(? = 'sent', NOW(), sent_at) WHERE id = ?",
            [$status === 'retry' ? 'pending' : $status, $attempts, $error === null ? null : mb_substr($error, 0, 255, 'UTF-8'), $status, (int) $row['id']]
        );
        return $status;
    }

    // Modul mobile_app koperasi (dicache satu larian)
    private function moduleActive(array $row)
    {
        $key = (int) $row['vendor_id'] . '#' . (int) $row['cooperative_id'];
        if (!array_key_exists($key, $this->modules)) {
            $this->modules[$key] = ModuleAccess::has($row['vendor_id'], $row['cooperative_id'], 'mobile_app');
        }
        return $this->modules[$key];
    }
}
