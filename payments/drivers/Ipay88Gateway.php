<?php
// iPay88. Kunci: merchant_code + merchant_key (rahsia). Halaman bayaran perlu borang POST → ibu bapa melalui
// store/payment-redirect.php (borang dihantar automatik). Tandatangan SHA256; semakan status melalui enquiry.asp
// (memerlukan amaun — "00" hanya jika rujukan DAN amaun sepadan).
class Ipay88Gateway extends BaseGateway
{
    const CODE = 'ipay88';
    const URLS = ['sandbox' => 'https://sandbox.ipay88.com.my/epayment', 'live' => 'https://payment.ipay88.com.my/epayment'];

    public function createBill(array $bill)
    {
        $amount = self::ringgit($bill['amount']);
        $fields = [
            'MerchantCode'  => $this->value('merchant_code'),
            'PaymentId'     => '',
            'RefNo'         => $bill['reference'],
            'Amount'        => $amount,
            'Currency'      => 'MYR',
            'ProdDesc'      => mb_substr((string) $bill['description'], 0, 100, 'UTF-8'),
            'UserName'      => mb_substr((string) $bill['name'], 0, 100, 'UTF-8'),
            'UserEmail'     => (string) $bill['email'],
            'UserContact'   => (string) $bill['phone'],
            'Remark'        => '',
            'Lang'          => 'UTF-8',
            'SignatureType' => 'SHA256',
            'Signature'     => $this->signature([$bill['reference'], self::digits($amount), 'MYR']),
            'ResponseURL'   => $bill['return_url'],
            'BackendURL'    => $bill['callback_url'],
        ];
        return [
            'gateway_ref'  => $bill['reference'],
            'redirect_url' => null,
            'form'         => ['action' => $this->baseUrl() . '/entry.asp', 'fields' => $fields],
        ];
    }

    public function verifyCallback(array $request)
    {
        $post = $request['post'];
        $expected = $this->signature([
            $post['PaymentId'] ?? '', $post['RefNo'] ?? '', self::digits($post['Amount'] ?? ''), $post['Currency'] ?? '', $post['Status'] ?? '',
        ]);
        $signature = (string) ($post['Signature'] ?? '');
        $valid = $signature !== '' && hash_equals($expected, strtolower($signature))
            && hash_equals($this->value('merchant_code'), (string) ($post['MerchantCode'] ?? ''));
        $ref = (string) ($post['RefNo'] ?? '');
        return ['signature_valid' => $valid, 'gateway_ref' => $ref !== '' ? $ref : null];
    }

    public function fetchStatus($gatewayRef, $amount)
    {
        $response = $this->http('POST', $this->baseUrl() . '/enquiry.asp', [
            'MerchantCode' => $this->value('merchant_code'),
            'RefNo'        => $gatewayRef,
            'Amount'       => self::ringgit($amount),
        ]);
        if ($response['status'] !== 200) {
            throw new GatewayException('ipay88 enquiry: HTTP ' . $response['status']);
        }
        $answer = trim($response['body']);
        if ($answer === '00') {
            // iPay88 hanya menjawab "00" bila amaun yang ditanya sama dengan amaun dibayar
            return ['status' => 'paid', 'amount' => (int) $amount, 'currency' => 'MYR', 'raw' => ['enquiry' => $answer]];
        }
        $status = stripos($answer, 'Payment fail') !== false ? 'failed' : 'pending';
        return ['status' => $status, 'amount' => null, 'currency' => null, 'raw' => ['enquiry' => $answer]];
    }

    // iPay88 tiada API untuk menguji kunci tanpa transaksi
    public function testConnection()
    {
        throw new GatewayException('ipay88: test unsupported', 1);
    }

    public function callbackResponse()
    {
        return 'RECEIVEOK';
    }

    // SHA256(merchant_key + merchant_code + medan...) dalam huruf kecil
    private function signature(array $parts)
    {
        return hash('sha256', $this->value('merchant_key') . $this->value('merchant_code') . implode('', $parts));
    }

    // "1,250.00" → "125000" (format amaun dalam tandatangan iPay88)
    private static function digits($amount)
    {
        return str_replace(['.', ','], '', (string) $amount);
    }
}
