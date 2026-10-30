<?php
// Pembantu modul pembukaan sekolah (school_opening — PELAN 18.13): musim pre-order & slot ambil.
// Musim disimpan DATETIME (opens_at 00:00:00, closes_at 23:59:59) supaya store (CheckoutQuote, GetStoreSchoolJob) membanding dengan NOW().
// Tempahan slot (booked) HANYA diubah oleh checkout / batal pesanan — panel tidak pernah menulis booked.
class PreorderSeasons
{
    const STATE_SQL = "(CASE WHEN s.opens_at > NOW() THEN 'upcoming' WHEN s.closes_at < NOW() THEN 'closed' ELSE 'open' END)";

    public static function find($vendorId, $cooperativeId, $id)
    {
        return Database::fetchOne(
            "SELECT s.*, " . self::STATE_SQL . " AS state,
                    (SELECT COUNT(*) FROM pickup_slots p WHERE p.vendor_id = s.vendor_id AND p.preorder_season_id = s.id) AS slot_count,
                    (SELECT COALESCE(SUM(p.capacity), 0) FROM pickup_slots p WHERE p.vendor_id = s.vendor_id AND p.preorder_season_id = s.id) AS capacity,
                    (SELECT COALESCE(SUM(p.booked), 0) FROM pickup_slots p WHERE p.vendor_id = s.vendor_id AND p.preorder_season_id = s.id) AS booked
               FROM preorder_seasons s WHERE s.id = ? AND s.vendor_id = ? AND s.cooperative_id = ?",
            [(int) $id, (int) $vendorId, (int) $cooperativeId]
        );
    }

    public static function all($vendorId, $cooperativeId)
    {
        $ids = Database::fetchAll(
            "SELECT id FROM preorder_seasons WHERE vendor_id = ? AND cooperative_id = ? ORDER BY school_year DESC",
            [(int) $vendorId, (int) $cooperativeId]
        );
        return array_map(function ($row) use ($vendorId, $cooperativeId) {
            return self::shape(self::find($vendorId, $cooperativeId, $row['id']));
        }, $ids);
    }

    public static function shape(array $row)
    {
        return [
            'id'               => (int) $row['id'],
            'school_year'      => (int) $row['school_year'],
            'opens_at'         => substr($row['opens_at'], 0, 10),
            'closes_at'        => substr($row['closes_at'], 0, 10),
            'pickup_starts_on' => $row['pickup_starts_on'],
            'note'             => (string) $row['note'],
            'state'            => $row['state'],
            'slot_count'       => (int) $row['slot_count'],
            'capacity'         => (int) $row['capacity'],
            'booked'           => (int) $row['booked'],
        ];
    }

    // Slot musim (bentuk Slot sama dengan store — CheckoutQuote::slotShape). $forUpdate = kunci baris (dalam transaksi)
    public static function slots($vendorId, $seasonId, $forUpdate = false)
    {
        return Database::fetchAll(
            "SELECT id, slot_date, time_start, time_end, grade_level_code, capacity, booked FROM pickup_slots
              WHERE vendor_id = ? AND preorder_season_id = ? ORDER BY slot_date, time_start, id" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $vendorId, (int) $seasonId]
        );
    }

    // Slot yang pernah dipilih dalam pesanan (termasuk dibatal) tidak boleh dipadam — orders.pickup_slot_id merujuknya
    public static function slotUsed($vendorId, array $slotIds)
    {
        if (!$slotIds) {
            return false;
        }
        $marks = implode(',', array_fill(0, count($slotIds), '?'));
        return (bool) Database::fetchValue(
            "SELECT 1 FROM orders WHERE vendor_id = ? AND pickup_slot_id IN ($marks) LIMIT 1",
            array_merge([(int) $vendorId], array_map('intval', $slotIds))
        );
    }
}
