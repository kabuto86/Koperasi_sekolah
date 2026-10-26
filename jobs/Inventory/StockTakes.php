<?php
// Pembantu stock take (PELAN 18.8): cari dalam koperasi vendor sesi + bentuk ringkasan.
class StockTakes
{
    public static function find($vendorId, $cooperativeId, $id, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT id, cooperative_id, status FROM stock_takes WHERE vendor_id = ? AND cooperative_id = ? AND id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $vendorId, (int) $cooperativeId, (int) $id]
        );
    }

    // $where/$args atas alias t
    public static function summaries($vendorId, $cooperativeId, $where = '', array $args = [], $tail = '')
    {
        $rows = Database::fetchAll(
            "SELECT t.id, t.cooperative_id, t.status, u.full_name AS started_by, t.started_at, t.completed_at,
                    (SELECT COUNT(*) FROM stock_take_items i WHERE i.vendor_id = t.vendor_id AND i.stock_take_id = t.id) AS item_count,
                    (SELECT COUNT(*) FROM stock_take_items i WHERE i.vendor_id = t.vendor_id AND i.stock_take_id = t.id
                        AND i.qty_counted IS NOT NULL) AS counted_count,
                    (SELECT COALESCE(SUM(i.qty_counted - i.qty_system), 0) FROM stock_take_items i
                      WHERE i.vendor_id = t.vendor_id AND i.stock_take_id = t.id AND i.qty_counted IS NOT NULL) AS variance_total
               FROM stock_takes t
               LEFT JOIN vendor_users u ON u.vendor_id = t.vendor_id AND u.id = t.started_by
              WHERE t.vendor_id = ? AND t.cooperative_id = ? $where
              ORDER BY t.id DESC $tail",
            array_merge([(int) $vendorId, (int) $cooperativeId], $args)
        );
        return array_map(function ($row) {
            return [
                'id'             => (int) $row['id'],
                'cooperative_id' => (int) $row['cooperative_id'],
                'status'         => $row['status'],
                'started_by'     => (string) $row['started_by'],
                'started_at'     => IsoDate::format($row['started_at']),
                'completed_at'   => IsoDate::format($row['completed_at']),
                'item_count'     => (int) $row['item_count'],
                'counted_count'  => (int) $row['counted_count'],
                'variance_total' => (int) $row['variance_total'],
            ];
        }, $rows);
    }

    public static function summary($vendorId, $cooperativeId, $id)
    {
        $rows = self::summaries($vendorId, $cooperativeId, 'AND t.id = ?', [(int) $id]);
        return $rows[0] ?? null;
    }
}
