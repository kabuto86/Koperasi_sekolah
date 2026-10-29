<?php
// Baris pembaharuan langganan vendor (PELAN 14) — dipakai oleh anggaran di vendor/subscription/get, draf invois
// pembaharuan (GenerateInvoiceJob) dan pusingan tempoh (RenewSubscriptionJob), supaya ketiga-tiganya sama.
//
// Sumber: pelan (harga semasa) + subscription_items yang masih terbuka (ended_at NULL, harga snapshot).
// Tukar pelan / kitaran yang dijadualkan ($applyPending) → semua item dihargakan semula ikut katalog & peringkat kitaran baharu.
// Baris `cancelling` (modul dihentikan, template tidak lagi digunakan, koperasi ditutup) dipapar tetapi tidak dicaj.
class RenewalLines
{
    // ['plan' => baris plans, 'billing_cycle', 'changed', 'lines' => [...], 'subtotal']
    public static function build(array $subscription, $applyPending)
    {
        $vendorId = (int) $subscription['vendor_id'];
        $planId = (int) $subscription['plan_id'];
        $cycle = $subscription['billing_cycle'];
        if ($applyPending && $subscription['pending_plan_id'] !== null) {
            $planId = (int) $subscription['pending_plan_id'];
            $cycle = $subscription['pending_billing_cycle'] ?: $cycle;
        }
        $changed = $planId !== (int) $subscription['plan_id'] || $cycle !== $subscription['billing_cycle'];
        $plan = Subscriptions::plan($planId);

        $lines = [[
            'type'             => 'plan',
            'item_type'        => 'plan',
            'item_id'          => null,
            'label'            => $plan['name'],
            'cooperative_id'   => null,
            'cooperative_name' => null,
            'price'            => Subscriptions::cyclePrice($plan, $cycle),
            'cancelling'       => false,
            'description'      => Invoices::text('billing.line_plan', [
                'name' => $plan['name'], 'cycle' => Invoices::text('billing.cycle.' . $cycle),
            ]),
        ]];

        $coops = Database::fetchAll(
            "SELECT id, school_name, template_id, status FROM cooperatives WHERE vendor_id = ? ORDER BY id",
            [$vendorId]
        );
        $byId = [];
        $number = 0;
        foreach ($coops as $coop) {
            $coop['number'] = $coop['status'] === 'closed' ? null : ++$number;
            $byId[(int) $coop['id']] = $coop;
        }
        $items = self::openItems($vendorId);

        foreach ($byId as $coopId => $coop) {
            if ($coop['number'] === null || $coop['number'] < 2) {
                continue;
            }
            $item = $items['cooperative'][$coopId][0] ?? null;
            $price = $changed || $item === null
                ? GetCooperativePricingJob::priceFor($planId, $coop['number'], $cycle) : (int) $item['unit_price'];
            $lines[] = self::line('cooperative', $item, $coop['school_name'], $coop, $price, false,
                Invoices::text('billing.renewal_coop', ['school' => $coop['school_name']]));
        }
        foreach ($items['cooperative'] as $coopId => $rows) {
            // Item koperasi yang sudah ditutup / kini koperasi pertama → ditamatkan pada pusingan
            $coop = $byId[$coopId] ?? null;
            if ($coop === null || $coop['number'] === null || $coop['number'] < 2) {
                $lines[] = self::line('cooperative', $rows[0], $coop['school_name'] ?? '', $coop, 0, true, '');
            }
        }

        foreach ($items['module'] as $coopId => $rows) {
            foreach ($rows as $item) {
                $coop = $byId[$coopId] ?? null;
                $cancelling = (int) $item['cancel_at_period_end'] === 1 || (int) $item['module_active'] !== 1
                    || $coop === null || $coop['number'] === null;
                $price = $changed ? Subscriptions::cyclePrice($item, $cycle) : (int) $item['unit_price'];
                $lines[] = self::line('module', $item, $item['code'], $coop, $price, $cancelling,
                    Invoices::text('billing.renewal_module', ['name' => Invoices::moduleName($item['code']), 'school' => $coop['school_name'] ?? '']));
            }
        }
        foreach ($items['template'] as $coopId => $rows) {
            foreach ($rows as $item) {
                $coop = $byId[$coopId] ?? null;
                // Template premium yang tidak lagi dipakai oleh koperasi itu berhenti pada hujung tempoh
                $cancelling = $coop === null || $coop['number'] === null || (int) $coop['template_id'] !== (int) $item['ref_id']
                    || (int) $item['is_free'] === 1;
                $price = $changed ? Subscriptions::cyclePrice($item, $cycle) : (int) $item['unit_price'];
                $lines[] = self::line('template', $item, $item['name'], $coop, $price, $cancelling,
                    Invoices::text('billing.renewal_template', ['name' => $item['name'], 'school' => $coop['school_name'] ?? '']));
            }
        }

        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += $line['cancelling'] ? 0 : $line['price'];
        }
        return ['plan' => $plan, 'billing_cycle' => $cycle, 'changed' => $changed, 'lines' => $lines, 'subtotal' => $subtotal];
    }

    // Baris invois dari baris pembaharuan (tidak termasuk yang berhenti)
    public static function invoiceLines(array $renewal)
    {
        $result = [];
        foreach ($renewal['lines'] as $line) {
            if ($line['cancelling']) {
                continue;
            }
            $result[] = [
                'description'    => $line['description'],
                'item_type'      => $line['item_type'],
                'cooperative_id' => $line['cooperative_id'],
                'qty'            => 1,
                'unit_price'     => $line['price'],
                'amount'         => $line['price'],
                'is_prorated'    => false,
            ];
        }
        return $result;
    }

    // Bentuk anggaran untuk UI (PELAN 18.10 renewal.lines)
    public static function publicLines(array $renewal)
    {
        $result = [];
        foreach ($renewal['lines'] as $line) {
            if ($line['cancelling'] && $line['item_type'] === 'cooperative') {
                continue;
            }
            $result[] = [
                'type'             => $line['type'],
                'label'            => $line['label'],
                'cooperative_name' => $line['cooperative_name'],
                'price'            => $line['price'],
                'cancelling'       => $line['cancelling'],
            ];
        }
        return $result;
    }

    // Item terbuka vendor dikumpul ikut jenis → koperasi
    private static function openItems($vendorId)
    {
        $rows = Database::fetchAll(
            "SELECT si.*, m.code, m.is_active AS module_active,
                    COALESCE(m.price_monthly, t.price_monthly, 0) AS price_monthly, COALESCE(m.price_yearly, t.price_yearly, 0) AS price_yearly,
                    t.name, t.is_free
               FROM subscription_items si
               LEFT JOIN modules m ON si.item_type = 'module' AND m.id = si.ref_id
               LEFT JOIN templates t ON si.item_type = 'template' AND t.id = si.ref_id
              WHERE si.vendor_id = ? AND si.ended_at IS NULL
              ORDER BY si.item_type, si.cooperative_id, m.sort_order, si.id",
            [(int) $vendorId]
        );
        $grouped = ['cooperative' => [], 'module' => [], 'template' => []];
        foreach ($rows as $row) {
            $grouped[$row['item_type']][(int) $row['cooperative_id']][] = $row;
        }
        return $grouped;
    }

    private static function line($type, $item, $label, $coop, $price, $cancelling, $description)
    {
        return [
            'type'             => $type,
            'item_type'        => $type,
            'item_id'          => $item === null ? null : (int) $item['id'],
            'label'            => (string) $label,
            'cooperative_id'   => $coop === null ? ($item === null ? null : (int) $item['cooperative_id']) : (int) $coop['id'],
            'cooperative_name' => $coop === null ? null : $coop['school_name'],
            'price'            => (int) $price,
            'cancelling'       => (bool) $cancelling,
            'description'      => $description,
        ];
    }
}
