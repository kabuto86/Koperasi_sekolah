<?php
// Terbit / tarik balik kit { id, is_published }. Kit draf tidak kelihatan di store (B6).
// Terbit memerlukan sekurang-kurangnya satu item aktif — kit kosong tidak berguna kepada ibu bapa.
class PublishKitJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;
    private $kitId;
    private $publish;

    public function __construct($vendorId, $cooperativeId, $actorId, $kitId, $publish)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
        $this->kitId = (int) $kitId;
        $this->publish = (bool) $publish;
    }

    public function run()
    {
        $kit = SchoolKits::detail($this->vendorId, $this->cooperativeId, $this->kitId);
        if ($kit === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($this->publish && !array_filter(array_column($kit['items'], 'is_active'))) {
            throw new ValidationException(t('kit.items_required'));
        }
        Database::execute(
            "UPDATE school_kits SET is_published = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [$this->publish ? 1 : 0, $this->vendorId, $this->cooperativeId, $this->kitId]
        );
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, $this->publish ? 'kit_publish' : 'kit_unpublish',
            'school_kit', $this->kitId, $kit['grade_level_code'] . ' ' . $kit['school_year']))->run();
        unset($kit['items']);
        $kit['is_published'] = $this->publish;
        return $kit;
    }
}
