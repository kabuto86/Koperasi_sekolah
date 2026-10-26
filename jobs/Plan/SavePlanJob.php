<?php
// Tambah / sunting pelan. Pelan baharu mendapat satu peringkat koperasi tambahan lalai
// (from_coop 2, tanpa had, harga sama seperti pelan — PELAN 9 B).
class SavePlanJob
{
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
        $code = strtoupper(trim((string) ($this->input['code'] ?? '')));
        $this->input['code'] = $code;

        $v = new Validator($this->input);
        $v->required('code')->pattern('code', '/^[A-Z0-9_]{2,30}$/')
          ->required('name')->maxLength('name', 100)
          ->required('price_monthly')->integer('price_monthly', 0, 100000000)
          ->required('price_yearly')->integer('price_yearly', 0, 1000000000)
          ->integer('max_cooperatives', 1, 100000)->integer('max_products', 1, 10000000)->integer('max_staff', 1, 100000)
          ->integer('sort_order', 0, 9999);
        if ($code !== '' && Database::fetchValue("SELECT 1 FROM plans WHERE code = ? AND id <> ?", [$code, $id])) {
            $v->addError('code', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        $values = [
            $code, trim($this->input['name']), (int) $this->input['price_monthly'], (int) $this->input['price_yearly'],
            $this->limit('max_cooperatives'), $this->limit('max_products'), $this->limit('max_staff'),
            json_encode($this->features(), JSON_UNESCAPED_UNICODE), !empty($this->input['is_active']) ? 1 : 0,
            (int) ($this->input['sort_order'] ?? 0),
        ];

        if ($id > 0) {
            if (ListPlansJob::find($id) === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            $values[] = $id;
            Database::execute(
                "UPDATE plans SET code = ?, name = ?, price_monthly = ?, price_yearly = ?, max_cooperatives = ?, max_products = ?,
                        max_staff = ?, features_json = ?, is_active = ?, sort_order = ? WHERE id = ?",
                $values
            );
        } else {
            Database::beginTransaction();
            $id = Database::insert(
                "INSERT INTO plans (code, name, price_monthly, price_yearly, max_cooperatives, max_products, max_staff, features_json, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                $values
            );
            Database::execute(
                "INSERT INTO plan_coop_tiers (plan_id, from_coop, to_coop, price_monthly, price_yearly) VALUES (?, 2, NULL, ?, ?)",
                [$id, $values[2], $values[3]]
            );
            Database::commit();
        }

        (new WriteAuditLogJob('owner', $this->actorId, null, 'plan.save', 'plan', $id, $code))->run();
        return ListPlansJob::find($id);
    }

    // Kosong / null = tanpa had
    private function limit($field)
    {
        $value = $this->input[$field] ?? null;
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function features()
    {
        $features = $this->input['features'] ?? [];
        $features = is_array($features) ? $features : [];
        $clean = [];
        foreach ($features as $feature) {
            $text = trim((string) $feature);
            if ($text !== '') {
                $clean[] = mb_substr($text, 0, 150, 'UTF-8');
            }
        }
        return array_slice($clean, 0, 30);
    }
}
