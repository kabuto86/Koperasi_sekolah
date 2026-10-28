<?php
// Syif & tunai laci POS (pos_shifts — PELAN 9 G, 18.11). Satu syif terbuka setiap juruwang setiap koperasi.
// Jangkaan tunai laci = tunai permulaan + jualan tunai POS syif ini + tunai pesanan store yang diterima juruwang
// yang sama di kaunter koperasi ini sepanjang syif (vendor/handover/receive-cash). Setiap pertanyaan menapis vendor_id.
class PosShifts
{
    public static function find($vendorId, $cooperativeId, $shiftId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM pos_shifts WHERE id = ? AND vendor_id = ? AND cooperative_id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $shiftId, (int) $vendorId, (int) $cooperativeId]
        );
    }

    public static function require($vendorId, $cooperativeId, $shiftId, $forUpdate = false)
    {
        $shift = self::find($vendorId, $cooperativeId, $shiftId, $forUpdate);
        if ($shift === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return $shift;
    }

    // Syif terbuka juruwang ini di koperasi ini, atau null
    public static function openFor($vendorId, $cooperativeId, $userId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM pos_shifts WHERE vendor_id = ? AND cooperative_id = ? AND vendor_user_id = ? AND status = 'open'
              ORDER BY id DESC LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $vendorId, (int) $cooperativeId, (int) $userId]
        );
    }

    // PosShift (PELAN 18.11)
    public static function summary(array $shift)
    {
        $vendorId = (int) $shift['vendor_id'];
        $args = [$vendorId, (int) $shift['cooperative_id'], (int) $shift['id']];
        $sales = Database::fetchAll(
            "SELECT payment_method, COUNT(*) AS n, SUM(total) AS total, SUM(discount) AS discount, SUM(tax) AS tax
               FROM orders WHERE vendor_id = ? AND cooperative_id = ? AND pos_shift_id = ? AND paid_at IS NOT NULL
              GROUP BY payment_method",
            $args
        );
        $by = ['cash' => ['n' => 0, 'total' => 0], 'online' => ['n' => 0, 'total' => 0]];
        $discount = 0;
        $tax = 0;
        foreach ($sales as $row) {
            $by[$row['payment_method']] = ['n' => (int) $row['n'], 'total' => (int) $row['total']];
            $discount += (int) $row['discount'];
            $tax += (int) $row['tax'];
        }
        $items = (int) Database::fetchValue(
            "SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id AND o.vendor_id = oi.vendor_id
              WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.pos_shift_id = ? AND o.paid_at IS NOT NULL",
            $args
        );
        $pending = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND cooperative_id = ? AND pos_shift_id = ? AND status = 'pending_payment'",
            $args
        );
        $orderCash = self::orderCash($shift);
        $expected = (int) $shift['opening_cash'] + $by['cash']['total'] + $orderCash['total'];
        $closed = $shift['status'] === 'closed';
        return [
            'id'               => (int) $shift['id'],
            'cooperative_id'   => (int) $shift['cooperative_id'],
            'user_id'          => (int) $shift['vendor_user_id'],
            'user_name'        => (string) Database::fetchValue("SELECT full_name FROM vendor_users WHERE id = ? AND vendor_id = ?",
                [(int) $shift['vendor_user_id'], $vendorId]),
            'status'           => $shift['status'],
            'opened_at'        => IsoDate::format($shift['opened_at']),
            'closed_at'        => IsoDate::format($shift['closed_at']),
            'opening_cash'     => (int) $shift['opening_cash'],
            'sale_count'       => $by['cash']['n'] + $by['online']['n'],
            'item_count'       => $items,
            'cash_count'       => $by['cash']['n'],
            'cash_sales'       => $by['cash']['total'],
            'online_count'     => $by['online']['n'],
            'online_sales'     => $by['online']['total'],
            'order_cash_count' => $orderCash['n'],
            'order_cash_total' => $orderCash['total'],
            'discount_total'   => $discount,
            'tax_total'        => $tax,
            'pending_count'    => $pending,
            'expected_cash'    => $closed ? (int) $shift['expected_cash'] : $expected,
            'closing_cash'     => $shift['closing_cash'] === null ? null : (int) $shift['closing_cash'],
            'variance'         => $shift['variance'] === null ? null : (int) $shift['variance'],
            'note'             => (string) $shift['note'],
        ];
    }

    // Tunai pesanan store (channel online) yang diterima juruwang syif ini di koperasi ini dalam tempoh syif
    private static function orderCash(array $shift)
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS n, COALESCE(SUM(p.amount), 0) AS total
               FROM payments p JOIN orders o ON o.id = p.order_id AND o.vendor_id = p.vendor_id
              WHERE p.vendor_id = ? AND p.cooperative_id = ? AND p.method = 'cash' AND p.status IN ('paid', 'refunded')
                AND o.channel = 'online' AND p.received_by = ? AND p.created_at >= ? AND p.created_at <= COALESCE(?, NOW())",
            [(int) $shift['vendor_id'], (int) $shift['cooperative_id'], (int) $shift['vendor_user_id'], $shift['opened_at'], $shift['closed_at']]
        );
        return ['n' => (int) $row['n'], 'total' => (int) $row['total']];
    }
}
