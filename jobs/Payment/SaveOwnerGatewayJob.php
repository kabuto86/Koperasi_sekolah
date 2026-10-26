<?php
// Gateway owner untuk kutip yuran langganan vendor. Medan bukan rahsia → owner_gateway_config (JSON);
// rahsia → owner_gateway_secrets_enc (Crypto). Rahsia kosong = kekal (jika driver sama).
class SaveOwnerGatewayJob
{
    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $code = (string) ($this->input['driver_code'] ?? '');
        $gateway = Database::fetchOne("SELECT code, config_fields_json FROM payment_gateways WHERE code = ?", [$code]);
        if ($gateway === null) {
            throw new ValidationException(t('error.check_form'), ['driver_code' => t('error.required')]);
        }
        $credentials = is_array($this->input['credentials'] ?? null) ? $this->input['credentials'] : [];
        $oldSecrets = $code === PlatformSettings::get('owner_gateway_code') ? ListPaymentGatewaysJob::ownerSecrets() : [];

        $values = [];
        $secrets = [];
        $v = new Validator($this->input);
        foreach (ListPaymentGatewaysJob::fields($gateway['config_fields_json']) as $field) {
            $name = $field['name'];
            $value = trim((string) ($credentials[$name] ?? ''));
            $v->maxLength('credentials.' . $name, 500);
            if ($field['secret']) {
                $value = $value !== '' ? $value : ($oldSecrets[$name] ?? '');
                if ($value === '') {
                    $v->addError('credentials.' . $name, t('error.required'));
                } else {
                    $secrets[$name] = $value;
                }
            } elseif ($value === '') {
                $v->addError('credentials.' . $name, t('error.required'));
            } else {
                $values[$name] = $value;
            }
        }
        ValidationException::throwIfFails($v);

        Database::beginTransaction();
        PlatformSettings::set('owner_gateway_code', $code, $this->actorId);
        PlatformSettings::set('owner_gateway_mode', empty($this->input['sandbox']) ? 'live' : 'sandbox', $this->actorId);
        PlatformSettings::set('owner_gateway_config', json_encode((object) $values, JSON_UNESCAPED_UNICODE), $this->actorId);
        PlatformSettings::set('owner_gateway_secrets_enc', Crypto::encrypt(json_encode($secrets)), $this->actorId);
        Database::commit();

        (new WriteAuditLogJob('owner', $this->actorId, null, 'gateway.owner_update', 'gateway', null, $code))->run();
        return ListPaymentGatewaysJob::ownerGateway();
    }
}
