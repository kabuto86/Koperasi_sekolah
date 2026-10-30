<?php
// Tambah / sunting modul add-on. Kod tidak boleh ditukar selepas dicipta (dirujuk oleh kod modul & ModuleAccess).
// Harga baharu hanya untuk langganan baharu — subscription_items menyimpan snapshot harga.
class SaveModuleJob
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
        $current = $id > 0 ? Database::fetchOne("SELECT id, code, name_key, description_key FROM modules WHERE id = ?", [$id]) : null;
        if ($id > 0 && $current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $code = $current ? $current['code'] : trim((string) ($this->input['code'] ?? ''));
        $this->input['code'] = $code;

        $v = new Validator($this->input);
        $v->required('code')->pattern('code', '/^[a-z][a-z0-9_]{1,30}$/', 'module.code_format')
          ->required('names.ms')->maxLength('names.ms', 100)->maxLength('names.en', 100)->maxLength('names.zh', 100)->maxLength('names.ta', 100)
          ->maxLength('descriptions.ms', 255)->maxLength('descriptions.en', 255)->maxLength('descriptions.zh', 255)->maxLength('descriptions.ta', 255)
          ->required('price_monthly')->integer('price_monthly', 0, 100000000)
          ->required('price_yearly')->integer('price_yearly', 0, 1000000000)
          ->integer('sort_order', 0, 9999);
        if ($current === null && $code !== '' && Database::fetchValue("SELECT 1 FROM modules WHERE code = ?", [$code])) {
            $v->addError('code', t('error.duplicate'));
        }
        ValidationException::throwIfFails($v);

        $nameKey = $current ? $current['name_key'] : 'module.' . $code;
        $descriptionKey = $current ? $current['description_key'] : 'module.' . $code . '.description';
        $values = [(int) $this->input['price_monthly'], (int) $this->input['price_yearly'],
            !empty($this->input['is_active']) ? 1 : 0, (int) ($this->input['sort_order'] ?? 0)];

        Database::beginTransaction();
        if ($current) {
            Database::execute(
                "UPDATE modules SET price_monthly = ?, price_yearly = ?, is_active = ?, sort_order = ? WHERE id = ?",
                array_merge($values, [$id])
            );
        } else {
            $id = Database::insert(
                "INSERT INTO modules (price_monthly, price_yearly, is_active, sort_order, code, name_key, description_key, is_core)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0)",
                array_merge($values, [$code, $nameKey, $descriptionKey])
            );
        }
        TranslationValues::save($nameKey, $this->input['names'] ?? []);
        TranslationValues::save($descriptionKey, $this->input['descriptions'] ?? []);
        Database::commit();
        (new GenerateLangFilesJob())->run();

        (new WriteAuditLogJob('owner', $this->actorId, null, 'module.save', 'module', $id, $code))->run();
        return ListModulesJob::find($id);
    }
}
