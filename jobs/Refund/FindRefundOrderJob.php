<?php
// Cari pesanan untuk borang refund (vendor/refunds/order — PELAN 18.10): order_id, atau q = no. pesanan / kod pendek tepat.
// Pesanan store & POS koperasi ini sahaja (VendorOrders::find). Belum dibayar → ditolak (batal dari halaman pesanan).
class FindRefundOrderJob
{
    private $vendorId;
    private $cooperative;
    private $counterUserId;
    private $params;

    public function __construct($vendorId, array $cooperative, $counterUserId, array $params)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->counterUserId = (int) $counterUserId;
        $this->params = $params;
    }

    public function run()
    {
        $order = $this->findOrder();
        if ($order === null) {
            throw new ValidationException(t('refund.order_not_found'));
        }
        if (Refunds::paidTotal($order) === 0) {
            throw new ValidationException(t('refund.order_not_paid'));
        }
        return self::shape($order, $this->counterUserId);
    }

    private function findOrder()
    {
        $orderId = (int) ($this->params['order_id'] ?? 0);
        if ($orderId > 0) {
            return VendorOrders::find($this->vendorId, $this->cooperative['id'], $orderId);
        }
        $q = strtoupper(trim((string) ($this->params['q'] ?? '')));
        if ($q === '') {
            return null;
        }
        return Database::fetchOne(
            "SELECT * FROM orders WHERE vendor_id = ? AND cooperative_id = ? AND (order_no = ? OR short_code = ?) ORDER BY id DESC LIMIT 1",
            [$this->vendorId, (int) $this->cooperative['id'], $q, $q]
        );
    }

    // RefundOrder (PELAN 18.10) + drawer_shift (syif terbuka pengguna kaunter — refund tunai ditolak dari laci syif itu)
    public static function shape(array $order, $counterUserId)
    {
        $payment = Refunds::latestPayment($order);
        $pending = Refunds::pendingQty($order);
        $shift = PosShifts::openFor($order['vendor_id'], $order['cooperative_id'], $counterUserId);
        return [
            'id'                      => (int) $order['id'],
            'order_no'                => $order['order_no'],
            'short_code'              => $order['short_code'],
            'channel'                 => $order['channel'],
            'status'                  => $order['status'],
            'customer_name'           => (string) $order['customer_name'],
            'payment_method'          => $order['payment_method'],
            'gateway_name'            => Refunds::gatewayName($payment),
            'gateway_supports_refund' => Refunds::gatewayFor($order, $payment) !== null,
            'total'                   => (int) $order['total'],
            'paid_amount'             => Refunds::paidTotal($order),
            'refunded_amount'         => Refunds::total($order),
            'pending_amount'          => Refunds::total($order, ['pending']),
            'refundable_amount'       => Refunds::refundable($order),
            'drawer_shift'            => $shift === null ? null : ['id' => (int) $shift['id'], 'opened_at' => IsoDate::format($shift['opened_at'])],
            'items'                   => array_map(function ($item) use ($pending) {
                return [
                    'id'             => (int) $item['id'],
                    'product_name'   => $item['product_name'],
                    'variant_label'  => $item['variant_label'],
                    'child_name'     => (string) $item['child_name'],
                    'unit_price'     => (int) $item['unit_price'],
                    'qty'            => (int) $item['qty'],
                    'collected_qty'  => (int) $item['collected_qty'],
                    'cancelled_qty'  => (int) $item['cancelled_qty'],
                    'returned_qty'   => (int) $item['returned_qty'],
                    'returnable_qty' => Refunds::returnable($item, $pending),
                ];
            }, CustomerOrders::items($order)),
        ];
    }
}
