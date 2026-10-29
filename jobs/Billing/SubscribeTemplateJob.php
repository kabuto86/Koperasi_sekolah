<?php
// Langgan template premium untuk satu koperasi (PELAN 5.3, 14): item langganan (snapshot) + invois prorata `issued`.
// Template tidak ditukar automatik — vendor memilihnya di borang koperasi. Template yang tidak dipakai pada hujung tempoh berhenti.
class SubscribeTemplateJob
{
    private $vendorId;
    private $cooperative;
    private $actorId;
    private $code;

    public function __construct($vendorId, array $cooperative, $actorId, $code)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->actorId = (int) $actorId;
        $this->code = (string) $code;
    }

    public function run()
    {
        Database::beginTransaction();
        try {
            $result = $this->subscribe();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return $result;
    }

    private function subscribe()
    {
        Subscriptions::lockVendor($this->vendorId);
        $subscription = AddonItems::requireSubscription($this->vendorId, true);
        $item = AddonItems::resolve($this->vendorId, $this->cooperative, 'template', $this->code, $subscription['billing_cycle']);
        if ($item === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($item['owned']) {
            throw new ValidationException(t('addon.already_subscribed'));
        }
        $coopId = (int) $this->cooperative['id'];
        Database::insert(
            "INSERT INTO subscription_items (vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at)
             VALUES (?, ?, 'template', ?, ?, ?, CURDATE())",
            [$this->vendorId, (int) $subscription['id'], $coopId, $item['id'], $item['price']]
        );
        $prorata = (new CalculateProrataJob($subscription, $item['price']))->run();
        $invoice = CalculateProrataJob::invoice($this->vendorId, $prorata, 'template', $coopId, Invoices::text('billing.line_template', [
            'name' => $item['name'], 'school' => $this->cooperative['school_name'], 'days' => $prorata['days_left'],
        ]));
        (new WriteActivityLogJob($this->vendorId, $coopId, $this->actorId, 'template_subscribe', 'template', $item['id'],
            $item['name'] . ($invoice ? ' · ' . $invoice['invoice_no'] : '')))->run();
        return SubscribeModuleJob::result($invoice);
    }
}
