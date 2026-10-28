<?php
// Pembantu refund (PELAN 18.10 "Refund", 10.2 "Konvensyen B11"). Setiap pertanyaan menapis vendor_id (sesi) + cooperative_id
// (disahkan VendorAuth::requireCooperative). Pesanan HANYA melalui VendorOrders::require (store & POS).
//   Boleh direfund = bayaran diterima (payments paid/refunded) − refund selesai − refund gateway yang masih pending.
//   Baki unit boleh dipulang = qty − cancelled − returned − unit dalam refund pending.
//   Unit belum diambil → dibatalkan (cancelled_qty, tahanan stok dilepas jika pesanan masih menahan stok);
//   unit sudah diambil → dipulang (returned_qty; restock → StockLedger 'refund' +qty, ref_type 'refund').
class Refunds
{
    const METHODS = ['cash', 'bank_transfer', 'gateway'];

    public static function find($vendorId, $cooperativeId, $refundId, $forUpdate = false)
    {
        return Database::fetchOne(
            "SELECT * FROM refunds WHERE id = ? AND vendor_id = ? AND cooperative_id = ?" . ($forUpdate ? ' FOR UPDATE' : ''),
            [(int) $refundId, (int) $vendorId, (int) $cooperativeId]
        );
    }

    // Jumlah wang diterima untuk pesanan (bil kedua / bayaran selepas batal turut dikira)
    public static function paidTotal(array $order)
    {
        return (int) Database::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE vendor_id = ? AND order_id = ? AND status IN ('paid', 'refunded')",
            [(int) $order['vendor_id'], (int) $order['id']]
        );
    }

    // Jumlah refund pesanan ikut status (lalai: selesai sahaja)
    public static function total(array $order, array $statuses = ['completed'])
    {
        $marks = implode(',', array_fill(0, count($statuses), '?'));
        return (int) Database::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE vendor_id = ? AND order_id = ? AND status IN ($marks)",
            array_merge([(int) $order['vendor_id'], (int) $order['id']], $statuses)
        );
    }

    public static function refundable(array $order)
    {
        return max(0, self::paidTotal($order) - self::total($order, ['completed', 'pending']));
    }

    // [order_item_id => unit] dalam refund gateway yang belum selesai
    public static function pendingQty(array $order)
    {
        $rows = Database::fetchAll(
            "SELECT ri.order_item_id, SUM(ri.qty) AS qty FROM refund_items ri
               JOIN refunds r ON r.id = ri.refund_id AND r.vendor_id = ri.vendor_id
              WHERE r.vendor_id = ? AND r.order_id = ? AND r.status = 'pending' GROUP BY ri.order_item_id",
            [(int) $order['vendor_id'], (int) $order['id']]
        );
        return array_map('intval', array_column($rows, 'qty', 'order_item_id'));
    }

    public static function returnable(array $item, array $pending)
    {
        return max(0, (int) $item['qty'] - (int) $item['cancelled_qty'] - (int) $item['returned_qty'] - ($pending[$item['id']] ?? 0));
    }

    // Bayaran terkini yang diterima (dirujuk refunds.payment_id; refund gateway dihantar ke bil ini)
    public static function latestPayment(array $order)
    {
        return Database::fetchOne(
            "SELECT * FROM payments WHERE vendor_id = ? AND order_id = ? AND status IN ('paid', 'refunded') ORDER BY id DESC LIMIT 1",
            [(int) $order['vendor_id'], (int) $order['id']]
        );
    }

    // Gateway yang boleh refund automatik bayaran ini: baris cooperative_gateways untuk gateway bil itu (aktif atau tidak),
    // driver dibenarkan owner (payment_gateways.supports_refund) DAN driver melaksanakan refund(). Pulangkan [baris, driver] atau null
    public static function gatewayFor(array $order, $payment)
    {
        if ($payment === null || $payment['method'] !== 'online' || (string) $payment['gateway_ref'] === '') {
            return null;
        }
        $row = CooperativeGateways::find($order['vendor_id'], $order['cooperative_id'], (string) $payment['gateway_code']);
        if ($row === null || !(int) $row['supports_refund'] || !GatewayFactory::supports($row['gateway_code'])) {
            return null;
        }
        $driver = CooperativeGateways::driver($row);
        return $driver->supportsRefund() ? [$row, $driver] : null;
    }

    // Nama gateway bayaran (untuk paparan), atau null
    public static function gatewayName($payment)
    {
        if ($payment === null || $payment['method'] !== 'online') {
            return null;
        }
        $name = Database::fetchValue("SELECT name FROM payment_gateways WHERE code = ?", [(string) $payment['gateway_code']]);
        return $name === null ? null : (string) $name;
    }

    // RF-{no. pesanan}-{n}: pesanan sudah dikunci (FOR UPDATE) oleh pemanggil → n unik tanpa mengunci baris koperasi
    public static function nextNumber(array $order)
    {
        $count = (int) Database::fetchValue("SELECT COUNT(*) FROM refunds WHERE vendor_id = ? AND order_id = ?",
            [(int) $order['vendor_id'], (int) $order['id']]);
        return 'RF-' . $order['order_no'] . '-' . ($count + 1);
    }

    // Kesan refund pada pesanan & stok (DALAM transaksi; pesanan dikunci). $lines = [order_item_id => unit].
    // Pulangkan status baharu pesanan (atau status asal jika tidak berubah)
    public static function apply(array $order, array $refund, array $lines, $userId, $language)
    {
        $vendorId = (int) $order['vendor_id'];
        $cooperativeId = (int) $order['cooperative_id'];
        $holding = $order['channel'] === 'online' && in_array($order['status'], CustomerOrders::HOLDING_STATUSES, true);
        $items = array_column(CustomerOrders::items($order), null, 'id');
        $release = [];
        $restock = [];

        foreach ($lines as $itemId => $qty) {
            $item = $items[$itemId];
            $cancel = min($qty, VendorOrders::remaining($item));
            $returned = $qty - $cancel;
            Database::execute(
                "UPDATE order_items SET cancelled_qty = cancelled_qty + ?, returned_qty = returned_qty + ? WHERE id = ? AND vendor_id = ?",
                [$cancel, $returned, (int) $itemId, $vendorId]
            );
            Database::execute("UPDATE refund_items SET cancelled_qty = ? WHERE vendor_id = ? AND refund_id = ? AND order_item_id = ?",
                [$cancel, $vendorId, (int) $refund['id'], (int) $itemId]);
            $items[$itemId]['cancelled_qty'] = (int) $item['cancelled_qty'] + $cancel;
            $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
            if ($cancel > 0 && $holding) {
                $release[$key] = ($release[$key] ?? 0) + $cancel;
            }
            if ($returned > 0 && (int) $refund['restock'] === 1) {
                $restock[$key] = ($restock[$key] ?? 0) + $returned;
            }
        }

        $status = self::nextStatus($order, $items);
        if ($status === 'refunded') {
            // Refund penuh: unit yang belum diambil tidak akan diserah lagi → dibatalkan & tahanan dilepas
            foreach ($items as $item) {
                $left = VendorOrders::remaining($item);
                if ($left > 0) {
                    Database::execute("UPDATE order_items SET cancelled_qty = cancelled_qty + ? WHERE id = ? AND vendor_id = ?",
                        [$left, (int) $item['id'], $vendorId]);
                    $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
                    if ($holding) {
                        $release[$key] = ($release[$key] ?? 0) + $left;
                    }
                }
            }
        }

        self::moveStock($order, $refund, $release, $restock, $userId);
        self::updateStatus($order, $status, $refund, $userId, $language);
        return $status;
    }

    // refunded (wang dipulang sepenuhnya) · collected (tiada baki, ada yang diambil) · cancelled (tiada baki, tiada yang diambil)
    private static function nextStatus(array $order, array $items)
    {
        if (self::total($order) >= self::paidTotal($order)) {
            return 'refunded';
        }
        if (!in_array($order['status'], CustomerOrders::HOLDING_STATUSES, true)) {
            return $order['status'];
        }
        $left = 0;
        $collected = 0;
        foreach ($items as $item) {
            $left += VendorOrders::remaining($item);
            $collected += (int) $item['collected_qty'];
        }
        if ($left > 0) {
            return $order['status'];
        }
        return $collected > 0 ? 'collected' : 'cancelled';
    }

    // Tertib kunci baris stok ikut (produk, varian) menaik — sama seperti CheckoutJob
    private static function moveStock(array $order, array $refund, array $release, array $restock, $userId)
    {
        CustomerOrders::sortStockKeys($release);
        CustomerOrders::sortStockKeys($restock);
        foreach ($release as $key => $qty) {
            list($productId, $variantId) = explode('#', $key);
            StockLedger::release($order['vendor_id'], $order['cooperative_id'], $productId, $variantId, $qty, (int) $order['id'], $refund['refund_no']);
        }
        foreach ($restock as $key => $qty) {
            list($productId, $variantId) = explode('#', $key);
            StockLedger::move($order['vendor_id'], $order['cooperative_id'], $productId, $variantId, 'refund', $qty, $userId,
                'refund', (int) $refund['id'], $refund['refund_no']);
        }
    }

    private static function updateStatus(array $order, $status, array $refund, $userId, $language)
    {
        $vendorId = (int) $order['vendor_id'];
        $note = Lang::getFor($language, 'refund.log', ['no' => $refund['refund_no'], 'amount' => Money::format($refund['amount']),
            'reason' => $refund['reason']]);
        if ($status === $order['status']) {
            CustomerOrders::logStatus($order, $status, 'vendor_user', $userId, $note);
            return;
        }
        $stamp = ['collected' => ', collected_at = COALESCE(collected_at, NOW())', 'cancelled' => ', cancelled_at = NOW()'][$status] ?? '';
        Database::execute("UPDATE orders SET status = ?, expires_at = NULL$stamp WHERE id = ? AND vendor_id = ?",
            [$status, (int) $order['id'], $vendorId]);
        CustomerOrders::logStatus($order, $status, 'vendor_user', $userId, $note);
        if ($status === 'refunded') {
            Database::execute("UPDATE payments SET status = 'refunded' WHERE vendor_id = ? AND order_id = ? AND status = 'paid'",
                [$vendorId, (int) $order['id']]);
        }
    }

    // Refund (PELAN 18.10) — baris refunds + no./kod pesanan + nama pelanggan + nama kakitangan
    public static function shape(array $row)
    {
        return [
            'id'            => (int) $row['id'],
            'refund_no'     => $row['refund_no'],
            'order_id'      => (int) $row['order_id'],
            'order_no'      => $row['order_no'],
            'short_code'    => $row['short_code'],
            'channel'       => $row['channel'],
            'customer_name' => (string) $row['customer_name'],
            'amount'        => (int) $row['amount'],
            'method'        => $row['method'],
            'status'        => $row['status'],
            'reason'        => $row['reason'],
            'restock'       => (bool) $row['restock'],
            'item_count'    => (int) $row['item_count'],
            'user_name'     => (string) $row['user_name'],
            'pos_shift_id'  => $row['pos_shift_id'] === null ? null : (int) $row['pos_shift_id'],
            'created_at'    => IsoDate::format($row['created_at']),
        ];
    }

    // SELECT untuk shape(): alias r = refunds, o = orders
    const SELECT_SQL = "SELECT r.*, o.order_no, o.short_code, o.channel, o.customer_name, vu.full_name AS user_name,
               (SELECT COALESCE(SUM(ri.qty), 0) FROM refund_items ri WHERE ri.vendor_id = r.vendor_id AND ri.refund_id = r.id) AS item_count
          FROM refunds r
          JOIN orders o ON o.id = r.order_id AND o.vendor_id = r.vendor_id AND o.cooperative_id = r.cooperative_id
          LEFT JOIN vendor_users vu ON vu.id = r.vendor_user_id AND vu.vendor_id = r.vendor_id";
}
