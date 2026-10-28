<?php
// Panggilan balik gateway (api/v1/webhooks/{gateway}.php?coop={id}) — PELAN 13.
//   1. Setiap panggilan dilog dalam webhook_logs (badan, query, keputusan)
//   2. Tandatangan disemak dengan kunci koperasi (driver->verifyCallback); salah → 400, tiada perubahan
//   3. Rujukan dicari dalam payments koperasi + gateway itu SAHAJA
//   4. Status ditanya semula ke API gateway + amaun disemak (PaymentSettlement) — idempotent
// Koperasi dari URL; vendor dari baris koperasi di DB. Pulangkan ['status' => kod HTTP, 'body' => teks].
class HandleGatewayCallbackJob
{
    const MAX_PAYLOAD = 20000;

    private $gatewayCode;
    private $cooperativeId;
    private $request;

    public function __construct($gatewayCode, $cooperativeId, array $request)
    {
        $this->gatewayCode = (string) $gatewayCode;
        $this->cooperativeId = $cooperativeId === null ? null : (int) $cooperativeId;
        $this->request = $request;
    }

    public function run()
    {
        $logId = $this->log();
        $row = $this->cooperativeId === null ? null : CooperativeGateways::forCallback($this->cooperativeId, $this->gatewayCode);
        if ($row === null || !GatewayFactory::supports($this->gatewayCode)) {
            return $this->finish($logId, 'no_gateway', 0, 404, 'NOT FOUND');
        }
        $driver = CooperativeGateways::driver($row);
        $verdict = $driver->verifyCallback($this->request);
        $signature = $verdict['signature_valid'] === true ? 1 : 0;
        if ($verdict['signature_valid'] === false) {
            return $this->finish($logId, 'bad_signature', 0, 400, 'INVALID SIGNATURE');
        }

        $payment = $verdict['gateway_ref'] === null ? null : Database::fetchOne(
            "SELECT * FROM payments WHERE gateway_ref = ? AND vendor_id = ? AND cooperative_id = ? AND gateway_code = ? AND method = 'online'",
            [$verdict['gateway_ref'], (int) $row['vendor_id'], (int) $row['cooperative_id'], $this->gatewayCode]
        );
        if ($payment === null) {
            // Rujukan tiada / milik koperasi lain — terima (gateway berhenti menghantar) tetapi tiada perubahan
            return $this->finish($logId, 'unknown_reference', $signature, 200, $driver->callbackResponse());
        }
        try {
            $result = PaymentSettlement::recheck($payment, $driver);
        } catch (GatewayException $error) {
            error_log('[KoperasiKu] semak semula ' . $payment['gateway_ref'] . ': ' . $error->getMessage());
            // Gateway akan menghantar semula; cron luput juga menyemak sebelum meluputkan
            return $this->finish($logId, 'recheck_failed', $signature, 502, 'RETRY');
        }
        $result = $verdict['signature_valid'] === null ? $result . ' (unsigned)' : $result;
        return $this->finish($logId, $result, $signature, 200, $driver->callbackResponse());
    }

    private function log()
    {
        $payload = json_encode([
            'post' => $this->request['post'],
            'get'  => $this->request['get'],
            'raw'  => $this->request['post'] ? null : $this->request['raw'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $cooperativeId = $this->cooperativeId !== null
            && Database::fetchValue("SELECT 1 FROM cooperatives WHERE id = ?", [$this->cooperativeId]) ? $this->cooperativeId : null;
        return Database::insert(
            "INSERT INTO webhook_logs (gateway_code, cooperative_id, payload, signature_valid, processed) VALUES (?, ?, ?, 0, 0)",
            [substr($this->gatewayCode, 0, 20), $cooperativeId, substr((string) $payload, 0, self::MAX_PAYLOAD)]
        );
    }

    private function finish($logId, $result, $signatureValid, $status, $body)
    {
        $processed = $status === 200 && $result !== 'unknown_reference' ? 1 : 0;
        Database::execute(
            "UPDATE webhook_logs SET signature_valid = ?, processed = ?, result = ? WHERE id = ?",
            [$signatureValid, $processed, substr($result, 0, 40), $logId]
        );
        return ['status' => $status, 'body' => $body, 'result' => $result];
    }
}
