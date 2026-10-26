<?php
// Salin semua kit tahun (school_year − 1) ke school_year sebagai DRAF { school_year } (PELAN 18.9, 16.13).
// Darjah yang sudah ada kit tahun sasaran dilangkau; item produk/varian tidak aktif dibuang. Harga tidak disalin (sentiasa semasa).
// → { copied, skipped, removed_items }
class CopyKitFromYearJob
{
    private $vendorId;
    private $cooperativeId;
    private $actorId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $year = (int) ($this->input['school_year'] ?? 0);
        if (!SchoolKits::yearAllowed($year)) {
            throw new ValidationException(t('error.check_form'), ['school_year' => t('error.invalid')]);
        }
        $result = ['copied' => 0, 'skipped' => 0, 'removed_items' => 0];

        Database::beginTransaction();
        try {
            // Kunci baris koperasi supaya dua salinan serentak tidak bertembung
            Database::fetchValue("SELECT id FROM cooperatives WHERE vendor_id = ? AND id = ? FOR UPDATE", [$this->vendorId, $this->cooperativeId]);
            $taken = array_column(Database::fetchAll(
                "SELECT grade_level_code FROM school_kits WHERE vendor_id = ? AND cooperative_id = ? AND school_year = ?",
                [$this->vendorId, $this->cooperativeId, $year]
            ), 'grade_level_code');
            $sources = Database::fetchAll(
                "SELECT * FROM school_kits WHERE vendor_id = ? AND cooperative_id = ? AND school_year = ? ORDER BY id",
                [$this->vendorId, $this->cooperativeId, $year - 1]
            );
            $items = SchoolKits::items($this->vendorId, array_column($sources, 'id'));
            foreach ($sources as $kit) {
                if (in_array($kit['grade_level_code'], $taken, true)) {
                    $result['skipped']++;
                    continue;
                }
                $result['removed_items'] += $this->copyKit($kit, $year, $items[(int) $kit['id']] ?? []);
                $result['copied']++;
            }
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
        if ($result['copied'] > 0) {
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, 'kit_copy', 'school_kit', null,
                ($year - 1) . ' → ' . $year . ' (' . $result['copied'] . ')'))->run();
        }
        return $result;
    }

    // Pulangkan bilangan item yang dibuang (produk/varian tidak aktif)
    private function copyKit(array $kit, $year, array $items)
    {
        $newId = Database::insert(
            "INSERT INTO school_kits (vendor_id, cooperative_id, grade_level_code, school_year, name, note, is_published)
             VALUES (?, ?, ?, ?, ?, ?, 0)",
            [$this->vendorId, $this->cooperativeId, $kit['grade_level_code'], $year, $kit['name'], $kit['note']]
        );
        $removed = 0;
        $order = 0;
        foreach ($items as $item) {
            if (!$item['is_active']) {
                $removed++;
                continue;
            }
            Database::insert(
                "INSERT INTO school_kit_items (vendor_id, school_kit_id, product_id, variant_id, qty, is_mandatory, note, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$this->vendorId, $newId, $item['product_id'], $item['variant_id'], $item['qty'], $item['is_mandatory'] ? 1 : 0,
                    $item['note'] === '' ? null : $item['note'], ++$order]
            );
        }
        return $removed;
    }
}
