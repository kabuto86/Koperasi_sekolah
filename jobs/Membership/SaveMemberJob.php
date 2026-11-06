<?php
// Tambah / sunting ahli koperasi. No. ahli dijana pelayan (A0001…) semasa tambah dan tidak boleh diubah.
// Tiada no. IC (PDPA). Kelas hanya untuk murid.
class SaveMemberJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $data = $this->validate();
        $id = (int) ($this->input['id'] ?? 0);
        Database::beginTransaction();
        try {
            if ($id > 0) {
                if (Membership::find($this->vendorId, $this->cooperativeId, $id, true) === null) {
                    throw new ValidationException(t('error.not_found'), [], 404);
                }
                Database::execute(
                    "UPDATE membership_members SET full_name = ?, member_type = ?, class_name = ?, phone = ?, email = ?, joined_on = ?, status = ?, note = ?
                      WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                    array_merge(array_values($data), [$id, $this->vendorId, $this->cooperativeId])
                );
            } else {
                Membership::lockCooperative($this->vendorId, $this->cooperativeId);
                $id = Database::insert(
                    "INSERT INTO membership_members (vendor_id, cooperative_id, member_no, full_name, member_type, class_name, phone, email, joined_on, status, note)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array_merge([$this->vendorId, $this->cooperativeId, Membership::nextNumber($this->vendorId, $this->cooperativeId)], array_values($data))
                );
            }
            Database::commit();
        } catch (Throwable $error) {
            Database::rollBack();
            throw $error;
        }
        $member = Membership::find($this->vendorId, $this->cooperativeId, $id);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'membership_member_save', 'membership_member', $id,
            $member['member_no'] . ' · ' . $member['full_name']))->run();
        return Membership::shape($member, Membership::balance($this->vendorId, $id));
    }

    private function validate()
    {
        $input = $this->input;
        $v = new Validator($input);
        $v->required('full_name')->maxLength('full_name', 150)
          ->required('member_type')->inList('member_type', Membership::TYPES)
          ->maxLength('class_name', 50)->maxLength('phone', 20)->maxLength('email', 191)->maxLength('note', 255)
          ->required('joined_on')->date('joined_on')
          ->required('status')->inList('status', Membership::STATUSES);
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if ($email !== '') {
            $v->email('email');
        }
        if (!$v->fails() && $input['joined_on'] > date('Y-m-d')) {
            $v->addError('joined_on', t('membership.date_future'));
        }
        ValidationException::throwIfFails($v);
        $type = $input['member_type'];
        $nullable = function ($value) {
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        };
        return [
            'full_name'   => trim($input['full_name']),
            'member_type' => $type,
            'class_name'  => $type === 'student' ? $nullable($input['class_name'] ?? '') : null,
            'phone'       => $nullable($input['phone'] ?? ''),
            'email'       => $email === '' ? null : $email,
            'joined_on'   => $input['joined_on'],
            'status'      => $input['status'],
            'note'        => $nullable($input['note'] ?? ''),
        ];
    }
}
