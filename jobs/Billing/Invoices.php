<?php
// Pembantu invois langganan (PELAN 14). Invois ialah SNAPSHOT: penerangan, harga & kadar SST disalin semasa dicipta —
// perubahan harga selepas itu tidak mengubah invois lama. draft → issued → paid (atau void).
//
//   $id = Invoices::create($vendorId, '2026-11-11', '2026-12-10', [ ['description' => ..., 'item_type' => 'plan', 'amount' => 4900] ], 'issued');
class Invoices
{
    // Tempoh bayar selepas invois diterbitkan (hari). Tempoh tangguh selepas tarikh akhir = platform_settings grace_days
    const DUE_DAYS = 7;

    // Cipta invois + baris (mesti dalam transaksi pemanggil). $status 'draft' atau 'issued'
    public static function create($vendorId, $periodStart, $periodEnd, array $lines, $status)
    {
        $vendorId = (int) $vendorId;
        $subtotal = array_sum(array_map(function ($line) { return (int) $line['amount']; }, $lines));
        $rate = Subscriptions::sstRate();
        $tax = Subscriptions::tax($subtotal, $rate);
        $id = Database::insert(
            "INSERT INTO invoices (vendor_id, invoice_no, period_start, period_end, subtotal, sst_rate, tax, total, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')",
            [$vendorId, self::nextNumber(), $periodStart, $periodEnd, $subtotal, $rate, $tax, $subtotal + $tax]
        );
        self::insertLines($id, $vendorId, $lines);
        if ($status === 'issued') {
            self::issue($id);
        }
        return $id;
    }

    // Ganti baris draf (pembaharuan dikira semula semasa diterbitkan) — draf sahaja
    public static function replaceLines($invoiceId, $vendorId, array $lines)
    {
        $subtotal = array_sum(array_map(function ($line) { return (int) $line['amount']; }, $lines));
        $rate = Subscriptions::sstRate();
        $tax = Subscriptions::tax($subtotal, $rate);
        Database::execute("DELETE FROM invoice_lines WHERE invoice_id = ? AND vendor_id = ?", [(int) $invoiceId, (int) $vendorId]);
        self::insertLines($invoiceId, $vendorId, $lines);
        Database::execute(
            "UPDATE invoices SET subtotal = ?, sst_rate = ?, tax = ?, total = ? WHERE id = ? AND vendor_id = ? AND status = 'draft'",
            [$subtotal, $rate, $tax, $subtotal + $tax, (int) $invoiceId, (int) $vendorId]
        );
    }

