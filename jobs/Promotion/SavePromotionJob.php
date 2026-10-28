<?php
// Tambah / sunting promosi (vendor/promotions/save — PELAN 18.10): kod ^[A-Z0-9-]{3,20}$ unik dalam koperasi (huruf besar,
// kosong = diskaun automatik); % 1–100 atau amaun tetap (sen) > 0; tamat ≥ mula; had guna kosong = tanpa had.
// Promosi dipakai oleh checkout store (CheckoutQuote) dan POS (PosCalculateCartJob) melalui Promotions::choose.
class SavePromotionJob
{
    const MAX_AMOUNT = 100000000;

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
        if ($id > 0 && Promotions::find($this->vendorId, $this->cooperativeId, $id) === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($id);
        $values = [$data['name'], $data['code'], $data['type'], $data['value'], $data['min_spend'], $data['starts_on'], $data['ends_on'],
            $data['max_uses'], $data['is_active']];
        if ($id > 0) {
            Database::execute(
                "UPDATE promotions SET name = ?, code = ?, type = ?, value = ?, min_spend = ?, starts_on = ?, ends_on = ?, max_uses = ?, is_active = ?
                  WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                array_merge($values, [$id, $this->vendorId, $this->cooperativeId])
            );
        } else {
            $id = Database::insert(
                "INSERT INTO promotions (vendor_id, cooperative_id, name, code, type, value, min_spend, starts_on, ends_on, max_uses, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array_merge([$this->vendorId, $this->cooperativeId], $values)
            );
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'promotion_save', 'promotion', $id,
            $data['code'] ?? $data['name']))->run();
        return Promotions::find($this->vendorId, $this->cooperativeId, $id);
    }

    private function validate($id)
    {
        $input = $this->input;
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $input['code'] = $code;
        $v = new Validator($input);
        $v->required('name')->maxLength('name', 100)
          ->required('type')->inList('type', ['percent', 'fixed'])
          ->required('value')
          ->required('starts_on')->date('starts_on')
          ->required('ends_on')->date('ends_on')
          ->integer('min_spend', 0, self::MAX_AMOUNT)
          ->integer('max_uses', 1, 1000000)
          ->pattern('code', '/^[A-Z0-9-]{3,20}$/', 'promo.code_format');
        $type = $input['type'] ?? '';
        if ($type === 'percent') {
            $v->integer('value', 1, 100);
        } elseif ($type === 'fixed') {
            $value = filter_var($input['value'] ?? null, FILTER_VALIDATE_INT);
            if ($value === false || $value < 1 || $value > self::MAX_AMOUNT) {
                $v->addError('value', t('promo.value_positive'));
            }
        }
        $starts = (string) ($input['starts_on'] ?? '');
        $ends = (string) ($input['ends_on'] ?? '');
        if ($starts !== '' && $ends !== '' && $ends < $starts) {
            $v->addError('ends_on', t('promo.end_before_start'));
        }
        if ($code !== '' && Database::fetchValue(
            "SELECT 1 FROM promotions WHERE vendor_id = ? AND cooperative_id = ? AND code = ? AND id <> ?",
            [$this->vendorId, $this->cooperativeId, $code, $id]
        )) {
            $v->addError('code', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        $maxUses = $input['max_uses'] ?? null;
        return [
            'name'      => trim((string) $input['name']),
            'code'      => $code === '' ? null : $code,
            'type'      => $type,
            'value'     => (int) $input['value'],
            'min_spend' => (int) ($input['min_spend'] ?? 0),
            'starts_on' => $starts,
            'ends_on'   => $ends,
            'max_uses'  => ($maxUses === null || $maxUses === '') ? null : (int) $maxUses,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
        ];
    }
}
