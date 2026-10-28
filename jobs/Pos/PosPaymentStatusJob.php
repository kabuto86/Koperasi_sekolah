<?php
// Status bayaran QR jualan POS (vendor/pos/payment-status — skrin kaunter bertanya setiap 2.5 saat).
// Status dibaca dari DB (dikemas kini webhook gateway melalui PaymentSettlement). Jika masih pending, bil ditanya semula
// ke API gateway paling kerap sekali setiap RECHECK_SECONDS bagi setiap jualan (elak membebankan gateway).
class PosPaymentStatusJob
{
    const RECHECK_SECONDS = 10;

    private $vendorId;
    private $cooperativeId;
    private $orderId;

    public function __construct($vendorId, $cooperativeId, $orderId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->orderId = (int) $orderId;
    }

    public function run()
    {
        $order = PosSales::require($this->vendorId, $this->cooperativeId, $this->orderId);
        if ($order['status'] === 'pending_payment' && RateLimit::attempt('pos_payment_check:' . (int) $order['id'], 1, self::RECHECK_SECONDS)) {
            PosSales::recheckPayment($order);
            $order = PosSales::require($this->vendorId, $this->cooperativeId, $this->orderId);
        }
        $status = $order['paid_at'] !== null ? 'paid' : ($order['status'] === 'pending_payment' ? 'pending' : 'cancelled');
        return ['status' => $status, 'receipt' => PosSales::receipt($order)];
    }
}
