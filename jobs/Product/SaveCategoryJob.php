<?php
// Tambah / sunting kategori { id?, name, sort_order, is_active } — nama unik dalam koperasi.
class SaveCategoryJob
{
    private $vendorId;
    private $cooperativeId;
    private $input;

    public function __construct($vendorId, $cooperativeId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        if ($id > 0 && ListCategoriesJob::find($this->vendorId, $this->cooperativeId, $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $name = trim((string) ($this->input['name'] ?? ''));
        $v = new Validator($this->input);
        $v->required('name')->maxLength('name', 100)->integer('sort_order', 0, 9999);
        $clash = Database::fetchValue(
            "SELECT 1 FROM categories WHERE vendor_id = ? AND cooperative_id = ? AND name = ? AND id <> ?",
            [$this->vendorId, $this->cooperativeId, $name, $id]
        );
        if ($name !== '' && $clash) {
            $v->addError('name', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        $sort = (int) ($this->input['sort_order'] ?? 0);
        $active = !empty($this->input['is_active']) ? 1 : 0;
        if ($id > 0) {
            Database::execute(
                "UPDATE categories SET name = ?, sort_order = ?, is_active = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                [$name, $sort, $active, $this->vendorId, $this->cooperativeId, $id]
            );
        } else {
            $id = Database::insert(
                "INSERT INTO categories (vendor_id, cooperative_id, name, sort_order, is_active) VALUES (?, ?, ?, ?, ?)",
                [$this->vendorId, $this->cooperativeId, $name, $sort, $active]
            );
        }
        return ListCategoriesJob::find($this->vendorId, $this->cooperativeId, $id);
    }
}
