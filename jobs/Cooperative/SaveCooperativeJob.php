<?php
// Tambah / sunting koperasi vendor sesi (PELAN 5.2 #2, 18.8).
// Koperasi baharu: had pelan (PlanLimit) + harga koperasi tambahan direkod dalam subscription_items (snapshot, B12 bil).
// Slug unik seluruh platform; template mesti dilanggan; sekurang-kurangnya satu kaedah bayaran; logo = data URL → Uploader.
class SaveCooperativeJob
{
    private $vendorId;
    private $actorId;
    private $input;

    public function __construct($vendorId, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $current = $id > 0 ? $this->find($id) : null;
        if ($id > 0 && $current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($current);

        $oldLogo = $current['logo_path'] ?? null;
        $newLogo = $oldLogo;
        Database::beginTransaction();
        try {
            if ($current === null) {
                $id = $this->insert($data);
            } else {
                $this->update($id, $data);
            }
            CooperativeSettings::set($this->vendorId, $id, ['opening_hours' => json_encode($data['opening_hours'])]);
            $newLogo = $this->saveLogo($id, $oldLogo);
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            if ($newLogo !== $oldLogo) {
                Uploader::delete($newLogo);
            }
            // Perlumbaan slug: UNIQUE global menolak walaupun semakan awal lulus
            if ($exception instanceof PDOException && (int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new ValidationException(t('error.check_form'), ['slug' => t('coop.slug_taken')]);
            }
            throw $exception;
        }
        if ($oldLogo && $oldLogo !== $newLogo) {
            Uploader::delete($oldLogo);
        }

        (new WriteActivityLogJob($this->vendorId, $id, $this->actorId, 'cooperative_save', 'cooperative', $id, $data['school_name']))->run();
        return (new GetCooperativeJob($this->vendorId, $id))->run();
    }

    private function find($id)
    {
        return Database::fetchOne(
            "SELECT id, template_id, logo_path FROM cooperatives WHERE vendor_id = ? AND id = ?",
            [$this->vendorId, $id]
        );
    }

    private function validate($current)
    {
        $malaysia = require APP_ROOT . '/config/malaysia.php';
        $input = $this->input;
        $input['school_code'] = strtoupper(trim((string) ($input['school_code'] ?? '')));
        $templateId = (int) ($input['template_id'] ?? 0);
        $language = (string) ($input['default_language'] ?? 'ms');

        $v = new Validator($input);
        $v->required('school_name')->maxLength('school_name', 150)
          ->required('school_code')->pattern('school_code', '/^[A-Z]{3}[0-9]{4}$/')
          ->required('school_type')->inList('school_type', $malaysia['school_types'])
          ->required('state')->inList('state', $malaysia['states'])
          ->required('district')->maxLength('district', 80)
          ->required('address')->maxLength('address', 255)
          ->maxLength('phone', 20)
          ->required('slug')
          ->required('template_id');

        $slug = (new CheckSlugJob($this->vendorId, $input['slug'] ?? '', $current['id'] ?? 0))->run();
        if (($input['slug'] ?? '') !== '' && !$slug['valid']) {
            $v->addError('slug', t('coop.slug_format'));
        } elseif (($input['slug'] ?? '') !== '' && !$slug['available']) {
            $v->addError('slug', t('coop.slug_taken'));
        }
        // Template lama yang sudah tidak dilanggan kekal dibenarkan selagi tidak ditukar
        $templateUnchanged = $current !== null && (int) $current['template_id'] === $templateId;
        if ($templateId > 0 && !$templateUnchanged && !ListTemplateOptionsJob::isUsable($this->vendorId, $templateId)) {
            $v->addError('template_id', t('coop.template_not_subscribed'));
        }
        if (!Database::fetchValue("SELECT 1 FROM languages WHERE code = ? AND is_active = 1", [$language])) {
            $v->addError('default_language', t('error.invalid'));
        }
        $acceptCash = !empty($input['accept_cash']);
        $acceptOnline = !empty($input['accept_online']);
        if (!$acceptCash && !$acceptOnline) {
            $v->addError('accept_online', t('coop.payment_required'));
        }
        $hours = $this->validateHours($v, $malaysia['week_days']);
        ValidationException::throwIfFails($v);

        $phone = trim((string) ($input['phone'] ?? ''));
        return [
            'school_name'      => trim((string) $input['school_name']),
            'school_code'      => $input['school_code'],
            'school_type'      => $input['school_type'],
            'state'            => $input['state'],
            'district'         => trim((string) $input['district']),
            'address'          => trim((string) $input['address']),
            'phone'            => $phone === '' ? null : $phone,
            'slug'             => $slug['slug'],
            'template_id'      => $templateId,
            'default_language' => $language,
            'accept_cash'      => $acceptCash ? 1 : 0,
            'accept_online'    => $acceptOnline ? 1 : 0,
            'opening_hours'    => $hours,
        ];
    }

    // Hari buka: masa HH:MM, mula sebelum tamat. Hari tutup: masa dikosongkan
    private function validateHours(Validator $v, array $days)
    {
        $input = is_array($this->input['opening_hours'] ?? null) ? $this->input['opening_hours'] : [];
        $hours = GetCooperativeJob::normaliseHours($input);
        $timePattern = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';
        foreach ($days as $day) {
            if (!$hours[$day]['open']) {
                continue;
            }
            foreach (['from', 'to'] as $part) {
                if (!preg_match($timePattern, $hours[$day][$part])) {
                    $v->addError('opening_hours.' . $day . '.' . $part, t('error.invalid'));
                }
            }
            if ($hours[$day]['from'] !== '' && $hours[$day]['from'] >= $hours[$day]['to']) {
                $v->addError('opening_hours.' . $day . '.to', t('error.invalid'));
            }
        }
        return $hours;
    }

    private function insert($data)
    {
        // Kunci baris vendor: dua permintaan serentak tidak boleh melepasi had pelan bersama
        Database::fetchValue("SELECT id FROM vendors WHERE id = ? FOR UPDATE", [$this->vendorId]);
        if (!PlanLimit::canAddCooperative($this->vendorId)) {
            $plan = PlanLimit::plan($this->vendorId);
            throw new ValidationException(t('coop.limit_reached', ['max' => $plan['max_cooperatives'] ?? 0]));
        }
        $number = PlanLimit::cooperativeCount($this->vendorId) + 1;

        $id = Database::insert(
            "INSERT INTO cooperatives (vendor_id, slug, school_name, school_code, school_type, state, district, address, phone,
                                       template_id, default_language, accept_cash, accept_online, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [
                $this->vendorId, $data['slug'], $data['school_name'], $data['school_code'], $data['school_type'], $data['state'],
                $data['district'], $data['address'], $data['phone'], $data['template_id'], $data['default_language'],
                $data['accept_cash'], $data['accept_online'],
            ]
        );
        $vendorEmail = (string) Database::fetchValue("SELECT email FROM vendors WHERE id = ?", [$this->vendorId]);
        CooperativeSettings::set($this->vendorId, $id, CooperativeSettings::DEFAULTS + [
            'alert_emails'   => json_encode($vendorEmail !== '' ? [$vendorEmail] : []),
            'receipt_header' => $data['school_name'],
        ]);
        $this->chargeExtraCooperative($id, $number);
        return $id;
    }

    // Koperasi ke-2 dan seterusnya: harga peringkat disalin ke subscription_items (invois & prorata — B12)
    private function chargeExtraCooperative($cooperativeId, $number)
    {
        $plan = PlanLimit::plan($this->vendorId);
        if ($plan === null || $number < 2) {
            return;
        }
        $price = GetCooperativePricingJob::priceFor($plan['plan_id'], $number, $plan['billing_cycle']);
        Database::insert(
            "INSERT INTO subscription_items (vendor_id, subscription_id, item_type, cooperative_id, ref_id, unit_price, started_at)
             VALUES (?, ?, 'cooperative', ?, NULL, ?, CURDATE())",
            [$this->vendorId, $plan['subscription_id'], $cooperativeId, $price]
        );
    }

    private function update($id, $data)
    {
        Database::execute(
            "UPDATE cooperatives
                SET slug = ?, school_name = ?, school_code = ?, school_type = ?, state = ?, district = ?, address = ?, phone = ?,
                    template_id = ?, default_language = ?, accept_cash = ?, accept_online = ?
              WHERE vendor_id = ? AND id = ?",
            [
                $data['slug'], $data['school_name'], $data['school_code'], $data['school_type'], $data['state'], $data['district'],
                $data['address'], $data['phone'], $data['template_id'], $data['default_language'], $data['accept_cash'],
                $data['accept_online'], $this->vendorId, $id,
            ]
        );
    }

    // logo_url: data URL = gambar baharu · kosong = buang · URL sedia ada = kekal
    private function saveLogo($id, $oldLogo)
    {
        $value = $this->input['logo_url'] ?? null;
        if (Uploader::isDataUrl($value)) {
            $path = Uploader::saveImage($value, $this->vendorId . '/' . $id . '/logo', 'logo_url');
        } elseif ($value === null || $value === '') {
            $path = null;
        } else {
            return $oldLogo;
        }
        Database::execute("UPDATE cooperatives SET logo_path = ? WHERE vendor_id = ? AND id = ?", [$path, $this->vendorId, $id]);
        return $path;
    }
}
