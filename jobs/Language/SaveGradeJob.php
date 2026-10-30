<?php
// Sunting darjah / tingkatan: nama 4 bahasa (translations), jenis & susunan. Kod tetap (dirujuk oleh anak & kit).
class SaveGradeJob
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
        $code = (string) ($this->input['code'] ?? '');
        $grade = Database::fetchOne("SELECT code, name_key FROM grade_levels WHERE code = ?", [$code]);
        if ($grade === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        foreach (Config::get('languages') as $language) {
            $v->required('names.' . $language)->maxLength('names.' . $language, 50);
        }
        $v->required('level_type')->inList('level_type', ['preschool', 'primary', 'secondary'])
          ->required('sort_order')->integer('sort_order', 0, 999);
        ValidationException::throwIfFails($v);

        Database::beginTransaction();
        Database::execute(
            "UPDATE grade_levels SET level_type = ?, sort_order = ? WHERE code = ?",
            [$this->input['level_type'], (int) $this->input['sort_order'], $code]
        );
        TranslationValues::save($grade['name_key'], $this->input['names']);
        Database::commit();
        (new GenerateLangFilesJob())->run();

        (new WriteAuditLogJob('owner', $this->actorId, null, 'grade.update', 'grade_level', null, $code))->run();
        return ListGradesJob::find($code);
    }
}