    private static function insertLines($invoiceId, $vendorId, array $lines)
    {
        foreach ($lines as $line) {
            Database::insert(
                "INSERT INTO invoice_lines (invoice_id, vendor_id, description, item_type, cooperative_id, qty, unit_price, amount, is_prorated)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    (int) $invoiceId, (int) $vendorId, mb_substr($line['description'], 0, 255, 'UTF-8'), $line['item_type'],
                    $line['cooperative_id'] ?? null, (int) ($line['qty'] ?? 1), (int) ($line['unit_price'] ?? $line['amount']),
                    (int) $line['amount'], empty($line['is_prorated']) ? 0 : 1,
                ]
            );
        }
    }

    // draft → issued: tarikh akhir = hari ini + DUE_DAYS (23:59:59), e-mel invoice_issued ke vendor (queue)
    public static function issue($invoiceId)
    {
        $due = BillingPeriod::addDays(BillingPeriod::today(), self::DUE_DAYS) . ' 23:59:59';
        $count = Database::execute(
            "UPDATE invoices SET status = 'issued', issued_at = NOW(), due_at = ? WHERE id = ? AND status = 'draft'",
            [$due, (int) $invoiceId]
        );
        if ($count === 1) {
            self::queueEmail((int) $invoiceId);
        }
        return $count === 1;
    }

    private static function queueEmail($invoiceId)
    {
        $row = Database::fetchOne(
            "SELECT i.vendor_id, i.invoice_no, i.total, i.due_at, v.company_name, v.email
               FROM invoices i JOIN vendors v ON v.id = i.vendor_id WHERE i.id = ?",
            [$invoiceId]
        );
        if ($row === null || $row['email'] === '') {
            return;
        }
        (new QueueEmailJob((int) $row['vendor_id'], $row['email'], 'invoice_issued', Config::get('default_language'), [
            'invoice_no'  => $row['invoice_no'],
            'vendor_name' => $row['company_name'],
            'total'       => Money::format((int) $row['total']),
            'due_date'    => date('d/m/Y', strtotime($row['due_at'])),
            'pay_url'     => View::absoluteUrl('vendor/invoices.php') . '?status=issued',
        ]))->run();
    }

    // INV-{tahun}-{0001}: pembilang atom dalam platform_settings (invoice_seq_{tahun}). Baris dikunci hingga commit —
    // dua invois serentak tidak mendapat nombor sama; rollback mengembalikan pembilang
    public static function nextNumber()
    {
        $year = date('Y');
        Database::execute(
            "INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE setting_value = LAST_INSERT_ID(setting_value + 1)",
            ['invoice_seq_' . $year]
        );
        $seq = (int) Database::fetchValue("SELECT LAST_INSERT_ID()");
        return sprintf('INV-%s-%04d', $year, $seq);
    }

    // Invois + vendor. $vendorId null = owner (semua vendor); selain itu ditapis vendor sesi
    public static function find($invoiceId, $vendorId = null, $lock = false)
    {
        $sql = "SELECT i.*, v.code AS vendor_code, v.company_name AS vendor_name, v.registration_no, v.address, v.email
                  FROM invoices i JOIN vendors v ON v.id = i.vendor_id WHERE i.id = ?";
        $params = [(int) $invoiceId];
        if ($vendorId !== null) {
            $sql .= " AND i.vendor_id = ?";
            $params[] = (int) $vendorId;
        }
        return Database::fetchOne($sql . ($lock ? ' FOR UPDATE' : ''), $params);
    }

    // InvoiceSummary (PELAN 18.7)
    public static function summary(array $row)
    {
        return [
            'id'           => (int) $row['id'],
            'invoice_no'   => $row['invoice_no'],
            'vendor_id'    => (int) $row['vendor_id'],
            'vendor_code'  => $row['vendor_code'],
            'vendor_name'  => $row['vendor_name'],
            'period_start' => $row['period_start'],
            'period_end'   => $row['period_end'],
            'subtotal'     => (int) $row['subtotal'],
            'tax'          => (int) $row['tax'],
            'total'        => (int) $row['total'],
            'status'       => $row['status'],
            'issued_at'    => IsoDate::format($row['issued_at']),
            'due_at'       => IsoDate::format($row['due_at']),
            'paid_at'      => IsoDate::format($row['paid_at']),
        ];
    }

    // InvoiceDetail: + lines, payments (diterima sahaja), sst_rate (snapshot invois)
    public static function detail(array $row)
    {
        $data = self::summary($row);
        $data['sst_rate'] = (float) $row['sst_rate'];
        $data['lines'] = array_map(function ($line) {
            return [
                'description' => $line['description'],
                'item_type'   => $line['item_type'],
                'qty'         => (int) $line['qty'],
                'unit_price'  => (int) $line['unit_price'],
                'amount'      => (int) $line['amount'],
                'is_prorated' => (bool) $line['is_prorated'],
            ];
        }, Database::fetchAll("SELECT * FROM invoice_lines WHERE invoice_id = ? AND vendor_id = ? ORDER BY id",
            [(int) $row['id'], (int) $row['vendor_id']]));
        $data['payments'] = array_map(function ($payment) {
            return [
                'method'       => $payment['method'],
                'gateway_code' => $payment['gateway_code'],
                'reference'    => (string) $payment['gateway_ref'],
                'amount'       => (int) $payment['amount'],
                'paid_at'      => IsoDate::format($payment['paid_at']),
                'note'         => (string) $payment['note'],
            ];
        }, Database::fetchAll("SELECT * FROM platform_payments WHERE invoice_id = ? AND vendor_id = ? AND status = 'paid' ORDER BY id",
            [(int) $row['id'], (int) $row['vendor_id']]));
        return $data;
    }

    // Teks baris invois dalam bahasa lalai platform (dokumen tetap — tidak ikut bahasa pelayar)
    public static function text($key, array $vars = [])
    {
        return Lang::getFor(Config::get('default_language'), $key, $vars);
    }

    // Nama modul (nilai owner dalam translations mengatasi lang/*.php)
    public static function moduleName($code)
    {
        $values = TranslationValues::get('module.' . $code);
        $name = $values[Config::get('default_language')] ?? '';
        return $name !== '' ? $name : (string) $code;
    }
}
