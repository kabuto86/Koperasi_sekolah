<?php
// Simpan tetapan koperasi (PELAN 18.10 vendor/settings/save) — pentadbir vendor sahaja.
// alert_emails 1–5 sah · pickup_methods ≥ 1 dari A–D (+E pilihan) · had kod (sen) · luput tunai 1–14 hari · cukai 0–10% ·
// stok minimum lalai ≥ 0 · bahasa aktif · resit (pengepala ≤ 80, pengaki ≤ 200, kertas 58/80).
class SaveCooperativeSettingsJob
{
    const MAX_ALERT_EMAILS = 5;

    private $cooperative;
    private $userId;
    private $input;

    public function __construct(array $cooperative, $userId, array $input)
    {
        $this->cooperative = $cooperative;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $errors = [];
        $emails = $this->alertEmails($errors);
        $methods = array_values(array_intersect(['A', 'B', 'C', 'D', 'E'], array_map('strval', (array) ($this->input['pickup_methods'] ?? []))));
        if (!array_diff($methods, ['E'])) {
            $errors['pickup_methods'] = t('settings_v.pickup_required');
        }
        $codeAbove = $this->integer('pickup_code_required_above', 0, 100000000, $errors, 0);
        $expiry = $this->integer('cash_order_expiry_days', 1, 14, $errors);
        $lowStock = $this->integer('low_stock_default', 0, 100000, $errors);
        $tax = $this->input['tax_rate'] ?? null;
        if (!is_numeric($tax) || $tax < 0 || $tax > 10 || abs(round($tax * 100) - $tax * 100) > 0.000001) {
            $errors['tax_rate'] = t('error.range', ['min' => 0, 'max' => 10]);
        }
        $header = trim((string) ($this->input['receipt_header'] ?? ''));
        $footer = trim((string) ($this->input['receipt_footer'] ?? ''));
        if (mb_strlen($header, 'UTF-8') > 80) {
            $errors['receipt_header'] = t('error.max_length', ['max' => 80]);
        }
        if (mb_strlen($footer, 'UTF-8') > 200) {
            $errors['receipt_footer'] = t('error.max_length', ['max' => 200]);
        }
        $paper = (string) ($this->input['receipt_paper'] ?? '80');
        if (!in_array($paper, ['58', '80'], true)) {
            $errors['receipt_paper'] = t('error.invalid');
        }
        $language = (string) ($this->input['default_language'] ?? '');
        if (Database::fetchValue("SELECT 1 FROM languages WHERE code = ? AND is_active = 1", [$language]) === null) {
            $errors['default_language'] = t('error.invalid');
        }
        if ($errors) {
            throw new ValidationException(t('error.check_form'), $errors);
        }

        $vendorId = (int) $this->cooperative['vendor_id'];
        $cooperativeId = (int) $this->cooperative['id'];
        Database::beginTransaction();
        try {
            CooperativeSettings::set($vendorId, $cooperativeId, [
                'alert_emails'               => json_encode($emails),
                'pickup_methods'             => implode(',', $methods),
                'pickup_code_required_above' => $codeAbove,
                'cash_order_expiry_days'     => $expiry,
                'low_stock_default'          => $lowStock,
                'tax_rate'                   => 0 + $tax,
                'receipt_header'             => $header,
                'receipt_footer'             => $footer,
                'receipt_paper'              => $paper,
                'receipt_show_logo'          => empty($this->input['receipt_show_logo']) ? '0' : '1',
            ]);
            Database::execute("UPDATE cooperatives SET default_language = ? WHERE id = ? AND vendor_id = ?", [$language, $cooperativeId, $vendorId]);
            (new WriteActivityLogJob($vendorId, $cooperativeId, $this->userId, 'settings_save', 'cooperative', $cooperativeId, ''))->run();
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        $this->cooperative['default_language'] = $language;
        return (new GetCooperativeSettingsJob($this->cooperative))->run();
    }

    private function alertEmails(array &$errors)
    {
        $list = $this->input['alert_emails'] ?? [];
        $list = is_array($list) ? $list : preg_split('/[\r\n,]+/', (string) $list);
        $emails = [];
        foreach ($list as $email) {
            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['alert_emails'] = t('settings_v.alert_emails_invalid');
            }
            if (!in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }
        if (!$emails) {
            $errors['alert_emails'] = t('error.required');
        } elseif (count($emails) > self::MAX_ALERT_EMAILS) {
            $errors['alert_emails'] = t('settings_v.alert_emails_max', ['max' => self::MAX_ALERT_EMAILS]);
        }
        return $emails;
    }

    private function integer($field, $min, $max, array &$errors, $default = null)
    {
        $value = $this->input[$field] ?? $default;
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < $min || $number > $max) {
            $errors[$field] = t('error.range', ['min' => $min, 'max' => $max]);
            return 0;
        }
        return $number;
    }
}
