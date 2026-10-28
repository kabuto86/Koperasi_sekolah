<?php
// Kontrak setiap driver gateway bayaran (PELAN 13). Wang dalam SEN (integer); mata wang MYR.
// Driver TIDAK menyentuh DB — job (jobs/Payment/) yang menyimpan & memutuskan status.
interface GatewayInterface
{
    // Cipta bil di gateway. $bill = [reference, amount, description, name, email, phone, return_url, callback_url].
    // Pulangkan ['gateway_ref' => id bil di gateway, 'redirect_url' => halaman bayaran | null, 'form' => null | ['action', 'fields']]
    // ('form' = gateway yang perlu POST borang, cth. iPay88). Gagal → GatewayException
    public function createBill(array $bill);

    // Semak panggilan balik. $request = Request::webhook() → ['post', 'get', 'raw', 'headers'].
    // Pulangkan ['signature_valid' => true | false | null (gateway tiada tandatangan), 'gateway_ref' => string | null]
    // Status dari panggilan balik TIDAK dipercayai — sentiasa fetchStatus() selepas ini
    public function verifyCallback(array $request);

    // Tanya status sebenar ke API gateway (pelayan ke pelayan).
    // Pulangkan ['status' => paid | failed | pending, 'amount' => sen | null, 'currency' => 'MYR' | null, 'raw' => array]
    public function fetchStatus($gatewayRef, $amount);

    // true = amaun dikunci dalam tandatangan semasa bil dicipta (gateway tidak memulangkan amaun dalam semakan status)
    public function amountLocked();

    // Uji kunci API tersimpan. Gagal → GatewayException
    public function testConnection();

    public function supportsRefund();

    // Badan jawapan HTTP yang dijangka gateway selepas panggilan balik diterima
    public function callbackResponse();
}
