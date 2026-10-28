<?php
// Rekod refund pesanan (vendor/refunds/create — PELAN 18.10, 10.2 "Konvensyen B11").
// Input: order_id, items [{ item_id, qty }] (boleh kosong = refund amaun sahaja), restock, amount (sen), method, reason.
// Pelayan mengira semula baki boleh direfund & unit boleh dipulang — nilai dari pelayar tidak dipercayai.
//   cash / bank_transfer → SATU transaksi: refunds (completed) + refund_items + Refunds::apply (stok & status).
//   gateway → (1) transaksi: refunds pending + refund_items (baki & unit "ditempah"); (2) driver->refund() DI LUAR transaksi;
//             (3) transaksi: berjaya → completed + gateway_ref + apply; gagal → failed (tiada kesan stok / status).
// Refund tunai direkod pada syif POS terbuka pengguna kaunter (PosAuth::counterUserId) → ditolak dari jangkaan laci.
class RefundOrderJob
{
    const MAX_ITEMS = 200;

    private $vendorId;
    private $cooperative;
    private $userId;
    private $counterUserId;
    private $input;

    public function __construct($vendorId, array $cooperative, $userId, $counterUserId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->counterUserId = (int) $counterUserId;
        $this->input = $input;
    }

    public function run()
    {
        $method = (string) ($this->input['method'] ?? '');
        Database::beginTransaction();
        try {
            $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], (int) ($this->input['order_id'] ?? 0), true);
            list($amount, $lines, $gateway) = $this->validate($order, $method);
            $refund = $this->insert($order, $amount, $lines, $method);
            if ($method !== 'gateway') {
                Refunds::apply($order, $refund, $lines, $this->userId, $this->cooperative['default_language']);
            }
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        if ($method === 'gateway') {
            $refund = $this->sendToGateway($refund, $gateway);
        }
        $this->log($refund, $refund['status'] === 'completed' ? 'refund_create' : 'refund_failed');
        if ($refund['status'] !== 'completed') {
            throw new ValidationException(t('refund.gateway_failed'), ['method' => t('refund.gateway_failed')]);
        }
        $row = Database::fetchOne(Refunds::SELECT_SQL . " WHERE r.id = ? AND r.vendor_id = ? AND r.cooperative_id = ?",
            [(int) $refund['id'], $this->vendorId, (int) $this->cooperative['id']]);
        return Refunds::shape($row);
    }

    // Pulangkan [amaun, [order_item_id => unit], [baris gateway, driver] | null]
    private function validate(array $order, $method)
    {
        $refundable = Refunds::refundable($order);
        if (Refunds::paidTotal($order) === 0) {
            throw new ValidationException(t('refund.order_not_paid'));
        }
        $v = new Validator($this->input);
        $v->required('amount')->integer('amount')
          ->required('method')->inList('method', Refunds::METHODS)
          ->required('reason')->maxLength('reason', 255);
        $amount = filter_var($this->input['amount'] ?? null, FILTER_VALIDATE_INT);
        if ($amount !== false && !($amount > 0 && $amount <= $refundable)) {
            $v->addError('amount', t('refund.amount_range', ['max' => Money::format($refundable)]));
        }
        $gateway = null;
        if ($method === 'gateway') {
            $payment = Refunds::latestPayment($order);
            $gateway = Refunds::gatewayFor($order, $payment);
            $paymentLeft = $payment === null ? 0 : (int) $payment['amount'] - $this->paymentRefunded($payment);
            if ($gateway === null) {
                $v->addError('method', t('refund.gateway_unsupported'));
            } elseif ($amount !== false && $amount > $paymentLeft) {
                $v->addError('amount', t('refund.amount_range', ['max' => Money::format(max(0, $paymentLeft))]));
            }
        }
        $lines = $this->lines($order, $v);
        if ($v->fails()) {
            $errors = $v->errors();
            $only = isset($errors['items']) && count($errors) === 1;
            throw new ValidationException($only ? $errors['items'] : t('error.check_form'), $errors);
        }
        return [(int) $amount, $lines, $gateway];
    }

    // Refund selesai / pending yang dirujuk ke bayaran ini
    private function paymentRefunded(array $payment)
    {
        return (int) Database::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE vendor_id = ? AND payment_id = ? AND status IN ('completed', 'pending')",
            [$this->vendorId, (int) $payment['id']]
        );
    }

    // Baris barang: item mesti milik pesanan ini; baris berganda dicantum; jumlah ≤ baki boleh dipulang
    private function lines(array $order, Validator $v)
    {
        $raw = $this->input['items'] ?? [];
        if (!is_array($raw) || count($raw) > self::MAX_ITEMS) {
            $v->addError('items', t('error.invalid'));
            return [];
        }
        $items = array_column(CustomerOrders::items($order), null, 'id');
        $pending = Refunds::pendingQty($order);
        $lines = [];
        foreach ($raw as $line) {
            $itemId = is_array($line) ? (int) ($line['item_id'] ?? 0) : 0;
            $qty = is_array($line) ? filter_var($line['qty'] ?? null, FILTER_VALIDATE_INT) : false;
            if (!isset($items[$itemId]) || $qty === false || $qty < 0) {
                $v->addError('items', t('refund.qty_too_many'));
                return [];
            }
            if ($qty > 0) {
                $lines[$itemId] = ($lines[$itemId] ?? 0) + $qty;
            }
        }
        foreach ($lines as $itemId => $qty) {
            if ($qty > Refunds::returnable($items[$itemId], $pending)) {
                $v->addError('items', t('refund.qty_too_many'));
            }
        }
        ksort($lines);
        return $lines;
    }

    private function insert(array $order, $amount, array $lines, $method)
    {
        $payment = Refunds::latestPayment($order);
        $shift = $method === 'cash' ? PosShifts::openFor($this->vendorId, $this->cooperative['id'], $this->counterUserId) : null;
        $refund = [
            'refund_no' => Refunds::nextNumber($order),
            'amount'    => $amount,
            'reason'    => trim((string) $this->input['reason']),
            'restock'   => empty($this->input['restock']) || $this->input['restock'] === 'false' ? 0 : 1,
            'status'    => $method === 'gateway' ? 'pending' : 'completed',
        ];
        $refund['id'] = Database::insert(
            "INSERT INTO refunds (vendor_id, cooperative_id, refund_no, order_id, payment_id, amount, reason, method, restock, status,
                                  vendor_user_id, pos_shift_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$this->vendorId, (int) $this->cooperative['id'], $refund['refund_no'], (int) $order['id'], $payment === null ? null : (int) $payment['id'],
                $amount, $refund['reason'], $method, $refund['restock'], $refund['status'], $this->userId, $shift === null ? null : (int) $shift['id']]
        );
        foreach ($lines as $itemId => $qty) {
            Database::insert("INSERT INTO refund_items (vendor_id, refund_id, order_item_id, qty) VALUES (?, ?, ?, ?)",
                [$this->vendorId, $refund['id'], $itemId, $qty]);
        }
        return $refund;
    }

    // Langkah 2 & 3 refund gateway. Pulangkan $refund dengan status completed | failed
    private function sendToGateway(array $refund, array $gateway)
    {
        list($row, $driver) = $gateway;
        $payment = Database::fetchOne("SELECT p.* FROM payments p JOIN refunds r ON r.payment_id = p.id AND r.vendor_id = p.vendor_id
             WHERE r.id = ? AND r.vendor_id = ?", [(int) $refund['id'], $this->vendorId]);
        try {
            $result = $driver->refund($payment['gateway_ref'], $refund['amount'], $refund['refund_no']);
        } catch (GatewayException $error) {
            error_log('refund gateway ' . $row['gateway_code'] . ' ' . $refund['refund_no'] . ': ' . $error->getMessage());
            Database::execute("UPDATE refunds SET status = 'failed' WHERE id = ? AND vendor_id = ? AND status = 'pending'",
                [(int) $refund['id'], $this->vendorId]);
            $refund['status'] = 'failed';
            return $refund;
        }

        Database::beginTransaction();
        try {
            $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], (int) $payment['order_id'], true);
            Database::execute("UPDATE refunds SET status = 'completed', gateway_ref = ? WHERE id = ? AND vendor_id = ?",
                [mb_substr((string) $result['refund_ref'], 0, 100, 'UTF-8'), (int) $refund['id'], $this->vendorId]);
            $lines = array_map('intval', array_column(Database::fetchAll(
                "SELECT order_item_id, qty FROM refund_items WHERE vendor_id = ? AND refund_id = ? ORDER BY order_item_id",
                [$this->vendorId, (int) $refund['id']]
            ), 'qty', 'order_item_id'));
            Refunds::apply($order, $refund, $lines, $this->userId, $this->cooperative['default_language']);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        $refund['status'] = 'completed';
        return $refund;
    }

    private function log(array $refund, $action)
    {
        (new WriteActivityLogJob($this->vendorId, (int) $this->cooperative['id'], $this->userId, $action, 'refund', (int) $refund['id'],
            $refund['refund_no'] . ' · ' . Money::format($refund['amount'])))->run();
    }
}
