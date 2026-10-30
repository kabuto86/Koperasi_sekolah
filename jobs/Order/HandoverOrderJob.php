<?php
// Serah barang di kaunter (PELAN 7, 18.9 vendor/handover/collect). Syarat: status ready / partially_collected, sudah dibayar,
// kod 4 digit jika pesanan memerlukannya (VerifyPickupCodeJob — transaksi sendiri), kuantiti ≤ baki setiap item.
// Satu transaksi: kunci pesanan → order_pickups + order_pickup_items → collected_qty → StockLedger::collect (tolak stok
// sebenar & lepas tahanan) → status collected / partially_collected + log → e-mel "telah diambil oleh …" kepada ibu bapa.
class HandoverOrderJob
{
    const PICKED_BY = ['student', 'parent', 'proxy'];

    private $vendorId;
    private $cooperative;
    private $userId;
    private $input;

    public function __construct($vendorId, array $cooperative, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $orderId = (int) ($this->input['id'] ?? 0);
        $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], $orderId);
        $this->checkOrder($order);
        list($type, $name) = $this->collector();
        $lines = $this->requestedLines();
        if ($order['pickup_code_hash'] !== null) {
            (new VerifyPickupCodeJob($this->vendorId, $this->cooperative['id'], $orderId, $this->input['code'] ?? ''))->run();
        }

        Database::beginTransaction();
        try {
            // Disemak semula selepas dikunci — dua kaunter serentak tidak boleh menyerah baki yang sama
            $order = VendorOrders::require($this->vendorId, $this->cooperative['id'], $orderId, true);
            $this->checkOrder($order);
            $status = $this->collect($order, $lines, $type, $name);
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return [
            'status'          => $status,
            'parent_notified' => $order['customer_id'] !== null && (string) $order['customer_email'] !== '',
            'order'           => (new GetVendorOrderJob($this->vendorId, $this->cooperative['id'], $orderId))->run(),
        ];
    }

    private function checkOrder(array $order)
    {
        if ($order['channel'] !== 'online' || !in_array($order['status'], VendorOrders::READY_STATUSES, true)) {
            throw new ValidationException(t('handover.not_ready'));
        }
        if ($order['paid_at'] === null) {
            throw new ValidationException(t('handover.pay_first'));
        }
        if ($order['pickup_code_hash'] !== null && VendorOrders::isLocked($order)) {
            throw new ValidationException(t('handover.locked'), ['code' => t('handover.locked')]);
        }
    }

    private function collector()
    {
        $type = (string) ($this->input['picked_by_type'] ?? '');
        $name = trim((string) ($this->input['picked_by_name'] ?? ''));
        $errors = [];
        if (!in_array($type, self::PICKED_BY, true)) {
            $errors['picked_by_type'] = t('error.required');
        }
        if ($name === '') {
            $errors['picked_by_name'] = t('error.required');
        } elseif (mb_strlen($name, 'UTF-8') > 150) {
            $errors['picked_by_name'] = t('error.max_length', ['max' => 150]);
        }
        if ($errors) {
            throw new ValidationException(t('error.check_form'), $errors);
        }
        return [$type, $name];
    }

    // [order_item_id => qty] (qty > 0 sahaja; item berulang dijumlahkan)
    private function requestedLines()
    {
        $lines = [];
        foreach (is_array($this->input['items'] ?? null) ? $this->input['items'] : [] as $line) {
            $itemId = is_array($line) ? (int) ($line['item_id'] ?? 0) : 0;
            $qty = is_array($line) ? filter_var($line['qty'] ?? null, FILTER_VALIDATE_INT) : false;
            if ($itemId <= 0 || $qty === false || $qty < 0) {
                throw new ValidationException(t('handover.too_many'));
            }
            if ($qty > 0) {
                $lines[$itemId] = ($lines[$itemId] ?? 0) + $qty;
            }
        }
        if (!$lines) {
            throw new ValidationException(t('handover.no_items'));
        }
        return $lines;
    }

    private function collect(array $order, array $lines, $type, $name)
    {
        $items = array_column(CustomerOrders::items($order), null, 'id');
        foreach ($lines as $itemId => $qty) {
            if (!isset($items[$itemId]) || $qty > VendorOrders::remaining($items[$itemId])) {
                throw new ValidationException(t('handover.too_many'));
            }
        }
        $orderId = (int) $order['id'];
        $pickupId = Database::insert(
            "INSERT INTO order_pickups (vendor_id, order_id, method, picked_by_type, picked_by_name, vendor_user_id) VALUES (?, ?, ?, ?, ?, ?)",
            [$this->vendorId, $orderId, $this->method($order), $type, $name, $this->userId]
        );
        $stock = [];
        $children = [];
        foreach ($lines as $itemId => $qty) {
            $item = $items[$itemId];
            Database::insert(
                "INSERT INTO order_pickup_items (vendor_id, order_pickup_id, order_item_id, qty) VALUES (?, ?, ?, ?)",
                [$this->vendorId, $pickupId, $itemId, $qty]
            );
            Database::execute(
                "UPDATE order_items SET collected_qty = collected_qty + ? WHERE id = ? AND vendor_id = ? AND order_id = ?",
                [$qty, $itemId, $this->vendorId, $orderId]
            );
            $items[$itemId]['collected_qty'] = (int) $item['collected_qty'] + $qty;
            $key = (int) $item['product_id'] . '#' . (int) $item['variant_id'];
            $stock[$key] = ($stock[$key] ?? 0) + $qty;
            if ((string) $item['child_name'] !== '') {
                $children[$item['child_name']] = true;
            }
        }
        // Tertib kunci baris stok sama seperti checkout → elak deadlock
        CustomerOrders::sortStockKeys($stock);
        foreach ($stock as $key => $qty) {
            list($productId, $variantId) = explode('#', $key);
            StockLedger::collect($this->vendorId, $this->cooperative['id'], $productId, $variantId, $qty, $orderId, $this->userId, $order['order_no']);
        }

        $complete = true;
        foreach ($items as $item) {
            $complete = $complete && VendorOrders::remaining($item) === 0;
        }
        $status = $complete ? 'collected' : 'partially_collected';
        Database::execute(
            "UPDATE orders SET status = ?, collected_at = " . ($complete ? 'NOW()' : 'collected_at') . " WHERE id = ? AND vendor_id = ?",
            [$status, $orderId, $this->vendorId]
        );
        CustomerOrders::logStatus($order, $status, 'vendor_user', $this->userId, $name);
        OrderEmails::queueCollected($order, $name, array_keys($children));
        MessageNotifications::orderCollected($this->cooperative, $order, $name, array_keys($children));
        PushNotifications::orderCollected($this->cooperative, $order, $name, array_keys($children));
        (new WriteActivityLogJob($this->vendorId, $this->cooperative['id'], $this->userId, 'handover_collect', 'order', $orderId,
            $order['order_no'] . ' — ' . $name))->run();
        return $status;
    }

    // Cara direkod: E bila kod disahkan; jika tidak, cara carian (found_by A/B/C dari skrin serah) yang aktif, lalai A.
    // QR (C) direkod D bila koperasi hanya mengaktifkan slip bercetak
    private function method(array $order)
    {
        if ($order['pickup_code_hash'] !== null) {
            return 'E';
        }
        $methods = VendorOrders::pickupSettings($this->vendorId, $this->cooperative['id'])['methods'];
        $found = (string) ($this->input['method'] ?? 'A');
        if ($found === 'C' && !in_array('C', $methods, true) && in_array('D', $methods, true)) {
            return 'D';
        }
        return in_array($found, ['A', 'B', 'C', 'D'], true) && in_array($found, $methods, true) ? $found : 'A';
    }
}
