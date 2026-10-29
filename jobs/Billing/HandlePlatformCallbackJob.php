<?php
// Panggilan balik gateway owner untuk yuran langganan (api/v1/webhooks/platform.php?gateway={kod}) — PELAN 13:
//   1. dilog dalam webhook_logs (cooperative_id NULL)
//   2. tandatangan disemak dengan kunci owner; salah → 400
//   3. rujukan dicari dalam platform_payments gateway itu SAHAJA
//   4. status ditanya semula ke API gateway + amaun disemak (PlatformPaymentSettlement) — idempotent
class HandlePlatformCallbackJob
{
    const MAX_PAYLOAD = 20000;

    private $gatewayCode;
    private $request;

    public function __construct($gatewayCode, array $request)
    {
        $this->gatewayCode = (string) $gatewayCode;
        $this->request = $request;
    }

    public function run()
    {
        $logId = $this->log();
        $driver = GatewayFactory::supports($this->gatewayCode) ? PlatformGateway::driver($this->gatewayCode) : null;
        if ($driver === null) {
            return $this->finish($logId, 'no_gateway', 0, 404, 'NOT FOUND');
        }
        $verdict = $driver->verifyCallback($this->request);
        $signature = $verdict['signature_valid'] === true ? 1 : 0;
        if ($verdict['signature_valid'] === false) {
            return $this->finish($logId, 'bad_signature', 0, 400, 'INVALID SIGNATURE');
        }
        $payment = $verdict['gateway_ref'] === null ? null : Database::fetchOne(
            "SELECT * FROM platform_payments WHERE gateway_ref = ? AND gateway_code = ? AND method = 'online'",
            [$verdict['gateway_ref'], $this->gatewayCode]
        );
        if ($payment === null) {
            return $this->finish($logId, 'unknown_reference', $signature, 200, $driver->callbackResponse());
        }
        try {
            $result = PlatformPaymentSettlement::recheck($payment, $driver);
        } catch (GatewayException $error) {
            error_log('[KoperasiKu] semak semula invois ' . $payment['gateway_ref'] . ': ' . $error->getMessage());
            return $this->finish($logId, 'recheck_failed', $signature, 502, 'RETRY');
        }
        $result = $verdict['signature_valid'] === null ? $result . ' (unsigned)' : $result;
        return $this->finish($logId, $result, $signature, 200, $driver->callbackResponse());
    }

    private function log()
    {
        $payload = json_encode([
            'scope' => 'platform',
            'post'  => $this->request['post'],
            'get'   => $this->request['get'],
            'raw'   => $this->request['post'] ? null : $this->request['raw'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return Database::insert(
            "INSERT INTO webhook_logs (gateway_code, cooperative_id, payload, signature_valid, processed) VALUES (?, NULL, ?, 0, 0)",
            [substr($this->gatewayCode, 0, 20), substr((string) $payload, 0, self::MAX_PAYLOAD)]
        );
    }

    private function finish($logId, $result, $signatureValid, $status, $body)
    {
        $processed = $status === 200 && $result !== 'unknown_reference' ? 1 : 0;
        Database::execute("UPDATE webhook_logs SET signature_valid = ?, processed = ?, result = ? WHERE id = ?",
            [$signatureValid, $processed, substr($result, 0, 40), $logId]);
        return ['status' => $status, 'body' => $body, 'result' => $result];
    }
}
