<?php
// Henti modul pada hujung tempoh (atau teruskan semula — undo). Modul kekal aktif hingga current_period_end dan
// tidak dicaj pada pembaharuan; tiada refund (PELAN 18.10 vendor/addons/cancel).
class CancelModuleJob
{
    private $vendorId;
    private $cooperative;
    private $actorId;
    private $code;
    private $undo;

    public function __construct($vendorId, array $cooperative, $actorId, $code, $undo)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->actorId = (int) $actorId;
        $this->code = (string) $code;
        $this->undo = (bool) $undo;
    }

    public function run()
    {
        $subscription = AddonItems::requireSubscription($this->vendorId);
        $coopId = (int) $this->cooperative['id'];
        $module = Database::fetchOne("SELECT id, code FROM modules WHERE code = ?", [$this->code]);
        if ($module === null || !ModuleAccess::has($this->vendorId, $coopId, $module['code'])) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $flag = $this->undo ? 0 : 1;
        Database::beginTransaction();
        Database::execute(
            "UPDATE cooperative_modules SET cancel_at_period_end = ? WHERE vendor_id = ? AND cooperative_id = ? AND module_id = ?",
            [$flag, $this->vendorId, $coopId, (int) $module['id']]
        );
        Database::execute(
            "UPDATE subscription_items SET cancel_at_period_end = ?
              WHERE vendor_id = ? AND cooperative_id = ? AND item_type = 'module' AND ref_id = ? AND ended_at IS NULL",
            [$flag, $this->vendorId, $coopId, (int) $module['id']]
        );
        (new WriteActivityLogJob($this->vendorId, $coopId, $this->actorId, 'module_cancel', 'module', (int) $module['id'],
            Invoices::moduleName($module['code']) . ($this->undo ? ' ↺' : '')))->run();
        Database::commit();
        return [
            'code'                 => $module['code'],
            'cancel_at_period_end' => !$this->undo,
            'ends_at'              => $subscription['current_period_end'],
        ];
    }
}
