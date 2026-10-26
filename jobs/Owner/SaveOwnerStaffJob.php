<?php
// Tambah / sunting kakitangan owner. Kakitangan baharu menerima e-mel jemputan (7 hari) untuk
// menetapkan kata laluan; 2FA disediakan semasa log masuk pertama.
class SaveOwnerStaffJob
{
    const INVITE_TTL = 604800;

    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $email = strtolower(trim((string) ($this->input['email'] ?? '')));
        $role = (string) ($this->input['role'] ?? '');

        $v = new Validator($this->input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('email')->email('email')->maxLength('email', 191)
          ->required('role')->inList('role', ['owner', 'owner_staff']);
        if ($email !== '' && Database::fetchValue("SELECT 1 FROM platform_users WHERE email = ? AND id <> ?", [$email, $id])) {
            $v->addError('email', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        if ($id > 0) {
            return $this->update($id, $email, $role);
        }
        return $this->create($email, $role);
    }

    private function update($id, $email, $role)
    {
        $current = ListOwnerStaffJob::find($id);
        if ($current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($id === $this->actorId && $role !== $current['role']) {
            throw new ValidationException(t('staff.cannot_demote_self'), ['role' => t('staff.cannot_demote_self')]);
        }
        Database::execute(
            "UPDATE platform_users SET full_name = ?, email = ?, role = ? WHERE id = ?",
            [trim($this->input['full_name']), $email, $role, $id]
        );
        // Tukar peranan → pengecualian kebenaran dibuang, kembali ke lalai peranan baharu (PELAN 4.1)
        if ($role !== $current['role']) {
            Database::execute("DELETE FROM platform_user_permissions WHERE platform_user_id = ?", [$id]);
        }
        (new WriteAuditLogJob('owner', $this->actorId, null, 'staff.save', 'platform_user', $id, $email))->run();
        return ListOwnerStaffJob::find($id);
    }

    private function create($email, $role)
    {
        // Kata laluan rawak yang tidak diketahui sesiapa — kakitangan menetapkan sendiri melalui jemputan
        $id = Database::insert(
            "INSERT INTO platform_users (email, password_hash, full_name, role, language) VALUES (?, ?, ?, ?, ?)",
            [$email, Password::hash(Crypto::randomToken(32)), trim($this->input['full_name']), $role, Config::get('default_language')]
        );
        $token = AuthToken::issue('owner', $id, 'invite', self::INVITE_TTL);
        (new QueueEmailJob(null, $email, 'staff_invite', Config::get('default_language'), [
            'name' => trim($this->input['full_name']),
            'link' => View::absoluteUrl('owner/reset-password.php?token=' . $token),
        ]))->run();
        (new WriteAuditLogJob('owner', $this->actorId, null, 'staff.create', 'platform_user', $id, $email))->run();
        return ListOwnerStaffJob::find($id);
    }
}
