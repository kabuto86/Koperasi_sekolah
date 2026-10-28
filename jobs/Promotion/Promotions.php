<?php
// Promosi koperasi untuk checkout store (PELAN 18.12 — sama seperti POS, pos-cart.js):
// satu promosi setiap pesanan; kupon (kod) ATAU diskaun automatik terbaik; diskaun tidak melebihi jumlah kecil.
// Cukai dikira selepas diskaun (CheckoutQuote). Setiap pertanyaan menapis vendor_id + cooperative_id dari baris koperasi.
class Promotions
{
    // Promosi yang sedang berjalan: aktif, dalam tarikh, belum mencapai had guna
    public static function active(array $cooperative)
    {
        return Database::fetchAll(
            "SELECT id, name, code, type, value, min_spend, max_uses, used_count FROM promotions
              WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 AND starts_on <= CURDATE() AND ends_on >= CURDATE()
                AND (max_uses IS NULL OR used_count < max_uses)
              ORDER BY id",
            [(int) $cooperative['vendor_id'], (int) $cooperative['id']]
        );
    }

    // Diskaun (sen) untuk jumlah kecil ini; 0 jika belanja minimum tidak dicapai
    public static function discount(array $promotion, $subtotal)
    {
        if ($subtotal <= 0 || $subtotal < (int) $promotion['min_spend']) {
            return 0;
        }
        $amount = $promotion['type'] === 'percent' ? (int) round($subtotal * (int) $promotion['value'] / 100) : (int) $promotion['value'];
        return min($amount, $subtotal);
    }

    // Pilih promosi: kod kupon (huruf besar) jika diberi, jika tidak automatik terbaik.
    // Pulangkan ['promotion' => baris|null, 'discount' => sen, 'error' => mesej|null]
    public static function choose(array $cooperative, $couponCode, $subtotal)
    {
        $active = self::active($cooperative);
        $code = strtoupper(trim((string) $couponCode));
        if ($code !== '') {
            foreach ($active as $promotion) {
                if ($promotion['code'] !== null && strtoupper($promotion['code']) === $code) {
                    if ($subtotal < (int) $promotion['min_spend']) {
                        return ['promotion' => null, 'discount' => 0,
                            'error' => t('pos.coupon_min_spend', ['amount' => Money::format($promotion['min_spend'])])];
                    }
                    return ['promotion' => $promotion, 'discount' => self::discount($promotion, $subtotal), 'error' => null];
                }
            }
            return ['promotion' => null, 'discount' => 0, 'error' => t('pos.coupon_invalid')];
        }
        $best = null;
        $bestAmount = 0;
        foreach ($active as $promotion) {
            $amount = self::discount($promotion, $subtotal);
            if (($promotion['code'] === null || $promotion['code'] === '') && $amount > $bestAmount) {
                $best = $promotion;
                $bestAmount = $amount;
            }
        }
        return ['promotion' => $best, 'discount' => $bestAmount, 'error' => null];
    }

    // Keadaan dikira pelayan (PELAN 18.10): inactive | used_up | scheduled | expired | active
    const STATE_SQL = "CASE WHEN is_active = 0 THEN 'inactive'
                            WHEN max_uses IS NOT NULL AND used_count >= max_uses THEN 'used_up'
                            WHEN starts_on > CURDATE() THEN 'scheduled'
                            WHEN ends_on < CURDATE() THEN 'expired'
                            ELSE 'active' END";

    // Baris promosi koperasi ini (panel vendor), atau null
    public static function find($vendorId, $cooperativeId, $id)
    {
        $row = Database::fetchOne(
            "SELECT *, " . self::STATE_SQL . " AS state FROM promotions WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
            [(int) $id, (int) $vendorId, (int) $cooperativeId]
        );
        return $row === null ? null : self::shape($row);
    }

    // Promotion (PELAN 18.10) — tanpa vendor_id
    public static function shape(array $row)
    {
        return [
            'id'             => (int) $row['id'],
            'cooperative_id' => (int) $row['cooperative_id'],
            'name'           => $row['name'],
            'code'           => $row['code'],
            'type'           => $row['type'],
            'value'          => (int) $row['value'],
            'min_spend'      => (int) $row['min_spend'],
            'starts_on'      => $row['starts_on'],
            'ends_on'        => $row['ends_on'],
            'max_uses'       => $row['max_uses'] === null ? null : (int) $row['max_uses'],
            'used_count'     => (int) $row['used_count'],
            'is_active'      => (bool) $row['is_active'],
            'state'          => $row['state'],
        ];
    }
}
