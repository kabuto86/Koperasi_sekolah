<?php
// Simpan kunci API gateway koperasi (PELAN 13, 18.10 vendor/payments/save-gateway). Driver mesti dibenarkan owner.
// Bukan rahsia → config_json; rahsia → credentials_encrypted (Crypto). Rahsia kosong = kekal nilai lama.
// Satu gateway aktif setiap koperasi — yang disimpan menjadi aktif, yang lain kekal tersimpan tetapi tidak aktif.
class SaveCooperativeGatewayJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $code = (string) ($this->input['gateway_code'] ?? '');
        $driver = Database::fetchOne("SELECT * FROM payment_gateways WHERE code = ? AND is_enabled_for_vendors = 1", [$code]);
        if ($driver === null || !GatewayFactory::supports($code)) {
            throw new ValidationException(t('payment.gateway_not_allowed'), ['gateway_code' => t('payment.gateway_not_allowed')]);
        }
        $mode = (string) ($this->input['mode'] ?? '');
        $credentials = is_array($this->input['credentials'] ?? null) ? $this->input['credentials'] : [];
        $existing = CooperativeGateways::find($this->vendorId, $this->cooperativeId, $code);
        $oldSecrets = $existing ? CooperativeGateways::secrets($existing) : [];

        $v = new Validator($this->input);
        if (!in_array($mode, ['sandbox', 'live'], true)) {
            $v->addError('mode', t('error.invalid'));
        }
        $values = [];
        $secrets = [];
        foreach (ListPaymentGatewaysJob::fields($driver['config_fields_json']) as $field) {
            $name = $field['name'];
            $value = is_scalar($credentials[$name] ?? null) ? trim((string) $credentials[$name]) : '';
            $v->maxLength('credentials.' . $name, 500);
            if ($field['secret']) {
                $value = $value !== '' ? $value : ($oldSecrets[$name] ?? '');
            }
            if ($value === '') {
                $v->addError('credentials.' . $name, t('error.required'));
            } elseif ($field['secret']) {
                $secrets[$name] = $value;
            } else {
                $values[$name] = $value;
            }
        }
        ValidationException::throwIfFails($v);

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO cooperative_gateways (vendor_id, cooperative_id, gateway_code, config_json, credentials_encrypted, mode, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE config_json = VALUES(config_json), credentials_encrypted = VALUES(credentials_encrypted),
                                         mode = VALUES(mode), is_active = 1",
                [$this->vendorId, $this->cooperativeId, $code, json_encode((object) $values, JSON_UNESCAPED_UNICODE),
                    Crypto::encrypt(json_encode($secrets)), $mode]
            );
            Database::execute(
                "UPDATE cooperative_gateways SET is_active = 0 WHERE vendor_id = ? AND cooperative_id = ? AND gateway_code <> ?",
                [$this->vendorId, $this->cooperativeId, $code]
            );
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'gateway_save', 'cooperative',
                $this->cooperativeId, $driver['name'] . ' · ' . $mode))->run();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return CooperativeGateways::publicShape(CooperativeGateways::find($this->vendorId, $this->cooperativeId, $code));
    }
}
