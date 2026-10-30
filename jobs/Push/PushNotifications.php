<?php
// Masukkan push apps ke notification_queue (channel 'push' — dihantar oleh cron/send-push.php · SendPushJob).
// Hanya bila koperasi melanggan modul mobile_app dan ibu bapa ada peranti push berdaftar. Satu baris setiap peranti;
// recipient = token FCM, message = JSON { title, body, data } dalam bahasa akaun ibu bapa. Tidak pernah hantar terus.
class PushNotifications
{
    const BODY_MAX = 150;

    // Pesanan sedia diambil (UpdateOrderStatusJob — kali pertama `ready`)
    public static function orderReady(array $cooperative, array $order)
    {
        $children = array_column(CustomerOrders::groups(CustomerOrders::items($order)), 'child_name');
        return self::queueForOrder($cooperative, $order, 'order_ready', [
            'child_name' => implode(', ', array_filter($children)),
        ]);
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

    // Berita terbit → ibu bapa yang ada anak aktif di koperasi itu. Pulangkan bilangan peranti.
    // Dipanggil dalam transaksi: UPDATE … WHERE push_sent_at IS NULL memastikan sekali sahaja (seperti NewsPosts::queueEmail)
    public static function news(array $post)
    {
        if ($post['push_sent_at'] !== null || $post['state'] !== 'published') {
            return 0;
        }
        $vendorId = (int) $post['vendor_id'];
        $cooperativeId = (int) $post['cooperative_id'];
        $claimed = Database::execute(
            "UPDATE news_posts SET push_sent_at = NOW() WHERE id = ? AND vendor_id = ? AND push_sent_at IS NULL",
            [(int) $post['id'], $vendorId]
        );
        if ($claimed !== 1 || !ModuleAccess::has($vendorId, $cooperativeId, 'mobile_app')) {
            return 0;
        }
        $cooperative = Database::fetchOne("SELECT id, vendor_id, slug, school_name FROM cooperatives WHERE id = ? AND vendor_id = ?",
            [$cooperativeId, $vendorId]);
        $devices = Database::fetchAll(
            "SELECT DISTINCT d.push_token, c.language FROM push_devices d
               JOIN customers c ON c.id = d.customer_id
               JOIN children ch ON ch.customer_id = c.id
              WHERE ch.vendor_id = ? AND ch.cooperative_id = ? AND ch.is_active = 1 AND c.is_active = 1 AND c.deleted_at IS NULL",
            [$vendorId, $cooperativeId]
        );
        $body = trim(preg_replace('/\s+/u', ' ', (string) $post['body']));
        if (mb_strlen($body, 'UTF-8') > self::BODY_MAX) {
            $body = mb_substr($body, 0, self::BODY_MAX - 1, 'UTF-8') . '…';
        }
        foreach ($devices as $device) {
            $title = Lang::getFor($device['language'], 'push.news.title', ['school_name' => $cooperative['school_name'], 'title' => $post['title']]);
            self::insert($cooperative, null, 'news', $device['push_token'], $title, $body, ['news_id' => (string) $post['id']]);
        }
        Database::execute("UPDATE news_posts SET push_recipients = ? WHERE id = ? AND vendor_id = ?", [count($devices), (int) $post['id'], $vendorId]);
        return count($devices);
    }

    private static function queueForOrder(array $cooperative, array $order, $event, array $vars)
    {
        if ($order['customer_id'] === null || !ModuleAccess::has($cooperative['vendor_id'], $cooperative['id'], 'mobile_app')) {
            return 0;
        }
        $devices = self::devices($order['customer_id']);
        $vars += [
            'order_no'    => (string) $order['order_no'],
            'short_code'  => (string) $order['short_code'],
            'school_name' => $cooperative['school_name'],
        ];
        foreach ($devices as $device) {
            $title = Lang::getFor($device['language'], 'push.' . $event . '.title', $vars);
            $body = Lang::getFor($device['language'], 'push.' . $event . '.body', $vars);
            self::insert($cooperative, (int) $order['id'], $event, $device['push_token'], $title, $body, ['order_no' => (string) $order['order_no']]);
        }
        return count($devices);
    }

    // Peranti ibu bapa aktif (akaun belum dipadam)
    private static function devices($customerId)
    {
        return Database::fetchAll(
            "SELECT d.push_token, c.language FROM push_devices d JOIN customers c ON c.id = d.customer_id
              WHERE d.customer_id = ? AND c.is_active = 1 AND c.deleted_at IS NULL ORDER BY d.id",
            [(int) $customerId]
        );
    }

    // data FCM mesti rentetan; event & slug untuk apps membuka skrin yang betul
    private static function insert(array $cooperative, $orderId, $event, $pushToken, $title, $body, array $data)
    {
        $message = json_encode([
            'title' => $title,
            'body'  => $body,
            'data'  => ['event' => $event, 'slug' => (string) $cooperative['slug']] + $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return Database::insert(
            "INSERT INTO notification_queue (vendor_id, cooperative_id, order_id, channel, event, recipient, message) VALUES (?, ?, ?, 'push', ?, ?, ?)",
            [(int) $cooperative['vendor_id'], (int) $cooperative['id'], $orderId, $event, $pushToken, $message]
        );
    }
}
