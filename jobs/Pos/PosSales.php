<?php
// Pembantu jualan POS (PELAN 18.11): pesanan `channel = 'pos'` (customer_id NULL, order_items.source 'pos', tiada anak).
// Setiap pertanyaan menapis vendor_id + cooperative_id. Jualan dibayar = terus `collected` dan stok ditolak
// (StockLedger::move 'pos_sale'); QR online menahan stok (reserve) sehingga PaymentSettlement mengesahkan bayaran.
class PosSales
{
    public static function find($vendorId, $cooperativeId, $orderId, $forUpdate = false)
    {
        $order = VendorOrders::find($vendorId, $cooperativeId, $orderId, $forUpdate);
        return $order !== null && $order['channel'] === 'pos' ? $order : null;
    }

    public static function require($vendorId, $cooperativeId, $orderId, $forUpdate = false)
    {
        $order = self::find($vendorId, $cooperativeId, $orderId, $forUpdate);
        if ($order === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        return $order;
    }

    // Bayaran diterima (tunai di kaunter, atau QR disahkan gateway). WAJIB dalam transaksi, pesanan sudah dikunci.
    // Online: tahanan dilepas dahulu. Stok ditolak atas nama juruwang jualan; log status + log aktiviti pos_sale.
    // $actorType = vendor_user (tunai) | system (webhook / semakan semula)
    public static function complete(array $order, $actorType, $actorId)
    {
        $vendorId = (int) $order['vendor_id'];
        $cooperativeId = (int) $order['cooperative_id'];
        $cashierId = $order['cashier_id'] === null ? null : (int) $order['cashier_id'];
        $totals = [];
        foreach (CustomerOrders::items($order) as $item) {
            $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
            $totals[$key] = ($totals[$key] ?? 0) + (int) $item['qty'];
        }
        CustomerOrders::sortStockKeys($totals);
        foreach ($totals as $key => $qty) {
            list($productId, $variantId) = explode('#', $key);
            if ($order['payment_method'] === 'online') {
                StockLedger::release($vendorId, $cooperativeId, $productId, $variantId, $qty, (int) $order['id'], $order['order_no']);
            }
            StockLedger::move($vendorId, $cooperativeId, $productId, $variantId, 'pos_sale', -$qty, $cashierId, 'order', (int) $order['id'], $order['order_no']);
        }
        Database::execute("UPDATE order_items SET collected_qty = qty WHERE vendor_id = ? AND order_id = ?", [$vendorId, (int) $order['id']]);
        Database::execute(
            "UPDATE orders SET status = 'collected', paid_at = COALESCE(paid_at, NOW()), collected_at = NOW(), expires_at = NULL
              WHERE id = ? AND vendor_id = ?",
            [(int) $order['id'], $vendorId]
        );
        $language = (string) Database::fetchValue("SELECT default_language FROM cooperatives WHERE id = ? AND vendor_id = ?", [$cooperativeId, $vendorId]);
        CustomerOrders::logStatus($order, 'collected', $actorType, $actorId, Lang::getFor($language, 'pos.log_sale'));
        (new WriteActivityLogJob($vendorId, $cooperativeId, $cashierId, 'pos_sale', 'order', (int) $order['id'],
            $order['order_no'] . ' · ' . Money::format($order['total'])))->run();
    }

    // Tanya gateway untuk bil pending jualan ini (DI LUAR transaksi) — PaymentSettlement memutuskan. Gateway tidak dapat
    // dihubungi → status kekal; webhook / cron akan menyelesaikan
    public static function recheckPayment(array $order)
    {
        $payments = Database::fetchAll(
            "SELECT * FROM payments WHERE vendor_id = ? AND cooperative_id = ? AND order_id = ? AND method = 'online' AND status = 'pending'
              ORDER BY id DESC LIMIT 3",
            [(int) $order['vendor_id'], (int) $order['cooperative_id'], (int) $order['id']]
        );
        foreach ($payments as $payment) {
            $gateway = CooperativeGateways::find($order['vendor_id'], $order['cooperative_id'], $payment['gateway_code']);
            if ($gateway === null) {
                continue;
            }
            try {
                if (PaymentSettlement::recheck($payment, CooperativeGateways::driver($gateway)) === 'paid') {
                    return;
                }
            } catch (GatewayException $error) {
                error_log('[KoperasiKu] semak bayaran POS ' . $payment['gateway_ref'] . ': ' . $error->getMessage());
            }
        }
    }

    // PosReceipt (PELAN 18.11)
    public static function receipt(array $order)
    {
        $vendorId = (int) $order['vendor_id'];
        $coop = Database::fetchOne(
            "SELECT school_name, address, phone, logo_path FROM cooperatives WHERE id = ? AND vendor_id = ?",
            [(int) $order['cooperative_id'], $vendorId]
        );
        $promotion = $order['promotion_id'] === null ? null : Database::fetchOne(
            "SELECT name, code FROM promotions WHERE id = ? AND vendor_id = ?", [(int) $order['promotion_id'], $vendorId]
        );
        $payment = $order['payment_method'] !== 'online' ? null : Database::fetchOne(
            "SELECT p.gateway_ref, pg.name FROM payments p LEFT JOIN payment_gateways pg ON pg.code = p.gateway_code
              WHERE p.vendor_id = ? AND p.order_id = ? AND p.method = 'online'
              ORDER BY p.status = 'paid' DESC, p.id DESC LIMIT 1",
            [$vendorId, (int) $order['id']]
        );
        $cashier = $order['cashier_id'] === null ? '' : (string) Database::fetchValue(
            "SELECT full_name FROM vendor_users WHERE id = ? AND vendor_id = ?", [(int) $order['cashier_id'], $vendorId]
        );
        $items = array_map(function ($item) {
            return [
                'product_name'  => $item['product_name'],
                'variant_label' => $item['variant_label'],
                'sku'           => (string) $item['sku'],
                'qty'           => (int) $item['qty'],
                'unit_price'    => (int) $item['unit_price'],
                'line_total'    => (int) $item['line_total'],
                'returned_qty'  => (int) $item['returned_qty'],
            ];
        }, CustomerOrders::items($order));
        return [
            'order_id'           => (int) $order['id'],
            'order_no'           => $order['order_no'],
            'short_code'         => $order['short_code'],
            'status'             => $order['status'],
            'payment_status'     => CustomerOrders::paymentStatus($order),
            'created_at'         => IsoDate::format($order['created_at']),
            'paid_at'            => IsoDate::format($order['paid_at']),
            'cashier_name'       => $cashier,
            'customer_name'      => (string) $order['customer_name'],
            'shift_id'           => $order['pos_shift_id'] === null ? null : (int) $order['pos_shift_id'],
            'items'              => $items,
            'subtotal'           => (int) $order['subtotal'],
            'promotion_name'     => $promotion ? $promotion['name'] : null,
            'promotion_code'     => $promotion ? $promotion['code'] : null,
            'promotion_discount' => (int) $order['promotion_discount'],
            'manual_discount'    => (int) $order['manual_discount'],
            'discount'           => (int) $order['discount'],
            'tax_rate'           => 0 + $order['tax_rate'],
            'tax'                => (int) $order['tax'],
            'total'              => (int) $order['total'],
            'refunded_amount'    => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE vendor_id = ? AND order_id = ? AND status = 'completed'",
                [$vendorId, (int) $order['id']]
            ),
            'payment_method'     => $order['payment_method'],
            'gateway_name'       => $payment ? $payment['name'] : null,
            'gateway_ref'        => $payment ? $payment['gateway_ref'] : null,
            'amount_received'    => $order['amount_received'] === null ? null : (int) $order['amount_received'],
            'change'             => $order['change_given'] === null ? null : (int) $order['change_given'],
            'school_name'        => $coop['school_name'],
            'address'            => (string) $coop['address'],
            'phone'              => (string) $coop['phone'],
            'logo_url'           => Uploader::url($coop['logo_path']),
        ];
    }
}
