<?php
// Draf invois pembaharuan untuk tempoh SETERUSNYA (snapshot RenewalLines) — dicipta oleh cron 7 hari sebelum
// current_period_end supaya owner boleh menyemak. Vendor tidak melihat draf. Pada tarikh pembaharuan RenewSubscriptionJob
// mengira semula baris draf (modul baharu / dihentikan dalam 7 hari itu) dan menerbitkannya. Satu invois pembaharuan setiap tempoh.
class GenerateInvoiceJob
{
    const LEAD_DAYS = 7;

    private $subscriptionId;

    public function __construct($subscriptionId)
    {
        $this->subscriptionId = (int) $subscriptionId;
    }

    // Pulangkan id draf baharu, atau null (sudah ada / tidak layak)
    public function run()
    {
        Database::beginTransaction();
        try {
            $result = $this->generate();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        return $result;
    }

    private function generate()
    {
        $peek = Subscriptions::find($this->subscriptionId);
        if ($peek === null) {
            return null;
        }
        Subscriptions::lockVendor($peek['vendor_id']);
        $sub = Subscriptions::find($this->subscriptionId, true);
        if (!in_array($sub['status'], Subscriptions::RENEWING_STATUSES, true)) {
            return null;
        }
        $vendorId = (int) $sub['vendor_id'];
        $nextStart = BillingPeriod::addDays($sub['current_period_end'], 1);
        if (self::renewalInvoice($vendorId, [$nextStart]) !== null) {
            return null;
        }
        $renewal = RenewalLines::build($sub, true);
        $nextEnd = BillingPeriod::endFor($nextStart, $renewal['billing_cycle']);
        return Invoices::create($vendorId, $nextStart, $nextEnd, RenewalLines::invoiceLines($renewal), 'draft');
    }

    // Invois pembaharuan (ada baris pelan) bermula pada salah satu tarikh — apa-apa status, termasuk void
    public static function renewalInvoice($vendorId, array $starts)
    {
        $marks = implode(',', array_fill(0, count($starts), '?'));
        return Database::fetchOne(
            "SELECT i.id, i.status FROM invoices i
              WHERE i.vendor_id = ? AND i.period_start IN ($marks)
                AND EXISTS (SELECT 1 FROM invoice_lines l WHERE l.invoice_id = i.id AND l.item_type = 'plan')
              ORDER BY i.id DESC LIMIT 1",
            array_merge([(int) $vendorId], $starts)
        );
    }
}
