<?php
// Satu-satunya cara mengubah stok (PELAN 18.8): kunci baris stock_levels (FOR UPDATE), kemas kini baki,
// tulis stock_movements dengan balance_after. WAJIB dipanggil di dalam transaksi DB — pemanggil yang commit.
//
//   Database::beginTransaction();
//   StockLedger::move($vendorId, $coopId, $productId, $variantId, 'restock', 10, $userId, null, null, 'Hadiah PIBG');
//   Database::commit();
class StockLedger
{
    const TYPES = ['sale', 'pos_sale', 'reserve', 'release', 'collect', 'refund', 'restock', 'adjust', 'stocktake', 'po_receive'];

    // Pulangkan baki baharu (qty_on_hand)
    public static function move($vendorId, $cooperativeId, $productId, $variantId, $type, $qtyChange, $userId, $refType = null, $refId = null, $note = '')
    {
        if (!Database::connection()->inTransaction()) {
            throw new RuntimeException('StockLedger::move mesti dalam transaksi DB.');
        }
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Jenis pergerakan stok tidak sah: ' . $type);
        }
        $level = self::lock($vendorId, $cooperativeId, $productId, $variantId);
        $balance = (int) $level['qty_on_hand'] + (int) $qtyChange;
        Database::execute(
            "UPDATE stock_levels SET qty_on_hand = ? WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ?",
            [$balance, (int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId]
        );
        Database::insert(
            "INSERT INTO stock_movements (vendor_id, cooperative_id, product_id, variant_id, type, qty_change, balance_after,
                                          ref_type, ref_id, vendor_user_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                (int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId, $type, (int) $qtyChange, $balance,
                $refType, $refId, $userId ?: null, mb_substr((string) $note, 0, 255, 'UTF-8') ?: null,
            ]
        );
        return $balance;
    }

    // Baris stok dikunci untuk transaksi ini; dicipta (baki 0) jika belum wujud
    public static function lock($vendorId, $cooperativeId, $productId, $variantId)
    {
        $args = [(int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId];
        $sql = "SELECT qty_on_hand, qty_reserved, min_stock FROM stock_levels
                 WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ? FOR UPDATE";
        $level = Database::fetchOne($sql, $args);
        if ($level === null) {
            Database::execute(
                "INSERT IGNORE INTO stock_levels (vendor_id, cooperative_id, product_id, variant_id, qty_on_hand, qty_reserved, min_stock)
                 VALUES (?, ?, ?, ?, 0, 0, ?)",
                array_merge($args, [CooperativeSettings::lowStockDefault($vendorId, $cooperativeId)])
            );
            $level = Database::fetchOne($sql, $args);
        }
        return $level;
    }
}
