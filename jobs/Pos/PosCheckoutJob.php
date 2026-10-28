<?php
// Jualan POS (vendor/pos/checkout — PELAN 18.11). SATU transaksi DB dengan tertib kunci tetap:
//   syif juruwang (FOR UPDATE) → baris stok ikut (produk, varian) menaik (PosCalculateCartJob) → promosi → baris koperasi (no. resit).
// Pelayan mengira semula harga, stok, had beli, promosi & cukai — nilai dari pelayar diabaikan.
//   Tunai : bayar serta-merta → payments (cash, received_by juruwang) → `collected`, stok ditolak (pos_sale).
//   Online: `pending_payment` + stok ditahan (reserve) + expires_at → bil QR gateway dicipta SELEPAS commit (PosOnlinePaymentJob);
//           gateway gagal → jualan dibatalkan serta-merta (tahanan dilepas). `paid` hanya oleh PaymentSettlement.
class PosCheckoutJob
{
    const MAX_CASH = 100000000;

    private $vendorId;
    private $cooperative;
    private $cashier;
    private $input;

    public function __construct($vendorId, array $cooperative, array $cashier, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->cashier = $cashier;
        $this->input = $input;
    }

    public function run()
    {
        $method = (string) ($this->input['payment_method'] ?? '');
        if (!in_array($method, ['cash', 'online'], true)) {
            throw new ValidationException(t('error.invalid'));
        }
        $customerName = trim((string) ($this->input['customer_name'] ?? ''));
        if (mb_strlen($customerName, 'UTF-8') > 100) {
            throw new ValidationException(t('error.check_form'), ['customer_name' => t('error.max_length', ['max' => 100])]);
        }
        $gateway = null;
        if ($method === 'online') {
            $gateway = CooperativeGateways::active($this->vendorId, $this->cooperative['id']);
            if ($gateway === null) {
                throw new ValidationException(t('pos.online_unavailable'));
            }
        }

        Database::beginTransaction();
        try {
            $order = $this->place($method, $customerName);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }

        $payment = null;
        if ($method === 'online') {
            try {
                $payment = (new PosOnlinePaymentJob($this->cooperative, $order, $gateway))->run();
            } catch (ValidationException $error) {
                (new CancelPosPaymentJob($this->vendorId, $this->cooperative, $this->cashier, $order['id'], false))->run();
                throw $error;
            }
        }
        $fresh = PosSales::require($this->vendorId, $this->cooperative['id'], $order['id']);
        return ['receipt' => PosSales::receipt($fresh), 'payment' => $payment];
    }

    private function place($method, $customerName)
    {
        $coop = $this->cooperative;
        $shift = PosShifts::openFor($this->vendorId, $coop['id'], $this->cashier['id'], true);
        if ($shift === null || (int) $shift['id'] !== (int) ($this->input['shift_id'] ?? 0)) {
            throw new ValidationException(t('pos.error.no_shift'));
        }
        $priced = (new PosCalculateCartJob($coop, $this->input))->run();
        $totals = $priced['totals'];

        $received = null;
        if ($method === 'cash') {
            $received = filter_var($this->input['amount_received'] ?? null, FILTER_VALIDATE_INT);
            if ($received === false || $received < $totals['total'] || $received > self::MAX_CASH) {
                throw new ValidationException(t('handover.cash_not_enough'), ['amount_received' => t('handover.cash_not_enough')]);
            }
        }
        $this->usePromotion($priced['promotion']);

        $order = $this->insertOrder($method, $customerName, $shift, $priced, $received);
        $this->insertItems($order, $priced['lines']);
        CustomerOrders::logStatus(['vendor_id' => $this->vendorId, 'id' => $order['id'], 'status' => null], 'pending_payment',
            'vendor_user', (int) $this->cashier['id'], Lang::getFor($coop['default_language'], 'pos.log_counter'));

        if ($method === 'cash') {
            Database::insert(
                "INSERT INTO payments (vendor_id, cooperative_id, order_id, method, amount, status, verified_at, received_by)
                 VALUES (?, ?, ?, 'cash', ?, 'paid', NOW(), ?)",
                [$this->vendorId, (int) $coop['id'], $order['id'], $totals['total'], (int) $this->cashier['id']]
            );
            Database::execute("UPDATE orders SET paid_at = NOW() WHERE id = ? AND vendor_id = ?", [$order['id'], $this->vendorId]);
            PosSales::complete($order, 'vendor_user', (int) $this->cashier['id']);
        } else {
            foreach ($priced['lines'] as $line) {
                StockLedger::reserve($this->vendorId, $coop['id'], $line['product_id'], $line['variant_id'], $line['qty'], $order['id'], $order['order_no']);
            }
        }
        return $order;
    }

