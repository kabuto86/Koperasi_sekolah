<?php
// SenangPay. Kunci: merchant_id + secret_key (rahsia). Tiada API cipta bil — pautan bayaran ditandatangani
// HMAC-SHA256 (butiran + amaun + no. rujukan), jadi amaun dikunci semasa bil dicipta.
// URL panggilan balik & kembali ditetapkan dalam papan pemuka SenangPay (vendor salin dari halaman Bayaran).
class SenangPayGateway extends BaseGateway
{
    const CODE = 'senangpay';
    const URLS = ['sandbox' => 'https://sandbox.senangpay.my', 'live' => 'https://app.senangpay.my'];

    public function createBill(array $bill)
    {
        $secret = $this->value('secret_key');
        $detail = self::plainText($bill['description'], 100);
        $amount = self::ringgit($bill['amount']);
        $orderId = $bill['reference'];
        $query = http_build_query([
            'detail'   => $detail,
            'amount'   => $amount,
            'order_id' => $orderId,
            'hash'     => hash_hmac('sha256', $secret . $detail . $amount . $orderId, $secret),
            'name'     => (string) $bill['name'],
            'email'    => (string) $bill['email'],
            'phone'    => (string) $bill['phone'],
        ]);
        $url = $this->baseUrl() . '/payment/' . rawurlencode($this->value('merchant_id')) . '?' . $query;
        return ['gateway_ref' => $orderId, 'redirect_url' => $url, 'form' => null];
    }

    public function verifyCallback(array $request)
    {
        $post = $request['post'];
        $secret = $this->value('secret_key');
        $source = $secret . ($post['status_id'] ?? '') . ($post['order_id'] ?? '') . ($post['transaction_id'] ?? '') . ($post['msg'] ?? '');
        $hash = (string) ($post['hash'] ?? '');
        $valid = $hash !== '' && hash_equals(hash_hmac('sha256', $source, $secret), $hash);
        $ref = (string) ($post['order_id'] ?? '');
        return ['signature_valid' => $valid, 'gateway_ref' => $ref !== '' ? $ref : null];
    }

    public function fetchStatus($gatewayRef, $amount)
    {
        $merchantId = $this->value('merchant_id');
        $secret = $this->value('secret_key');
        $response = $this->http('GET', $this->baseUrl() . '/apiv1/query_order_status?' . http_build_query([
            'merchant_id' => $merchantId,
            'order_id'    => $gatewayRef,
            'hash'        => hash_hmac('sha256', $merchantId . $secret . $gatewayRef, $secret),
        ]));
        $json = $response['json'];
        if ($response['status'] !== 200 || !is_array($json)) {
            throw new GatewayException('senangpay query_order_status: HTTP ' . $response['status']);
        }
        $status = 'pending';
        foreach (is_array($json['data'] ?? null) ? $json['data'] : [] as $row) {
            $state = strtolower((string) ($row['payment_info']['status'] ?? ''));
            if ($state === 'paid') {
                $paid = $row['payment_info']['amount'] ?? $row['order_detail']['amount'] ?? null;
                return ['status' => 'paid', 'amount' => $paid === null ? null : self::sen($paid), 'currency' => 'MYR', 'raw' => $row];
            }
            if ($state === 'failed') {
                $status = 'failed';
            }
        }
        return ['status' => $status, 'amount' => null, 'currency' => null, 'raw' => $json];
    }

    // Amaun ditandatangani dalam pautan bayaran — tidak boleh diubah ibu bapa
    public function amountLocked()
    {
        return true;
    }

    // Semakan kunci: tanya status rujukan rekaan — tandatangan salah ditolak SenangPay (status 0 + msg)
    public function testConnection()
    {
        $merchantId = $this->value('merchant_id');
        $secret = $this->value('secret_key');
        $response = $this->http('GET', $this->baseUrl() . '/apiv1/query_order_status?' . http_build_query([
            'merchant_id' => $merchantId,
            'order_id'    => 'TEST-CONNECTION',
            'hash'        => hash_hmac('sha256', $merchantId . $secret . 'TEST-CONNECTION', $secret),
        ]));
        $message = strtolower((string) ($response['json']['msg'] ?? ''));
        if ($response['status'] !== 200 || !is_array($response['json']) || strpos($message, 'hash') !== false
            || strpos($message, 'merchant') !== false) {
            throw new GatewayException('senangpay: ' . substr($response['body'], 0, 200));
        }
        return true;
    }
}
