<?php
// Benarkan / sekat driver gateway untuk vendor. Disekat: vendor tidak boleh memilihnya lagi (semakan pembayaran — B8).
class TogglePaymentGatewayJob
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
        $code = (string) ($this->input['code'] ?? '');
        if (!Database::fetchValue("SELECT 1 FROM payment_gateways WHERE code = ?", [$code])) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $allowed = !empty($this->input['allowed']);
        Database::execute("UPDATE payment_gateways SET is_enabled_for_vendors = ? WHERE code = ?", [$allowed ? 1 : 0, $code]);
        (new WriteAuditLogJob('owner', $this->actorId, null, 'gateway.toggle', 'gateway', null,
            $code . ': ' . ($allowed ? 'allowed' : 'blocked')))->run();
        return ['code' => $code, 'allowed_for_vendors' => $allowed];
    }
}
