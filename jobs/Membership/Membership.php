<?php
// Pembantu modul keahlian koperasi (membership — W4): ahli, baki syer, bentuk API. Setiap pertanyaan menapis vendor_id + cooperative_id.
// Amaun dalam sen. Baki syer = buy + dividend (dilabur semula) − withdraw.
class Membership
{
    const TYPES = ['student', 'teacher', 'staff', 'parent', 'other'];
    const STATUSES = ['active', 'inactive'];
    const TX_TYPES = ['buy', 'withdraw'];
    const BALANCE_SQL = "COALESCE(SUM(CASE WHEN t.type = 'withdraw' THEN -t.amount ELSE t.amount END), 0)";

    // Baris ahli milik vendor + koperasi, atau null
    public static function find($vendorId, $cooperativeId, $memberId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM membership_members WHERE id = ? AND vendor_id = ? AND cooperative_id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $memberId, (int) $vendorId, (int) $cooperativeId]
        );
    }

    // Baki syer ahli (sehingga tarikh $asOf 'YYYY-MM-DD' jika diberi)
    public static function balance($vendorId, $memberId, $asOf = null)
    {
        $sql = "SELECT " . self::BALANCE_SQL . " FROM membership_share_transactions t WHERE t.vendor_id = ? AND t.member_id = ?";
        $args = [(int) $vendorId, (int) $memberId];
        if ($asOf !== null) {
            $sql .= " AND t.transaction_date <= ?";
            $args[] = $asOf;
        }
        return (int) Database::fetchValue($sql, $args);
    }

    // No. ahli seterusnya A0001, A0002… Pemanggil memegang kunci baris koperasi (FOR UPDATE) dalam transaksi
    public static function nextNumber($vendorId, $cooperativeId)
    {
        $last = Database::fetchValue(
            "SELECT MAX(CAST(SUBSTRING(member_no, 2) AS UNSIGNED)) FROM membership_members
              WHERE vendor_id = ? AND cooperative_id = ? AND member_no REGEXP '^A[0-9]+$'",
            [(int) $vendorId, (int) $cooperativeId]
        );
        return 'A' . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function shape(array $row, $balance = null)
    {
        return [
            'id'          => (int) $row['id'],
            'member_no'   => $row['member_no'],
            'full_name'   => $row['full_name'],
            'member_type' => $row['member_type'],
            'class_name'  => (string) $row['class_name'],
            'phone'       => (string) $row['phone'],
            'email'       => (string) $row['email'],
            'joined_on'   => $row['joined_on'],
            'status'      => $row['status'],
            'note'        => (string) $row['note'],
            'share_balance' => $balance === null ? (int) ($row['share_balance'] ?? 0) : (int) $balance,
        ];
    }

    public static function shapeTransaction(array $row)
    {
        return [
            'id'               => (int) $row['id'],
            'type'             => $row['type'],
            'amount'           => (int) $row['amount'],
            'transaction_date' => $row['transaction_date'],
            'note'             => (string) $row['note'],
            'staff_name'       => (string) ($row['staff_name'] ?? ''),
            'created_at'       => IsoDate::format($row['created_at']),
        ];
    }

    public static function shapeDividend(array $row)
    {
        return [
            'id'           => (int) $row['id'],
            'year'         => (int) $row['year'],
            'rate'         => (float) $row['rate'],
            'total_shares' => (int) $row['total_shares'],
            'total_amount' => (int) $row['total_amount'],
            'member_count' => (int) $row['member_count'],
            'payout'       => $row['payout'],
            'status'       => $row['status'],
            'paid_at'      => IsoDate::format($row['paid_at']),
            'created_at'   => IsoDate::format($row['created_at']),
        ];
    }

    // Baris dividen milik vendor + koperasi, atau null
    public static function findDividend($vendorId, $cooperativeId, $dividendId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM membership_dividends WHERE id = ? AND vendor_id = ? AND cooperative_id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $dividendId, (int) $vendorId, (int) $cooperativeId]
        );
    }

    // Kunci baris koperasi (siri no. ahli / dividen tahun sama tidak berlumba)
    public static function lockCooperative($vendorId, $cooperativeId)
    {
        Database::fetchValue("SELECT id FROM cooperatives WHERE id = ? AND vendor_id = ? FOR UPDATE", [(int) $cooperativeId, (int) $vendorId]);
    }
}
