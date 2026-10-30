<?php
// Tambah / sunting jualan murah (vendor/clearance/save): starts_at = starts_on 00:00:00, ends_at = ends_on 23:59:59; tamat ≥ mula.
class SaveClearanceSaleJob
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
        $id = (int) ($this->input['id'] ?? 0);
        if ($id > 0 && ClearanceSales::find($this->vendorId, $this->cooperativeId, $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('name')->maxLength('name', 150)
          ->required('starts_on')->date('starts_on')
          ->required('ends_on')->date('ends_on');
        $starts = trim((string) ($this->input['starts_on'] ?? ''));
        $ends = trim((string) ($this->input['ends_on'] ?? ''));
        if (!$v->fails() && $ends < $starts) {
            $v->addError('ends_on', t('addon.end_before_start'));
        }
        ValidationException::throwIfFails($v);

        $name = trim((string) $this->input['name']);
        $values = [$name, $starts . ' 00:00:00', $ends . ' 23:59:59', !empty($this->input['is_active']) ? 1 : 0];
        if ($id > 0) {
            Database::execute(
                "UPDATE clearance_sales SET name = ?, starts_at = ?, ends_at = ?, is_active = ? WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                array_merge($values, [$id, $this->vendorId, $this->cooperativeId])
            );
        } else {
            $id = Database::insert(
                "INSERT INTO clearance_sales (vendor_id, cooperative_id, name, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                array_merge([$this->vendorId, $this->cooperativeId], $values)
            );
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'clearance_save', 'clearance_sale', $id, $name))->run();
        return ClearanceSales::shape(ClearanceSales::find($this->vendorId, $this->cooperativeId, $id));
    }
}
