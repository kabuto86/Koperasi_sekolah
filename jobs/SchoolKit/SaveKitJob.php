<?php
// Simpan kit { id?, grade_level_code, school_year, name, note, items: [{ product_id, variant_id, qty, is_mandatory, note }], publish }
// (PELAN 18.9). Susunan items = sort_order. Item hanya produk/varian AKTIF koperasi yang sama; pasangan produk+varian tidak berulang.
// Satu kit per darjah per tahun dalam koperasi. Item sedia ada (produk+varian sama) dikekalkan id-nya supaya baris troli kit (B6) tidak putus.
class SaveKitJob
{
    private $vendorId;
    private $cooperativeId;
    private $schoolType;
    private $actorId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $schoolType, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->schoolType = (string) $schoolType;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $existing = $id > 0 ? SchoolKits::find($this->vendorId, $this->cooperativeId, $id) : null;
        if ($id > 0 && $existing === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($existing);
        $publish = array_key_exists('publish', $this->input) ? !empty($this->input['publish']) : null;

        Database::beginTransaction();
        try {
            if ($this->gradeTaken($data, $id)) {
                throw new ValidationException(t('error.check_form'), ['grade_level_code' => t('kit.duplicate')]);
            }
            if ($existing !== null) {
                SchoolKits::find($this->vendorId, $this->cooperativeId, $id, true);
                Database::execute(
                    "UPDATE school_kits SET grade_level_code = ?, school_year = ?, name = ?, note = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                    [$data['grade_level_code'], $data['school_year'], $data['name'], $data['note'], $this->vendorId, $this->cooperativeId, $id]
                );
            } else {
                $id = Database::insert(
                    "INSERT INTO school_kits (vendor_id, cooperative_id, grade_level_code, school_year, name, note, is_published)
                     VALUES (?, ?, ?, ?, ?, ?, 0)",
                    [$this->vendorId, $this->cooperativeId, $data['grade_level_code'], $data['school_year'], $data['name'], $data['note']]
                );
            }
            $this->saveItems($id, $data['items']);
            if ($publish !== null) {
                Database::execute(
                    "UPDATE school_kits SET is_published = ? WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
                    [$publish ? 1 : 0, $this->vendorId, $this->cooperativeId, $id]
                );
            }
            Database::commit();
        } catch (PDOException $exception) {
            Database::rollBack();
            // UNIQUE(cooperative_id, grade_level_code, school_year) — dua simpanan serentak
            if ($exception->getCode() === '23000') {
                throw new ValidationException(t('error.check_form'), ['grade_level_code' => t('kit.duplicate')]);
            }
            throw $exception;
        } catch (Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $detail = SchoolKits::detail($this->vendorId, $this->cooperativeId, $id);
        $action = $publish === true ? 'kit_publish' : 'kit_save';
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->actorId, $action, 'school_kit', $id,
            $detail['grade_level_code'] . ' ' . $detail['school_year'] . ' (' . count($detail['items']) . ')'))->run();
        unset($detail['items']);
        return $detail;
    }

    private function validate($existing)
    {
        $v = new Validator($this->input);
        $v->required('grade_level_code')->required('school_year')->integer('school_year')
            ->maxLength('name', 150)->maxLength('note', 1000);
        $grade = (string) ($this->input['grade_level_code'] ?? '');
        if ($grade !== '' && !in_array($grade, (new ListCooperativeGradesJob($this->schoolType))->run(), true)) {
            $v->addError('grade_level_code', t('error.invalid'));
        }
        $year = (int) ($this->input['school_year'] ?? 0);
        $sameYear = $existing !== null && (int) $existing['school_year'] === $year;
        if ($year > 0 && !$sameYear && !SchoolKits::yearAllowed($year)) {
            $v->addError('school_year', t('error.invalid'));
        }
        $items = $this->cleanItems();
        if ($items === null) {
            $v->addError('items', t('error.invalid'));
        } elseif (!$items) {
            $v->addError('items', t('kit.items_required'));
        }
        if ($v->fails()) {
            $errors = $v->errors();
            // Hanya ralat item → mesej item terus (borang lain tiada medan untuk dipapar)
            $message = count($errors) === 1 && isset($errors['items']) ? $errors['items'] : t('error.check_form');
            throw new ValidationException($message, $errors);
        }
        return [
            'grade_level_code' => $grade,
            'school_year'      => $year,
            'name'             => trim((string) ($this->input['name'] ?? '')),
            'note'             => self::nullable($this->input['note'] ?? ''),
            'items'            => $items,
        ];
    }

    // [] = tiada item · null = ada item tidak sah (produk koperasi lain / tidak aktif, varian salah, kuantiti salah, pendua)
    private function cleanItems()
    {
        $list = is_array($this->input['items'] ?? null) ? array_values($this->input['items']) : [];
        if (count($list) > SchoolKits::MAX_ITEMS) {
            return null;
        }
        $items = [];
        foreach ($list as $line) {
            $item = is_array($line) ? $this->cleanItem($line) : null;
            if ($item === null || isset($items[$item['key']])) {
                return null;
            }
            $items[$item['key']] = $item;
        }
        return array_values($items);
    }

    private function cleanItem(array $line)
    {
        $qty = $line['qty'] ?? 0;
        if (!(is_int($qty) || (is_string($qty) && preg_match('/^\d+$/', $qty))) || (int) $qty < 1 || (int) $qty > SchoolKits::MAX_QTY) {
            return null;
        }
        $note = trim((string) ($line['note'] ?? ''));
        if (mb_strlen($note, 'UTF-8') > 255) {
            return null;
        }
        $product = ProductCatalog::find($this->vendorId, $this->cooperativeId, $line['product_id'] ?? 0);
        if ($product === null || !$product['is_active']) {
            return null;
        }
        $variantId = (int) ($line['variant_id'] ?? 0);
        if ($variantId > 0) {
            if (!$product['has_variants'] || !Database::fetchValue(
                "SELECT 1 FROM product_variants WHERE vendor_id = ? AND product_id = ? AND id = ? AND is_active = 1",
                [$this->vendorId, (int) $product['id'], $variantId]
            )) {
                return null;
            }
        }
        return [
            'key'          => $product['id'] . ':' . $variantId,
            'product_id'   => (int) $product['id'],
            'variant_id'   => $variantId > 0 ? $variantId : null,
            'qty'          => (int) $qty,
            'is_mandatory' => !empty($line['is_mandatory']),
            'note'         => $note === '' ? null : $note,
        ];
    }

    private function gradeTaken(array $data, $id)
    {
        return (bool) Database::fetchValue(
            "SELECT 1 FROM school_kits WHERE vendor_id = ? AND cooperative_id = ? AND grade_level_code = ? AND school_year = ? AND id <> ?",
            [$this->vendorId, $this->cooperativeId, $data['grade_level_code'], $data['school_year'], (int) $id]
        );
    }

    // Kemas kini item sedia ada ikut produk+varian, tambah yang baharu, buang yang tiada lagi
    private function saveItems($kitId, array $items)
    {
        $current = [];
        foreach (Database::fetchAll(
            "SELECT id, product_id, variant_id FROM school_kit_items WHERE vendor_id = ? AND school_kit_id = ?",
            [$this->vendorId, $kitId]
        ) as $row) {
            $current[$row['product_id'] . ':' . (int) $row['variant_id']] = (int) $row['id'];
        }
        $keep = [];
        foreach ($items as $index => $item) {
            $args = [$item['qty'], $item['is_mandatory'] ? 1 : 0, $item['note'], $index + 1];
            if (isset($current[$item['key']])) {
                $keep[] = $current[$item['key']];
                Database::execute(
                    "UPDATE school_kit_items SET qty = ?, is_mandatory = ?, note = ?, sort_order = ? WHERE vendor_id = ? AND id = ?",
                    array_merge($args, [$this->vendorId, $current[$item['key']]])
                );
                continue;
            }
            Database::insert(
                "INSERT INTO school_kit_items (vendor_id, school_kit_id, product_id, variant_id, qty, is_mandatory, note, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                array_merge([$this->vendorId, $kitId, $item['product_id'], $item['variant_id']], $args)
            );
        }
        $removed = array_diff(array_values($current), $keep);
        if ($removed) {
            $marks = implode(',', array_fill(0, count($removed), '?'));
            Database::execute(
                "DELETE FROM school_kit_items WHERE vendor_id = ? AND school_kit_id = ? AND id IN ($marks)",
                array_merge([$this->vendorId, $kitId], array_values($removed))
            );
        }
    }

    private static function nullable($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