    // Kunci promosi & semak had guna sekali lagi (sama seperti CheckoutJob). Dilepas semula jika QR dibatal / luput (releaseHolds)
    private function usePromotion($promotion)
    {
        if ($promotion === null) {
            return;
        }
        $row = Database::fetchOne(
            "SELECT used_count, max_uses FROM promotions WHERE id = ? AND vendor_id = ? AND cooperative_id = ? FOR UPDATE",
            [(int) $promotion['id'], $this->vendorId, (int) $this->cooperative['id']]
        );
        if ($row === null || ($row['max_uses'] !== null && (int) $row['used_count'] >= (int) $row['max_uses'])) {
            throw new ValidationException(t('pos.coupon_invalid'), ['coupon_code' => t('pos.coupon_invalid')]);
        }
        Database::execute("UPDATE promotions SET used_count = used_count + 1 WHERE id = ?", [(int) $promotion['id']]);
    }

    private function insertOrder($method, $customerName, array $shift, array $priced, $received)
    {
        $coop = $this->cooperative;
        $totals = $priced['totals'];
        $numbers = OrderNumbers::nextPos($coop);
        $order = [
            'vendor_id'          => $this->vendorId,
            'cooperative_id'     => (int) $coop['id'],
            'customer_id'        => null,
            'order_no'           => $numbers['order_no'],
            'short_code'         => $numbers['short_code'],
            'channel'            => 'pos',
            'payment_method'     => $method,
            'status'             => 'pending_payment',
            'subtotal'           => $totals['subtotal'],
            'discount'           => $totals['discount'],
            'promotion_discount' => $totals['promotion_discount'],
            'manual_discount'    => $totals['manual_discount'],
            'tax_rate'           => $priced['tax_rate'],
            'tax'                => $totals['tax'],
            'total'              => $totals['total'],
            'promotion_id'       => $priced['promotion'] ? (int) $priced['promotion']['id'] : null,
            'pos_shift_id'       => (int) $shift['id'],
            'cashier_id'         => (int) $this->cashier['id'],
            'amount_received'    => $received,
            'change_given'       => $received === null ? null : $received - $totals['total'],
            'pickup_qr_token'    => $numbers['qr_token'],
            'customer_name'      => $customerName === '' ? null : $customerName,
            'expires_at'         => $method === 'online'
                ? date('Y-m-d H:i:s', time() + 60 * (int) Config::get('pos_payment_expiry_minutes', 15)) : null,
        ];
        $columns = array_keys($order);
        $order['id'] = Database::insert(
            "INSERT INTO orders (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")",
            array_values($order)
        );
        $order['paid_at'] = null;
        return $order;
    }

    // Snapshot nama, varian, SKU & harga (PELAN 16 perkara 6) — source 'pos', tiada anak
    private function insertItems(array $order, array $lines)
    {
        foreach ($lines as $line) {
            Database::insert(
                "INSERT INTO order_items (vendor_id, order_id, product_id, variant_id, product_name, variant_label, sku, unit_price, qty, line_total, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pos')",
                [
                    $this->vendorId, $order['id'], $line['product_id'], $line['variant_id'], mb_substr($line['product_name'], 0, 150, 'UTF-8'),
                    $line['variant_label'], $line['sku'], $line['unit_price'], $line['qty'], $line['line_total'],
                ]
            );
        }
    }
}
