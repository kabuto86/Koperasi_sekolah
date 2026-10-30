<?php
// Log mesej koperasi (vendor/messaging/log): tapis status, terbaru dahulu. Nombor penerima dipapar bertopeng.
class ListMessageLogJob
{
    const STATUSES = ['pending', 'sent', 'failed'];

    private $vendorId;
    private $cooperativeId;
    private $params;

    public function __construct($vendorId, $cooperativeId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->params = $params;
    }

    public function run()
    {
        list($page, $perPage, $offset) = Pagination::from($this->params);
        $where = "vendor_id = ? AND cooperative_id = ? AND channel IN ('whatsapp', 'sms')";
        $args = [$this->vendorId, $this->cooperativeId];
        $status = (string) ($this->params['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where .= ' AND status = ?';
            $args[] = $status;
        }
        $total = Database::fetchValue("SELECT COUNT(*) FROM notification_queue WHERE $where", $args);
        $rows = Database::fetchAll(
            "SELECT id, channel, recipient, event, message, status, attempts, created_at, sent_at FROM notification_queue
              WHERE $where ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        $items = array_map(function ($row) {
            return [
                'id'         => (int) $row['id'],
                'channel'    => $row['channel'],
                'recipient'  => MessagingSettings::mask($row['recipient']),
                'event'      => $row['event'],
                'message'    => $row['message'],
                'status'     => $row['status'],
                'attempts'   => (int) $row['attempts'],
                'created_at' => IsoDate::format($row['created_at']),
                'sent_at'    => IsoDate::format($row['sent_at']),
            ];
        }, $rows);
        return Pagination::result($items, $total, $page, $perPage);
    }
}
