<?php
// ToyyibPay (FPX). Kunci: secret_key (rahsia) + category_code.
// Panggilan balik ToyyibPay TIDAK bertandatangan → status sentiasa disahkan dengan getBillTransactions (kunci rahsia, pelayan ke pelayan).
class ToyyibPayGateway extends BaseGateway
{
    const CODE = 'toyyibpay';
    const URLS = ['sandbox' => 'https://dev.toyyibpay.com', 'live' => 'https://toyyibpay.com'];

    public function createBill(array $bill)
    {
        $response = $this->http('POST', $this->baseUrl() . '/index.php/api/createBill', [
            'userSecretKey'           => $this->value('secret_key'),
            'categoryCode'            => $this->value('category_code'),
            'billName'                => self::plainText($bill['reference'], 30),
            'billDescription'         => self::plainText($bill['description'], 100),
            'billPriceSetting'        => 1,
            'billPayorInfo'           => 1,
            'billAmount'              => (int) $bill['amount'],
            'billReturnUrl'           => $bill['return_url'],
            'billCallbackUrl'         => $bill['callback_url'],
            'billExternalReferenceNo' => $bill['reference'],
            'billTo'                  => mb_substr((string) $bill['name'], 0, 100, 'UTF-8'),
            'billEmail'               => (string) $bill['email'],
            'billPhone'               => preg_replace('/[^0-9]/', '', (string) $bill['phone']),
            'billPaymentChannel'      => '0',
        ]);
        $code = $response['json'][0]['BillCode'] ?? '';
        if (!is_string($code) || !preg_match('/^[A-Za-z0-9]{4,30}$/', $code)) {
            throw new GatewayException('toyyibpay createBill: ' . substr($response['body'], 0, 200));
        }
        return ['gateway_ref' => $code, 'redirect_url' => $this->baseUrl() . '/' . $code, 'form' => null];
    }

    public function verifyCallback(array $request)
    {
        $ref = (string) ($request['post']['billcode'] ?? '');
        return ['signature_valid' => null, 'gateway_ref' => $ref !== '' ? $ref : null];
    }

    // billpaymentStatus: 1 berjaya, 2 menunggu, 3 gagal, 4 menunggu (FPX)
    public function fetchStatus($gatewayRef, $amount)
    {
        $response = $this->http('POST', $this->baseUrl() . '/index.php/api/getBillTransactions', ['billCode' => $gatewayRef]);
        if ($response['status'] !== 200 || !is_array($response['json'])) {
            throw new GatewayException('toyyibpay getBillTransactions: HTTP ' . $response['status']);
        }
        $status = 'pending';
        foreach ($response['json'] as $row) {
            $state = (string) ($row['billpaymentStatus'] ?? '');
            if ($state === '1') {
                return ['status' => 'paid', 'amount' => self::sen($row['billpaymentAmount'] ?? 0), 'currency' => 'MYR', 'raw' => $row];
            }
            if ($state === '3' && $status === 'pending') {
                $status = 'failed';
            }
        }
        return ['status' => $status, 'amount' => null, 'currency' => null, 'raw' => $response['json']];
    }

    public function testConnection()
    {
        $response = $this->http('POST', $this->baseUrl() . '/index.php/api/getCategoryDetails', [
            'userSecretKey' => $this->value('secret_key'),
            'categoryCode'  => $this->value('category_code'),
        ]);
        if ($response['status'] !== 200 || empty($response['json'][0]['categoryName'])) {
            throw new GatewayException('toyyibpay: ' . substr($response['body'], 0, 200));
        }
        return true;
    }
}
