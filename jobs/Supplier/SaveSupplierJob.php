<?php
// Tambah / sunting pembekal vendor sesi { id?, name, contact_name, phone, email, address, is_active }.
class SaveSupplierJob
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
        if ($id > 0 && ListSuppliersJob::find($this->vendorId, $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('name')->maxLength('name', 150)->maxLength('contact_name', 150)
          ->required('phone')->maxLength('phone', 20)
          ->email('email')->maxLength('email', 191)->maxLength('address', 255);
        ValidationException::throwIfFails($v);

        $values = [
            trim((string) $this->input['name']),
            $this->nullable('contact_name'),
            trim((string) $this->input['phone']),
            $this->nullableLower('email'),
            $this->nullable('address'),
            !empty($this->input['is_active']) ? 1 : 0,
        ];
        if ($id > 0) {
            Database::execute(
                "UPDATE suppliers SET name = ?, contact_name = ?, phone = ?, email = ?, address = ?, is_active = ? WHERE vendor_id = ? AND id = ?",
                array_merge($values, [$this->vendorId, $id])
            );
        } else {
            $id = Database::insert(
                "INSERT INTO suppliers (name, contact_name, phone, email, address, is_active, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?)",
                array_merge($values, [$this->vendorId])
            );
        }
        (new WriteActivityLogJob($this->vendorId, null, $this->actorId, 'supplier_save', 'supplier', $id, $values[0]))->run();
        return ListSuppliersJob::find($this->vendorId, $id);
    }

    private function nullable($field)
    {
        $value = trim((string) ($this->input[$field] ?? ''));
        return $value === '' ? null : $value;
    }

    private function nullableLower($field)
    {
        $value = $this->nullable($field);
        return $value === null ? null : strtolower($value);
    }
}
