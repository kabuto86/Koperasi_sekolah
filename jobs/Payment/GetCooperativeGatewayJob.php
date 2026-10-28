<?php
// Nilai tersimpan satu driver untuk koperasi (vendor/payments/gateway) — GatewayPublic | null, tanpa rahsia.
class GetCooperativeGatewayJob
{
    private $vendorId;
    private $cooperativeId;
    private $gatewayCode;

    public function __construct($vendorId, $cooperativeId, $gatewayCode)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->gatewayCode = (string) $gatewayCode;
    }

    public function run()
    {
        return CooperativeGateways::publicShape(CooperativeGateways::find($this->vendorId, $this->cooperativeId, $this->gatewayCode));
    }
}
