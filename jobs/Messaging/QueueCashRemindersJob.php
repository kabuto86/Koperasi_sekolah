<?php
// Cron (cron/expire-cash-orders.php): peringatan WhatsApp/SMS untuk pesanan tunai store yang luput dalam 24 jam.
// Sekali sahaja setiap pesanan (notification_queue.order_id + event cash_reminder). Syarat modul/tetapan dalam MessageNotifications.
class QueueCashRemindersJob
{
    const HOURS_BEFORE = 24;

    private $limit;

    public function __construct($limit = 200)
    {
        $this->limit = max(1, (int) $limit);
    }

    // Pulangkan bilangan mesej yang dimasukkan ke baris gilir
    public function run()
    {
        $orders = Database::fetchAll(
            "SELECT o.* FROM orders o
              WHERE o.status = 'unpaid_cash' AND o.channel = 'online' AND o.customer_id IS NOT NULL
                AND o.expires_at > NOW() AND o.expires_at <= DATE_ADD(NOW(), INTERVAL " . self::HOURS_BEFORE . " HOUR)
                AND NOT EXISTS (SELECT 1 FROM notification_queue n WHERE n.vendor_id = o.vendor_id AND n.order_id = o.id AND n.event = 'cash_reminder')
              ORDER BY o.expires_at LIMIT " . $this->limit
        );
        $count = 0;
        $cooperatives = [];
        foreach ($orders as $order) {
            $key = (int) $order['cooperative_id'];
            if (!isset($cooperatives[$key])) {
                $cooperatives[$key] = Database::fetchOne(
                    "SELECT id, vendor_id, school_name FROM cooperatives WHERE id = ? AND vendor_id = ?",
                    [$key, (int) $order['vendor_id']]
                );
            }
            $count += MessageNotifications::cashReminder($cooperatives[$key], $order) === null ? 0 : 1;
        }
        return $count;
    }
}
