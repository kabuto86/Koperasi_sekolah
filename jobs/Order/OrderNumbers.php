<?php
// No. pesanan, kod pendek & token QR (PELAN 16 perkara 5) — dijana DALAM transaksi checkout, unik per koperasi.
//   order_no   TM-2610-0001  (singkatan sekolah + YYMM + turutan bulanan koperasi)
//   short_code A123          (huruf + 3 digit; jika sesak, huruf + 4 / 5 digit) — mudah disebut murid di kaunter
//   qr_token   32 aksara hex rawak (QR pada e-mel, halaman pesanan & slip)
//   POS (B10): order_no POS-261028-001 (turutan harian koperasi) · short_code sama seperti pesanan store
class OrderNumbers
{
    // Huruf tanpa I & O (tidak keliru dengan 1 & 0)
    const LETTERS = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    // Kunci baris koperasi (FOR UPDATE) supaya dua checkout serentak tidak mendapat nombor sama
    public static function next(array $cooperative)
    {
        if (!Database::connection()->inTransaction()) {
            throw new RuntimeException('OrderNumbers::next mesti dalam transaksi DB.');
        }
        $cooperativeId = (int) $cooperative['id'];
        Database::fetchOne("SELECT id FROM cooperatives WHERE id = ? AND vendor_id = ? FOR UPDATE", [$cooperativeId, (int) $cooperative['vendor_id']]);

        $prefix = self::prefix($cooperative['school_name']) . '-' . date('ym') . '-';
        $last = Database::fetchValue(
            "SELECT MAX(CAST(SUBSTRING(order_no, ?) AS UNSIGNED)) FROM orders WHERE cooperative_id = ? AND order_no LIKE ?",
            [strlen($prefix) + 1, $cooperativeId, $prefix . '%']
        );
        return [
            'order_no'   => $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT),
            'short_code' => self::shortCode($cooperativeId),
            'qr_token'   => bin2hex(random_bytes(16)),
        ];
    }

    // No. resit jualan POS (PELAN 18.11) — juga mengunci baris koperasi
    public static function nextPos(array $cooperative)
    {
        if (!Database::connection()->inTransaction()) {
            throw new RuntimeException('OrderNumbers::nextPos mesti dalam transaksi DB.');
        }
        $cooperativeId = (int) $cooperative['id'];
        Database::fetchOne("SELECT id FROM cooperatives WHERE id = ? AND vendor_id = ? FOR UPDATE", [$cooperativeId, (int) $cooperative['vendor_id']]);

        $prefix = 'POS-' . date('ymd') . '-';
        $last = Database::fetchValue(
            "SELECT MAX(CAST(SUBSTRING(order_no, ?) AS UNSIGNED)) FROM orders WHERE cooperative_id = ? AND order_no LIKE ?",
            [strlen($prefix) + 1, $cooperativeId, $prefix . '%']
        );
        return [
            'order_no'   => $prefix . str_pad((string) ((int) $last + 1), 3, '0', STR_PAD_LEFT),
            'short_code' => self::shortCode($cooperativeId),
            'qr_token'   => bin2hex(random_bytes(16)),
        ];
    }

    // Huruf pertama perkataan selepas jenis sekolah: "SK Taman Melati" → TM, "SJKC Chung Hwa" → CH
    public static function prefix($schoolName)
    {
        $words = preg_split('/\s+/', trim((string) $schoolName));
        array_shift($words);
        $letters = '';
        foreach ($words as $word) {
            if (preg_match('/^[A-Za-z]/', $word)) {
                $letters .= strtoupper($word[0]);
            }
        }
        return $letters !== '' ? substr($letters, 0, 3) : 'KP';
    }

    // Huruf + 3 digit; jika sesak 4, kemudian 5 digit (B10: jualan POS berkongsi ruang kod yang sama)
    private static function shortCode($cooperativeId)
    {
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $digits = 3 + intdiv($attempt, 20);
            $code = self::LETTERS[random_int(0, strlen(self::LETTERS) - 1)]
                . str_pad((string) random_int(0, 10 ** $digits - 1), $digits, '0', STR_PAD_LEFT);
            $used = Database::fetchValue("SELECT 1 FROM orders WHERE cooperative_id = ? AND short_code = ?", [$cooperativeId, $code]);
            if ($used === null) {
                return $code;
            }
        }
        throw new RuntimeException('Kod pendek pesanan tidak dapat dijana.');
    }
}
