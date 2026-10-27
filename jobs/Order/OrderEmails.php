<?php
// E-mel pesanan baharu (PELAN 6, 16 perkara 12): dimasukkan ke email_queue dalam transaksi checkout, dihantar oleh
// cron/send-emails.php. Amaran vendor → semua alert_emails koperasi (bahasa koperasi); resit → ibu bapa (bahasanya)
// dengan no. pendek, QR (jadual HTML) dan senarai barang ikut anak.
class OrderEmails
{
    const MAX_ALERT_EMAILS = 5;

    // $order = baris orders yang baru dicipta, $customer = baris LoadCustomerSessionJob
    public static function queueNewOrder(array $cooperative, array $order, array $customer)
    {
        $vendorId = (int) $cooperative['vendor_id'];
        $total = Money::format($order['total']);

        $vendorVars = [
            'order_no'      => $order['order_no'],
            'school_name'   => $cooperative['school_name'],
            'customer_name' => $order['customer_name'],
            'total'         => $total,
            'order_url'     => View::absoluteUrl('vendor/order.php?id=' . (int) $order['id']),
        ];
        foreach (self::alertEmails($cooperative) as $email) {
            (new QueueEmailJob($vendorId, $email, 'order_new_vendor', $cooperative['default_language'], $vendorVars))->run();
        }

        if ((string) $customer['email'] === '') {
            return;
        }
        $language = $customer['language'];
        $receiptVars = [
            'order_no'      => $order['order_no'],
            'customer_name' => $order['customer_name'],
            'school_name'   => $cooperative['school_name'],
            'total'         => $total,
            'pickup_code'   => $order['short_code'],
            'order_url'     => View::absoluteStoreUrl($cooperative['slug'], 'order-detail', ['id' => (int) $order['id']]),
        ];
        $html = [
            'items_table' => self::itemsTable(CustomerOrders::items($order), $language),
            'pickup_qr'   => QrCode::emailTable($order['pickup_qr_token']),
        ];
        (new QueueEmailJob($vendorId, $customer['email'], 'order_receipt', $language, $receiptVars, $html))->run();
    }

    // Senarai e-mel amaran koperasi (JSON dalam cooperative_settings), sah & unik, maksimum 5
    public static function alertEmails(array $cooperative)
    {
        $list = json_decode((string) CooperativeSettings::get($cooperative['vendor_id'], $cooperative['id'], 'alert_emails', '[]'), true);
        $emails = [];
        foreach (is_array($list) ? $list : [] as $email) {
            $email = strtolower(trim((string) $email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }
        return array_slice($emails, 0, self::MAX_ALERT_EMAILS);
    }

    // Jadual barang ikut anak (atribut HTML sahaja, tanpa CSS) — semua teks di-escape
    private static function itemsTable(array $items, $language)
    {
        $html = '<table cellpadding="4" cellspacing="0" border="0" width="100%">';
        foreach (CustomerOrders::groups($items) as $group) {
            $grade = Lang::getFor($language, 'grade.' . $group['grade_level_code']);
            $heading = trim($group['child_name'] . ' — ' . $grade . ' ' . $group['class_name']);
            $html .= '<tr><td colspan="2"><strong>' . e($heading) . '</strong></td></tr>';
            foreach ($group['items'] as $item) {
                $name = $item['product_name'] . ($item['variant_label'] ? ' — ' . $item['variant_label'] : '');
                $html .= '<tr><td>' . (int) $item['qty'] . ' × ' . e($name) . '</td><td align="right">'
                    . e(Money::format($item['line_total'])) . '</td></tr>';
            }
        }
        return $html . '</table>';
    }
}
