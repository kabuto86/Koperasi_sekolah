<?php
// Billplz (API v3). Kunci: api_key (rahsia), collection_id, x_signature (rahsia).
// Panggilan balik ditandatangani X-Signature (HMAC-SHA256 atas semua medan "kunciNilai" disusun, dicantum "|").
class BillplzGateway extends BaseGateway
{
    const CODE = 'billplz';
    const URLS = ['sandbox' => 'https://www.billplz-sandbox.com/api', 'live' => 'https://www.billplz.com/api'];

    public function createBill(array $bill)
    {
        $response = $this->http('POST', $this->baseUrl() . '/v3/bills', [
            'collection_id'     => $this->value('collection_id'),
            'email'             => (string) $bill['email'],
            'name'              => mb_substr((string) $bill['name'], 0, 255, 'UTF-8'),
            'amount'            => (int) $bill['amount'],
            'callback_url'      => $bill['callback_url'],
            'redirect_url'      => $bill['return_url'],
            'description'       => mb_substr((string) $bill['description'], 0, 200, 'UTF-8'),
            'reference_1_label' => 'Order',
            'reference_1'       => $bill['reference'],
        ], [], $this->value('api_key') . ':');
        $json = $response['json'];
        if ($response['status'] !== 200 || empty($json['id']) || empty($json['url'])) {
            throw new GatewayException('billplz create bill: HTTP ' . $response['status'] . ' ' . substr($response['body'], 0, 200));
        }
        return ['gateway_ref' => (string) $json['id'], 'redirect_url' => (string) $json['url'], 'form' => null];
    }

    public function verifyCallback(array $request)
    {
        $fields = $request['post'];
        $signature = (string) ($fields['x_signature'] ?? '');
        unset($fields['x_signature']);
        $valid = $signature !== '' && hash_equals(self::sign($fields, $this->value('x_signature')), $signature);
        $ref = (string) ($request['post']['id'] ?? '');
        return ['signature_valid' => $valid, 'gateway_ref' => $ref !== '' ? $ref : null];
    }

    // Sumber tandatangan Billplz: setiap medan → "kunci" . "nilai", disusun menaik (tanpa beza huruf), dicantum dengan "|"
    public static function sign(array $fields, $key)
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            $parts[] = $name . (is_scalar($value) ? (string) $value : '');
        }
        sort($parts, SORT_STRING | SORT_FLAG_CASE);
        return hash_hmac('sha256', implode('|', $parts), (string) $key);
    }

    public function fetchStatus($gatewayRef, $amount)
    {
        $response = $this->http('GET', $this->baseUrl() . '/v3/bills/' . rawurlencode($gatewayRef), null, [], $this->value('api_key') . ':');
        $json = $response['json'];
        if ($response['status'] !== 200 || !is_array($json)) {
            throw new GatewayException('billplz get bill: HTTP ' . $response['status']);
        }
        if (($json['paid'] ?? false) === true && ($json['state'] ?? '') === 'paid') {
            return ['status' => 'paid', 'amount' => (int) ($json['paid_amount'] ?? $json['amount'] ?? 0), 'currency' => 'MYR', 'raw' => $json];
        }
        $status = ($json['state'] ?? '') === 'deleted' ? 'failed' : 'pending';
        return ['status' => $status, 'amount' => null, 'currency' => null, 'raw' => $json];
    }

    public function testConnection()
    {
        $response = $this->http('GET', $this->baseUrl() . '/v3/collections/' . rawurlencode($this->value('collection_id')), null, [],
            $this->value('api_key') . ':');
        if ($response['status'] !== 200 || empty($response['json']['id'])) {
            throw new GatewayException('billplz: HTTP ' . $response['status']);
        }
        return true;
    }
}
