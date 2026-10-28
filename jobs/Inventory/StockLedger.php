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

    // Tahan stok untuk pesanan (PELAN 16 perkara 4): qty_reserved += $qty, qty_on_hand kekal.
    // Pergerakan 'reserve' / 'release' direkod dengan qty_change 0 (baki fizikal tidak berubah) + rujukan pesanan.
    // Pemanggil menyemak baki (qty_on_hand − qty_reserved) dahulu — lihat CheckoutJob
    public static function reserve($vendorId, $cooperativeId, $productId, $variantId, $qty, $refId, $note = '')
    {
        return self::changeReserved($vendorId, $cooperativeId, $productId, $variantId, 'reserve', (int) $qty, $refId, $note);
    }

    // Lepas tahanan (pesanan dibatal / luput) — tidak pernah di bawah 0
    public static function release($vendorId, $cooperativeId, $productId, $variantId, $qty, $refId, $note = '')
    {
        return self::changeReserved($vendorId, $cooperativeId, $productId, $variantId, 'release', -(int) $qty, $refId, $note);
    }

    // Barang pesanan diserah (B9): qty_on_hand −= qty DAN qty_reserved −= qty (tahanan bertukar menjadi jualan).
    // Pergerakan 'collect' qty_change −qty + rujukan pesanan + kakitangan yang menyerah. Pulangkan baki baharu
    public static function collect($vendorId, $cooperativeId, $productId, $variantId, $qty, $orderId, $userId, $note = '')
    {
        if (!Database::connection()->inTransaction()) {
            throw new RuntimeException('StockLedger::collect mesti dalam transaksi DB.');
        }
        $level = self::lock($vendorId, $cooperativeId, $productId, $variantId);
        $balance = (int) $level['qty_on_hand'] - (int) $qty;
        $reserved = max(0, (int) $level['qty_reserved'] - (int) $qty);
        Database::execute(
            "UPDATE stock_levels SET qty_on_hand = ?, qty_reserved = ? WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ?",
            [$balance, $reserved, (int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId]
        );
        Database::insert(
            "INSERT INTO stock_movements (vendor_id, cooperative_id, product_id, variant_id, type, qty_change, balance_after,
                                          ref_type, ref_id, vendor_user_id, note)
             VALUES (?, ?, ?, ?, 'collect', ?, ?, 'order', ?, ?, ?)",
            [
                (int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId, -(int) $qty, $balance,
                (int) $orderId, $userId ?: null, mb_substr((string) $note, 0, 255, 'UTF-8') ?: null,
            ]
        );
        return $balance;
    }

    private static function changeReserved($vendorId, $cooperativeId, $productId, $variantId, $type, $change, $refId, $note)
    {
        if (!Database::connection()->inTransaction()) {
            throw new RuntimeException('StockLedger::' . $type . ' mesti dalam transaksi DB.');
        }
        $level = self::lock($vendorId, $cooperativeId, $productId, $variantId);
        $reserved = max(0, (int) $level['qty_reserved'] + $change);
        Database::execute(
            "UPDATE stock_levels SET qty_reserved = ? WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id = ?",
            [$reserved, (int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId]
        );
        Database::insert(
            "INSERT INTO stock_movements (vendor_id, cooperative_id, product_id, variant_id, type, qty_change, balance_after,
                                          ref_type, ref_id, vendor_user_id, note)
             VALUES (?, ?, ?, ?, ?, 0, ?, 'order', ?, NULL, ?)",
            [
                (int) $vendorId, (int) $cooperativeId, (int) $productId, (int) $variantId, $type, (int) $level['qty_on_hand'],
                (int) $refId, mb_substr((string) $note, 0, 255, 'UTF-8') ?: null,
            ]
        );
        return $reserved;
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
