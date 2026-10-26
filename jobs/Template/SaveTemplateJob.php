<?php
// Sunting template (nama, keterangan, harga, aktif). Template baharu ditambah oleh pembangun (folder templates/),
// jadi tiada "tambah" di sini. template1 sentiasa percuma & aktif (lalai setiap koperasi).
class SaveTemplateJob
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
        $template = ListTemplatesJob::find((int) ($this->input['id'] ?? 0));
        if ($template === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $isFree = !empty($this->input['is_free']);
        $isActive = !empty($this->input['is_active']);
        if ($template['code'] === 'template1' && (!$isFree || !$isActive)) {
            throw new ValidationException(t('template.template1_locked'));
        }

        $v = new Validator($this->input);
        $v->required('name')->maxLength('name', 100)->maxLength('description', 255);
        if (!$isFree) {
            $v->required('price_monthly')->integer('price_monthly', 0, 100000000)
              ->required('price_yearly')->integer('price_yearly', 0, 1000000000);
        }
        ValidationException::throwIfFails($v);

        Database::execute(
            "UPDATE templates SET name = ?, description = ?, is_free = ?, is_active = ?, price_monthly = ?, price_yearly = ? WHERE id = ?",
            [
                trim($this->input['name']), trim((string) ($this->input['description'] ?? '')), $isFree ? 1 : 0, $isActive ? 1 : 0,
                $isFree ? 0 : (int) $this->input['price_monthly'], $isFree ? 0 : (int) $this->input['price_yearly'], $template['id'],
            ]
        );
        (new WriteAuditLogJob('owner', $this->actorId, null, 'template.save', 'template', $template['id'], $template['code']))->run();
        return ListTemplatesJob::find($template['id']);
    }
}
