<?php
// Stripe Checkout. Kunci: publishable_key, secret_key (rahsia), webhook_secret (rahsia).
// Webhook ditandatangani header Stripe-Signature (t=…, v1=HMAC-SHA256("{t}.{badan}")), toleransi 5 minit.
// Mod sandbox = kunci ujian (sk_test_…); URL API sama untuk kedua-dua mod.
class StripeGateway extends BaseGateway
{
    const CODE = 'stripe';
    const URLS = ['sandbox' => 'https://api.stripe.com', 'live' => 'https://api.stripe.com'];
    const TOLERANCE = 300;

    public function createBill(array $bill)
    {
        $response = $this->http('POST', $this->baseUrl() . '/v1/checkout/sessions', [
            'mode'                 => 'payment',
            'success_url'          => $bill['return_url'],
            'cancel_url'           => $bill['return_url'],
            'client_reference_id'  => $bill['reference'],
            'customer_email'       => (string) $bill['email'],
            'metadata'             => ['reference' => $bill['reference']],
            'line_items'           => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'myr',
                    'unit_amount'  => (int) $bill['amount'],
                    'product_data' => ['name' => mb_substr((string) $bill['description'], 0, 200, 'UTF-8')],
                ],
            ]],
        ], $this->authHeader());
        $json = $response['json'];
        if ($response['status'] !== 200 || empty($json['id']) || empty($json['url'])) {
            throw new GatewayException('stripe checkout session: HTTP ' . $response['status']);
        }
        return ['gateway_ref' => (string) $json['id'], 'redirect_url' => (string) $json['url'], 'form' => null];
    }

    public function verifyCallback(array $request)
    {
        $header = (string) ($request['headers']['stripe-signature'] ?? '');
        $timestamp = 0;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2 && $pair[0] === 't') {
                $timestamp = (int) $pair[1];
            } elseif (count($pair) === 2 && $pair[0] === 'v1') {
                $signatures[] = $pair[1];
            }
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $request['raw'], $this->value('webhook_secret'));
        $valid = false;
        foreach ($signatures as $signature) {
            $valid = $valid || hash_equals($expected, $signature);
        }
        $valid = $valid && abs(time() - $timestamp) <= self::TOLERANCE;

        $event = json_decode((string) $request['raw'], true);
        $object = $event['data']['object'] ?? [];
        $ref = (strpos((string) ($event['type'] ?? ''), 'checkout.session.') === 0) ? (string) ($object['id'] ?? '') : '';
        return ['signature_valid' => $valid, 'gateway_ref' => $ref !== '' ? $ref : null];
    }

    public function fetchStatus($gatewayRef, $amount)
    {
        $response = $this->http('GET', $this->baseUrl() . '/v1/checkout/sessions/' . rawurlencode($gatewayRef), null, $this->authHeader());
        $json = $response['json'];
        if ($response['status'] !== 200 || !is_array($json)) {
            throw new GatewayException('stripe get session: HTTP ' . $response['status']);
        }
        if (($json['payment_status'] ?? '') === 'paid') {
            return ['status' => 'paid', 'amount' => (int) ($json['amount_total'] ?? 0), 'currency' => strtoupper((string) ($json['currency'] ?? '')),
                'raw' => ['id' => $json['id'] ?? null, 'payment_intent' => $json['payment_intent'] ?? null]];
        }
        $status = ($json['status'] ?? '') === 'expired' ? 'failed' : 'pending';
        return ['status' => $status, 'amount' => null, 'currency' => null, 'raw' => ['id' => $json['id'] ?? null, 'status' => $json['status'] ?? null]];
    }

    public function testConnection()
    {
        $response = $this->http('GET', $this->baseUrl() . '/v1/balance', null, $this->authHeader());
        if ($response['status'] !== 200) {
            throw new GatewayException('stripe: HTTP ' . $response['status']);
        }
        return true;
    }

    public function supportsRefund()
    {
        return true;
    }

    // Refund = POST /v1/refunds atas payment_intent sesi Checkout (dibaca semula dari Stripe, bukan dari DB).
    // Idempotency-Key = no. refund → cubaan berganda tidak memulangkan wang dua kali
    public function refund($gatewayRef, $amount, $reference)
    {
        $session = $this->http('GET', $this->baseUrl() . '/v1/checkout/sessions/' . rawurlencode($gatewayRef), null, $this->authHeader());
        $intent = (string) ($session['json']['payment_intent'] ?? '');
        if ($session['status'] !== 200 || $intent === '') {
            throw new GatewayException('stripe refund: sesi tiada payment_intent (HTTP ' . $session['status'] . ')');
        }
        $response = $this->http('POST', $this->baseUrl() . '/v1/refunds', [
            'payment_intent' => $intent,
            'amount'         => (int) $amount,
            'metadata'       => ['reference' => $reference],
        ], array_merge($this->authHeader(), ['Idempotency-Key: ' . $reference]));
        $json = $response['json'];
        if ($response['status'] !== 200 || empty($json['id']) || in_array($json['status'] ?? '', ['failed', 'canceled'], true)) {
            throw new GatewayException('stripe refund: HTTP ' . $response['status']);
        }
        return ['refund_ref' => (string) $json['id']];
    }

    public function callbackResponse()
    {
        return '{"received":true}';
    }

    private function authHeader()
    {
        return ['Authorization: Bearer ' . $this->value('secret_key')];
    }
}
