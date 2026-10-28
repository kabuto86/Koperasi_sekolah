<?php
// Keadaan kaunter POS (vendor/pos/session — PELAN 18.11): koperasi, juruwang PIN (atau null), syif terbuka,
// QR online (gateway aktif koperasi — tunai sentiasa dibenarkan di POS), hari "koperasi tutup" (modul calendar,
// amaran sahaja) dan senarai kakitangan yang boleh log masuk PIN di koperasi ini (cashiers — skrin log masuk).
class GetPosSessionJob
{
    private $vendorId;
    private $cooperative;
    private $cashier;

    public function __construct($vendorId, array $cooperative, $cashier)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->cashier = $cashier;
    }

    public function run()
    {
        $coop = $this->cooperative;
        $gateway = CooperativeGateways::active($this->vendorId, $coop['id']);
        $shift = $this->cashier === null ? null : PosShifts::openFor($this->vendorId, $coop['id'], $this->cashier['id']);
        return [
            'cooperative'      => self::cooperativeShape($coop),
            'cashier'          => $this->cashier,
            'shift'            => $shift === null ? null : PosShifts::summary($shift),
            'online_available' => $gateway !== null,
            'gateway_name'     => $gateway === null ? null : $gateway['name'],
            'closed_today'     => CheckoutQuote::ordersBlocked($coop),
            'cashiers'         => PosCashiers::forCounter($this->vendorId, $coop['id']),
        ];
    }

    public static function cooperativeShape(array $coop)
    {
        return [
            'id'          => (int) $coop['id'],
            'school_name' => $coop['school_name'],
            'address'     => (string) $coop['address'],
            'phone'       => (string) $coop['phone'],
            'logo_url'    => Uploader::url($coop['logo_path']),
        ];
    }
}
