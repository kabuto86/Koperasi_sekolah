<?php
// Sebut harga checkout store (PELAN 18.12 store/checkout/quote): CartQuote (Carts) + promosi (satu setiap pesanan,
// cukai selepas diskaun), kaedah bayaran, kod 4 digit, slot ambil (modul school_opening) dan tutup tempahan (modul calendar).
// Dikongsi GetCheckoutQuoteJob (paparan) dan CheckoutJob (dikira semula dalam transaksi) — harga pelayar diabaikan.
class CheckoutQuote
{
    // $customer = baris customers (id, full_name, email_verified_at). Pulangkan:
    // ['data' => CartQuote penuh, 'lines' => baris dalaman Carts, 'promotion' => baris|null]
    public static function build(array $customer, array $cooperative, $couponCode)
    {
        $result = Carts::quoteWithLines($customer['id'], $cooperative);
        $data = $result['quote'];

        $choice = Promotions::choose($cooperative, $couponCode, $data['subtotal']);
        $taxable = $data['subtotal'] - $choice['discount'];
        $tax = (int) round($taxable * $data['tax_rate'] / 100);
        $total = $taxable + $tax;

        $settings = CooperativeSettings::all($cooperative['vendor_id'], $cooperative['id']);
        $methods = explode(',', (string) ($settings['pickup_methods'] ?? 'A,B,C,D'));
        $codeAbove = (int) ($settings['pickup_code_required_above'] ?? 0);
        $gateway = CooperativeGateways::active($cooperative['vendor_id'], $cooperative['id']);
        $slots = self::slots($cooperative, $data['groups']);

        $data['discount'] = $choice['discount'];
        $data['promotion_name'] = $choice['promotion'] ? $choice['promotion']['name'] : null;
        $data['promotion_code'] = $choice['promotion'] ? $choice['promotion']['code'] : null;
        $data['coupon_code'] = strtoupper(trim((string) $couponCode));
        $data['coupon_error'] = $choice['error'];
        $data['tax'] = $tax;
        $data['total'] = $total;
        $data['payment_methods'] = self::paymentMethods($cooperative, $gateway);
        $data['gateway_name'] = $gateway ? $gateway['name'] : null;
        $data['requires_code'] = in_array('E', $methods, true) || ($codeAbove > 0 && $total > $codeAbove);
        $data['pickup_slots'] = $slots;
        $data['slot_required'] = count($slots) > 0;
        $data['orders_blocked'] = self::ordersBlocked($cooperative);
        $data['customer_name'] = $customer['full_name'];
        $data['email_verified'] = $customer['email_verified_at'] !== null;
        return ['data' => $data, 'lines' => $result['lines'], 'promotion' => $choice['promotion']];
    }

    // Tunai jika koperasi terima; online hanya bila gateway koperasi aktif, dibenarkan owner & driver wujud (CooperativeGateways::active)
    private static function paymentMethods(array $cooperative, $gateway)
    {
        $methods = [];
        if ((int) $cooperative['accept_cash'] === 1) {
            $methods[] = 'cash';
        }
        if ((int) $cooperative['accept_online'] === 1 && $gateway !== null) {
            $methods[] = 'online';
        }
        return $methods;
    }

    // Slot ambil musim pre-order yang sedang dibuka, untuk darjah anak-anak dalam troli (NULL = semua darjah).
    // Slot yang sudah lepas tidak ditawarkan
    public static function slots(array $cooperative, array $groups)
    {
        if (!StoreSchool::hasModule($cooperative, 'school_opening')) {
            return [];
        }
        $vendorId = (int) $cooperative['vendor_id'];
        $seasonId = Database::fetchValue(
            "SELECT id FROM preorder_seasons WHERE vendor_id = ? AND cooperative_id = ? AND opens_at <= NOW() AND closes_at >= NOW()
              ORDER BY school_year LIMIT 1",
            [$vendorId, (int) $cooperative['id']]
        );
        if ($seasonId === null) {
            return [];
        }
        $grades = array_values(array_unique(array_column($groups, 'grade_level_code')));
        $rows = Database::fetchAll(
            "SELECT id, slot_date, time_start, time_end, grade_level_code, capacity, booked FROM pickup_slots
              WHERE vendor_id = ? AND cooperative_id = ? AND preorder_season_id = ? AND slot_date >= CURDATE()
              ORDER BY slot_date, time_start, id",
            [$vendorId, (int) $cooperative['id'], (int) $seasonId]
        );
        $slots = [];
        foreach ($rows as $row) {
            if ($row['grade_level_code'] === null || in_array($row['grade_level_code'], $grades, true)) {
                $slots[] = self::slotShape($row);
            }
        }
        return $slots;
    }

    public static function slotShape(array $row)
    {
        return [
            'id'               => (int) $row['id'],
            'slot_date'        => $row['slot_date'],
            'time_start'       => substr($row['time_start'], 0, 5),
            'time_end'         => substr($row['time_end'], 0, 5),
            'grade_level_code' => $row['grade_level_code'],
            'capacity'         => (int) $row['capacity'],
            'booked'           => (int) $row['booked'],
            'remaining'        => max(0, (int) $row['capacity'] - (int) $row['booked']),
        ];
    }

    // Modul calendar: hari ini koperasi tutup (blocks_orders) → { title, reopens_on }, jika tidak null
    private static function ordersBlocked(array $cooperative)
    {
        if (!StoreSchool::hasModule($cooperative, 'calendar')) {
            return null;
        }
        $event = Database::fetchOne(
            "SELECT title, ends_on FROM calendar_events
              WHERE vendor_id = ? AND cooperative_id = ? AND blocks_orders = 1 AND starts_on <= CURDATE() AND ends_on >= CURDATE()
              ORDER BY ends_on DESC LIMIT 1",
            [(int) $cooperative['vendor_id'], (int) $cooperative['id']]
        );
        if ($event === null) {
            return null;
        }
        return ['title' => $event['title'], 'reopens_on' => date('Y-m-d', strtotime($event['ends_on'] . ' +1 day'))];
    }
}
