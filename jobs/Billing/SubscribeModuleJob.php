<?php
// Langgan modul add-on untuk satu koperasi (PELAN 5.3, 14): aktif SERTA-MERTA (cooperative_modules hingga hujung tempoh)
// + item langganan (harga kitaran, snapshot) + invois prorata `issued` (bayar dalam 7 hari). Trial → tiada invois.
// Satu transaksi; baris vendor dikunci (dua klik serentak tidak menggandakan langganan / invois).
class SubscribeModuleJob
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
        $item = AddonItems::resolve($this->vendorId, $this->cooperative, 'module', $this->code, $subscription['billing_cycle']);
        if ($item === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($item['owned']) {
            throw new ValidationException(t('addon.already_subscribed'));
        }
        $coopId = (int) $this->cooperative['id'];
        $today = BillingPeriod::today();
        Database::execute(
            "INSERT INTO cooperative_modules (vendor_id, cooperative_id, module_id, status, started_at, ends_at, cancel_at_period_end)
             VALUES (?, ?, ?, 'active', ?, ?, 0)
             ON DUPLICATE KEY UPDATE status = 'active', started_at = VALUES(started_at), ends_at = VALUES(ends_at), cancel_at_period_end = 0",
            [$this->vendorId, $coopId, $item['id'], $today, $subscription['current_period_end']]
        );
        // Item lama (berhenti tetapi belum tamat) ditutup dahulu — satu item terbuka setiap modul setiap koperasi
        Database::execute(
            "UPDATE subscription_items SET ended_at = ? WHERE vendor_id = ? AND cooperative_id = ? AND item_type = 'module' AND ref_id = ? AND ended_at IS NULL",
            [BillingPeriod::addDays($today, -1), $this->vendorId, $coopId, $item['id']]
        );
        Database::insert(
            "INSERT INTO subscription_items (vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at)
             VALUES (?, ?, 'module', ?, ?, ?, ?)",
            [$this->vendorId, (int) $subscription['id'], $coopId, $item['id'], $item['price'], $today]
        );

        $prorata = (new CalculateProrataJob($subscription, $item['price']))->run();
        $invoice = CalculateProrataJob::invoice($this->vendorId, $prorata, 'module', $coopId, Invoices::text('billing.line_module', [
            'name' => $item['name'], 'school' => $this->cooperative['school_name'], 'days' => $prorata['days_left'],
        ]));
        (new WriteActivityLogJob($this->vendorId, $coopId, $this->actorId, 'module_subscribe', 'module', $item['id'],
            $item['name'] . ($invoice ? ' · ' . $invoice['invoice_no'] : '')))->run();
        return self::result($invoice);
    }

    public static function result($invoice)
    {
        return [
            'invoice_id' => $invoice ? (int) $invoice['id'] : null,
            'invoice_no' => $invoice ? $invoice['invoice_no'] : null,
            'total'      => $invoice ? (int) $invoice['total'] : 0,
        ];
    }
}
