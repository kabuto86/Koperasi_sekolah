<?php
// Simpan terjemahan satu kunci (owner, PELAN 12). Bahasa Melayu wajib (jatuh balik bahasa lain). Pemegang tempat {x} dalam
// mana-mana bahasa mesti wujud dalam teks asal Bahasa Melayu — jika tidak, ia akan dipapar mentah kepada pengguna.
// Nilai kosong / sama dengan lang/*.php = kembali ke fail (TranslationValues::save). Fail bahasa dijana semula selepas commit.
class SaveTranslationJob
{
    const MAX_LENGTH = 5000;

    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $key = (string) ($this->input['key'] ?? '');
        $current = $this->current($key);
        if ($current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $values = is_array($this->input['values'] ?? null) ? $this->input['values'] : [];
        $this->validate($key, $values, $current);

        Database::beginTransaction();
        TranslationValues::save($key, $values);
        Database::commit();
        (new GenerateLangFilesJob())->run();

        (new WriteAuditLogJob('owner', $this->actorId, null, 'translation.update', 'translation', null, $key))->run();
        return ListTranslationsJob::shape($key, TranslationValues::get($key));
    }

    // Nilai semasa kunci yang wujud (lang/*.php atau DB) — kunci baharu tidak boleh dicipta dari sini
    private function current($key)
    {
        if (!preg_match('/^[A-Za-z0-9_.-]{1,150}$/', $key)) {
            return null;
        }
        $inFile = Lang::fileValue('ms', $key) !== null;
        $inDb = Database::fetchValue("SELECT 1 FROM translations WHERE translation_key = ? LIMIT 1", [$key]);
        return $inFile || $inDb ? TranslationValues::get($key) : null;
    }

    private function validate($key, array $values, array $current)
    {
        if (!is_string($values['ms'] ?? null) || trim($values['ms']) === '') {
            throw new ValidationException(t('translation.ms_required'), ['values.ms' => t('error.required')]);
        }
        $v = new Validator(['values' => $values]);
        $allowed = self::placeholders(Lang::fileValue('ms', $key) ?? $current['ms']);
        foreach (Config::get('languages') as $code) {
            $value = $values[$code] ?? '';
            if (!is_string($value)) {
                $v->addError('values.' . $code, t('error.invalid'));
                continue;
            }
            $v->maxLength('values.' . $code, self::MAX_LENGTH);
            $unknown = array_diff(self::placeholders($value), $allowed);
            if (count($unknown) > 0) {
                $v->addError('values.' . $code, t('translation.placeholder_unknown', ['list' => implode(', ', $unknown)]));
            }
        }
        ValidationException::throwIfFails($v);
    }

    // ['{name}', '{class}'] — corak sama dengan placeholdersOf() dalam assets/js/owner/translations.js
    public static function placeholders($text)
    {
        preg_match_all('/\{[a-z_]+\}/', (string) $text, $matches);
        return array_values(array_unique($matches[0]));
    }
}
