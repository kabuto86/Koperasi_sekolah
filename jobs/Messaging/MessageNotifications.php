<?php
// Masukkan mesej WhatsApp/SMS ke notification_queue (dihantar oleh cron/send-messages.php — SendQueuedMessagesJob).
// Hanya bila koperasi melanggan modul whatsapp_sms, tetapan dihidupkan dan peristiwa itu ditanda. Tidak pernah hantar terus —
// kaunter & serah barang tidak menunggu pembekal.
class MessageNotifications
{
    // Pesanan sedia diambil (UpdateOrderStatusJob — kali pertama `ready`)
    public static function orderReady(array $cooperative, array $order)
    {
        $children = array_column(CustomerOrders::groups(CustomerOrders::items($order)), 'child_name');
        return self::queueForOrder($cooperative, $order, 'order_ready', ['child_name' => implode(', ', array_filter($children))]);
    }

    // Barang diserah (HandoverOrderJob — setiap serahan, termasuk separa)
    public static function orderCollected(array $cooperative, array $order, $pickedBy, array $childNames)
    {
        return self::queueForOrder($cooperative, $order, 'order_collected', [
            'child_name' => implode(', ', $childNames),
            'picked_by'  => (string) $pickedBy,
            'time'       => date('d/m H:i'),
        ]);
    }

    // Pesanan tunai hampir luput (QueueCashRemindersJob)
    public static function cashReminder(array $cooperative, array $order)
    {
        $children = array_column(CustomerOrders::groups(CustomerOrders::items($order)), 'child_name');
        return self::queueForOrder($cooperative, $order, 'cash_reminder', [
            'child_name' => implode(', ', array_filter($children)),
            'expires_on' => date('d/m/Y H:i', strtotime($order['expires_at'])),
        ]);
    }

    // Mesej ujian dari panel (peristiwa tidak perlu ditanda, tetapan mesti dihidupkan)
    public static function test(array $cooperative, $phone)
    {
        $settings = MessagingSettings::find($cooperative['vendor_id'], $cooperative['id']);
        if ($settings === null || (int) $settings['is_enabled'] !== 1) {
            return null;
        }
        return self::insert($cooperative, $settings['channel'], 'test', $phone,
            t('messaging.test_message', ['school' => $cooperative['school_name']]), null);
    }

    private static function queueForOrder(array $cooperative, array $order, $event, array $vars)
    {
        $phone = MessagingSettings::phone($order['customer_phone'] ?? '');
        if ($order['customer_id'] === null || $phone === null || !ModuleAccess::has($cooperative['vendor_id'], $cooperative['id'], 'whatsapp_sms')) {
            return null;
        }
        $settings = MessagingSettings::find($cooperative['vendor_id'], $cooperative['id']);
        $shape = MessagingSettings::publicShape($settings);
        if (!$shape['is_enabled'] || empty($shape['events'][$event])) {
            return null;
        }
        $message = MessagingSettings::render($shape['templates'][$event], $vars + [
            'parent_name' => (string) $order['customer_name'],
            'short_code'  => (string) $order['short_code'],
            'school_name' => $cooperative['school_name'],
        ]);
        return self::insert($cooperative, $shape['channel'], $event, $phone, $message, (int) $order['id']);
    }

    private static function insert(array $cooperative, $channel, $event, $phone, $message, $orderId)
    {
        return Database::insert(
            "INSERT INTO notification_queue (vendor_id, cooperative_id, order_id, channel, event, recipient, message) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [(int) $cooperative['vendor_id'], (int) $cooperative['id'], $orderId, $channel, $event, $phone, $message]
        );
    }
}
