<?php
// Log masuk juruwang di kaunter (vendor/pos/pin-login): PIN disemak oleh PosPinLoginJob (Argon2id, 5 salah → dikunci),
// log aktiviti pos_login atas nama juruwang → { cashier, shift }. Endpoint menyimpan juruwang dalam sesi (PosAuth).
class PosLoginJob
{
    private $vendorId;
    private $cooperative;
    private $userId;
    private $pin;

    public function __construct($vendorId, array $cooperative, $userId, $pin)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->pin = (string) $pin;
    }

    public function run()
    {
        $coopId = (int) $this->cooperative['id'];
        $cashier = (new PosPinLoginJob($this->vendorId, $coopId, $this->userId, $this->pin))->run();
        (new WriteActivityLogJob($this->vendorId, $coopId, $cashier['id'], 'pos_login'))->run();
        $shift = PosShifts::openFor($this->vendorId, $coopId, $cashier['id']);
        return ['cashier' => $cashier, 'shift' => $shift === null ? null : PosShifts::summary($shift)];
    }
}
