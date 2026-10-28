<?php
// Kaedah bayaran diterima koperasi (vendor/payments/save-methods): sekurang-kurangnya satu; online perlu gateway aktif.
class SavePaymentMethodsJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $cash = !empty($this->input['accept_cash']);
        $online = !empty($this->input['accept_online']);
        if (!$cash && !$online) {
            throw new ValidationException(t('coop.payment_required'), ['accept_online' => t('coop.payment_required')]);
        }
        if ($online && CooperativeGateways::active($this->vendorId, $this->cooperativeId) === null) {
            throw new ValidationException(t('payment.online_needs_gateway'), ['accept_online' => t('payment.online_needs_gateway')]);
        }
        Database::execute(
            "UPDATE cooperatives SET accept_cash = ?, accept_online = ? WHERE id = ? AND vendor_id = ?",
            [$cash ? 1 : 0, $online ? 1 : 0, $this->cooperativeId, $this->vendorId]
        );
        $detail = implode(' + ', array_filter([$cash ? 'cash' : '', $online ? 'online' : '']));
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'payment_methods_save', 'cooperative',
            $this->cooperativeId, $detail))->run();
        return ['accept_cash' => $cash, 'accept_online' => $online];
    }
}
