<?php
// Simpan draf atau terbit pengumuman (publish: true). Yang sudah terbit tidak boleh disunting.
class SaveAnnouncementJob
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
        if ($id > 0) {
            $current = Database::fetchOne("SELECT id, published_at FROM vendor_announcements WHERE id = ?", [$id]);
            if ($current === null) {
                throw new ValidationException(t('error.not_found'), [], 404);
            }
            if ($current['published_at'] !== null) {
                throw new ValidationException(t('announcement.already_published'));
            }
        }

        $target = (string) ($this->input['target'] ?? '');
        $vendorIds = $target === 'selected' ? $this->vendorIds() : [];
        $v = new Validator($this->input);
        $v->required('title')->maxLength('title', 200)->required('body')->maxLength('body', 5000)
          ->required('target')->inList('target', ['all', 'selected']);
        if ($target === 'selected' && count($vendorIds) === 0) {
            $v->addError('vendor_ids', t('announcement.choose_vendor'));
        }
        ValidationException::throwIfFails($v);

        $publish = !empty($this->input['publish']);
        $values = [trim($this->input['title']), trim($this->input['body']), $target];

        Database::beginTransaction();
        if ($id > 0) {
            Database::execute(
                "UPDATE vendor_announcements SET title = ?, body = ?, target = ?, published_at = " . ($publish ? 'NOW()' : 'NULL') . " WHERE id = ?",
                array_merge($values, [$id])
            );
            Database::execute("DELETE FROM vendor_announcement_targets WHERE announcement_id = ?", [$id]);
        } else {
            $id = Database::insert(
                "INSERT INTO vendor_announcements (title, body, target, created_by, published_at) VALUES (?, ?, ?, ?, " . ($publish ? 'NOW()' : 'NULL') . ")",
                array_merge($values, [$this->actorId])
            );
        }
        foreach ($vendorIds as $vendorId) {
            Database::execute("INSERT INTO vendor_announcement_targets (announcement_id, vendor_id) VALUES (?, ?)", [$id, $vendorId]);
        }
        Database::commit();

        (new WriteAuditLogJob('owner', $this->actorId, null, $publish ? 'announcement.publish' : 'announcement.save',
            'announcement', $id, $values[0]))->run();
        return ListAnnouncementsJob::find($id);
    }

    // Hanya id vendor yang wujud diterima
    private function vendorIds()
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($this->input['vendor_ids'] ?? [])))));
        if (count($ids) === 0) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT id FROM vendors WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")",
            $ids
        );
        return array_map('intval', array_column($rows, 'id'));
    }
}
