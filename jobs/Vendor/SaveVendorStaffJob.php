<?php
// Tambah / sunting kakitangan vendor sesi. E-mel unik seluruh platform (mesej umum).
// Koperasi yang diberi mesti milik vendor ini. Kakitangan baharu: e-mel jemputan (7 hari) + had pelan.
class SaveVendorStaffJob
{
    const INVITE_TTL = 604800;

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
        $current = $id > 0 ? ListVendorStaffJob::find($this->vendorId, $id, $this->actorId) : null;
        if ($id > 0 && $current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($id);

        if ($current !== null && $current['is_self'] && $data['role'] !== $current['role']) {
            throw new ValidationException(t('staff.cannot_demote_self'), ['role' => t('staff.cannot_demote_self')]);
        }
        if ($current === null) {
            $limit = ListVendorStaffJob::limit($this->vendorId);
            if ($limit['max'] !== null && $limit['used'] >= $limit['max']) {
                throw new ValidationException(t('staff_v.limit_reached', ['max' => $limit['max']]));
            }
        }

        Database::beginTransaction();
        try {
            $id = $current === null ? $this->insert($data) : $this->update($id, $data);
            $this->saveCooperatives($id, $data['cooperative_ids']);
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        if ($current === null) {
            $this->sendInvite($id, $data);
        }
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'staff_save', 'vendor_user', $id, $data['full_name']))->run();
        return ListVendorStaffJob::find($this->vendorId, $id, $this->actorId);
    }

    private function validate($id)
    {
        $email = strtolower(trim((string) ($this->input['email'] ?? '')));
        $role = (string) ($this->input['role'] ?? '');
        $pin = trim((string) ($this->input['pin'] ?? ''));
        $coopIds = array_values(array_unique(array_map('intval', is_array($this->input['cooperative_ids'] ?? null) ? $this->input['cooperative_ids'] : [])));

        $v = new Validator($this->input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('email')->email('email')->maxLength('email', 191)
          ->maxLength('phone', 20)
          ->required('role')->inList('role', VendorAuth::ALL_ROLES);
        if ($email !== '' && Database::fetchValue("SELECT 1 FROM vendor_users WHERE email = ? AND id <> ?", [$email, $id])) {
            $v->addError('email', t('error.duplicate'));
        }
        foreach ($coopIds as $coopId) {
            if ((new ValidateCooperativeJob($this->vendorId, $coopId))->run() === null) {
                $v->addError('cooperative_ids', t('error.invalid'));
            }
        }
        if ($role !== '' && $role !== 'vendor_admin' && !$coopIds) {
            $v->addError('cooperative_ids', t('staff_v.coop_required'));
        }
        if ($pin !== '' && !preg_match('/^[0-9]{4,6}$/', $pin)) {
            $v->addError('pin', t('staff_v.pin_format'));
        }
        ValidationException::throwIfFails($v);

        return [
            'full_name'       => trim((string) $this->input['full_name']),
            'email'           => $email,
            'phone'           => trim((string) ($this->input['phone'] ?? '')),
            'role'            => $role,
            'pin'             => $pin,
            'cooperative_ids' => $role === 'vendor_admin' ? [] : $coopIds,
        ];
    }

    private function insert($data)
    {
        return Database::insert(
            "INSERT INTO vendor_users (vendor_id, email, password_hash, full_name, phone, role, pin_hash, language)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->vendorId, $data['email'], Password::hash(Crypto::randomToken(32)), $data['full_name'],
                $data['phone'] === '' ? null : $data['phone'], $data['role'],
                $data['pin'] === '' ? null : Password::hash($data['pin']), Config::get('default_language'),
            ]
        );
    }

    private function update($id, $data)
    {
        Database::execute(
            "UPDATE vendor_users SET full_name = ?, email = ?, phone = ?, role = ? WHERE id = ? AND vendor_id = ?",
            [$data['full_name'], $data['email'], $data['phone'] === '' ? null : $data['phone'], $data['role'], $id, $this->vendorId]
        );
        // PIN baharu = buka kunci PIN juga (PosPinLoginJob)
        if ($data['pin'] !== '') {
            Database::execute(
                "UPDATE vendor_users SET pin_hash = ?, pin_failed_attempts = 0 WHERE id = ? AND vendor_id = ?",
                [Password::hash($data['pin']), $id, $this->vendorId]
            );
        }
        return $id;
    }

    private function saveCooperatives($userId, $coopIds)
    {
        Database::execute("DELETE FROM vendor_user_cooperatives WHERE vendor_id = ? AND vendor_user_id = ?", [$this->vendorId, $userId]);
        foreach ($coopIds as $coopId) {
            Database::insert(
                "INSERT INTO vendor_user_cooperatives (vendor_id, vendor_user_id, cooperative_id) VALUES (?, ?, ?)",
                [$this->vendorId, $userId, $coopId]
            );
        }
    }

    private function sendInvite($userId, $data)
    {
        $token = AuthToken::issue('vendor', $userId, 'invite', self::INVITE_TTL, $this->vendorId);
        (new QueueEmailJob($this->vendorId, $data['email'], 'staff_invite', Config::get('default_language'), [
            'name' => $data['full_name'],
            'link' => View::absoluteUrl('vendor/reset-password.php?token=' . $token),
        ]))->run();
    }
}
