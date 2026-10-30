<?php
// Tambah / sunting produk + varian + darjah sasaran + terjemahan (PELAN 5.2 #3, 18.8). Harga dalam sen, dikira di pelayan.
// SKU & barcode unik dalam koperasi merentas produk DAN varian (imbasan barcode mesti menemui satu barang sahaja).
// Varian baharu → baris stok 0. Varian yang sudah ada stok/sejarah tidak boleh dibuang (nyahaktif sahaja).
class SaveProductJob
{
    const MAX_PRICE = 10000000; // RM 100,000

    private $vendorId;
    private $cooperative;
    private $actorId;
    private $input;

    // $cooperative = baris dari VendorAuth::requireCooperative()
    public function __construct($vendorId, array $cooperative, $actorId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperative = $cooperative;
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $coopId = (int) $this->cooperative['id'];
        $id = (int) ($this->input['id'] ?? 0);
        $current = $id > 0 ? ProductCatalog::find($this->vendorId, $coopId, $id) : null;
        if ($id > 0 && $current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($id);
        $oldVariants = $current ? ProductCatalog::variants($this->vendorId, $id) : [];
        $this->checkRemovals($id, $current, $data, $oldVariants);

        $oldImage = $current['image_path'] ?? null;
        $newImage = $this->imagePath($oldImage);
        Database::beginTransaction();
        try {
            if ($current === null) {
                Database::fetchValue("SELECT id FROM vendors WHERE id = ? FOR UPDATE", [$this->vendorId]);
                if (!PlanLimit::canAddProduct($this->vendorId)) {
                    $plan = PlanLimit::plan($this->vendorId);
                    throw new ValidationException(t('product.limit_reached', ['max' => $plan['max_products'] ?? 0]));
                }
                $id = $this->insert($data, $newImage);
            } else {
                $this->update($id, $data, $newImage);
            }
            $this->saveVariants($id, $data, $oldVariants);
            $this->saveGrades($id, $data['grade_levels']);
            $this->saveTranslations($id, $data['translations']);
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            if ($newImage !== $oldImage) {
                $this->deleteImageIfUnused($newImage);
            }
            throw $exception;
        }
        if ($oldImage && $oldImage !== $newImage) {
            $this->deleteImageIfUnused($oldImage);
        }

        (new WriteActivityLogJob($this->vendorId, $coopId, $this->actorId, 'product_save', 'product', $id, $data['name']))->run();
        if ($current !== null) {
            $this->logPriceChanges($id, $current, $data, $oldVariants);
        }
        $rows = ProductCatalog::summaries($this->vendorId, $coopId, 'AND p.id = ?', [$id]);
        return $rows[0];
    }

    // Log audit tukar harga (PELAN 15): harga asas & harga akhir setiap varian sedia ada — "Pensel 2B (Merah): RM 1.20 → RM 1.50"
    private function logPriceChanges($id, array $current, array $data, array $oldVariants)
    {
        $changes = [];
        $oldPrice = (int) $current['price'];
        if ($oldPrice !== $data['price']) {
            $changes[] = $data['name'] . ': ' . Money::format($oldPrice) . ' → ' . Money::format($data['price']);
        }
        $oldDeltas = array_column($oldVariants, 'price_delta', 'id');
        foreach ($data['variants'] as $variant) {
            if (!isset($oldDeltas[$variant['id']])) {
                continue;
            }
            $before = $oldPrice + (int) $oldDeltas[$variant['id']];
            $after = $data['price'] + $variant['price_delta'];
            if ($before !== $after) {
                $changes[] = $data['name'] . ' (' . $variant['label'] . '): ' . Money::format($before) . ' → ' . Money::format($after);
            }
        }
        if (count($changes) > 0) {
            $detail = mb_substr(implode('; ', $changes), 0, 1000, 'UTF-8');
            (new WriteActivityLogJob($this->vendorId, (int) $this->cooperative['id'], $this->actorId, 'price_change', 'product', $id, $detail))->run();
        }
    }

    private function validate($productId)
    {
        $input = $this->input;
        $v = new Validator($input);
        $v->required('name')->maxLength('name', 150)->maxLength('description', 2000)
          ->required('category_id')
          ->required('price')->integer('price', 0, self::MAX_PRICE)
          ->integer('cost_price', 0, self::MAX_PRICE)
          ->required('unit')->maxLength('unit', 20)
          ->maxLength('sku', 50)->maxLength('barcode', 50)
          ->integer('max_per_order', 1, 9999);

        $coopId = (int) $this->cooperative['id'];
        $categoryId = (int) ($input['category_id'] ?? 0);
        if ($categoryId > 0 && ListCategoriesJob::find($this->vendorId, $coopId, $categoryId) === null) {
            $v->addError('category_id', t('error.invalid'));
        }

        $hasVariants = !empty($input['has_variants']);
        $price = (int) ($input['price'] ?? 0);
        $variants = $hasVariants ? $this->cleanVariants($v, $price, $productId) : [];
        if ($hasVariants && !$variants) {
            $v->addError('variants', t('product.variants_required'));
        }
        $grades = $this->cleanGrades($v);
        $translations = $this->cleanTranslations($v);

        $sku = self::code($input['sku'] ?? '');
        $barcode = self::code($input['barcode'] ?? '');
        $this->checkCodes($v, $productId, $sku, $barcode, $variants);
        ValidationException::throwIfFails($v);

        $maxPerOrder = $input['max_per_order'] ?? null;
        return [
            'category_id'   => $categoryId,
            'name'          => trim((string) $input['name']),
            'description'   => trim((string) ($input['description'] ?? '')),
            'sku'           => $sku,
            'barcode'       => $barcode,
            'price'         => $price,
            'cost_price'    => (int) ($input['cost_price'] ?? 0),
            'unit'          => trim((string) $input['unit']),
            'max_per_order' => $maxPerOrder === null || $maxPerOrder === '' ? null : (int) $maxPerOrder,
            'has_variants'  => $hasVariants,
            'is_active'     => !empty($input['is_active']),
            'variants'      => $variants,
            'grade_levels'  => $grades,
            'translations'  => $translations,
        ];
    }

    // Kosong → NULL (UNIQUE membenarkan banyak NULL)
    private static function code($value)
    {
        $value = trim((string) (is_scalar($value) ? $value : ''));
        return $value === '' ? null : $value;
    }

    // id varian mesti milik produk ini (produk baharu: semua varian baharu)
    private function cleanVariants(Validator $v, $price, $productId)
    {
        $ownIds = $productId > 0 ? array_column(ProductCatalog::variants($this->vendorId, $productId), 'id') : [];
        $result = [];
        $list = is_array($this->input['variants'] ?? null) ? $this->input['variants'] : [];
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $delta = $item['price_delta'] ?? 0;
            $sku = self::code($item['sku'] ?? '');
            $barcode = self::code($item['barcode'] ?? '');
            if ($label === '' || mb_strlen($label, 'UTF-8') > 100 || mb_strlen((string) $sku) > 50 || mb_strlen((string) $barcode) > 50) {
                $v->addError('variants', t('error.check_form'));
            }
            if (!is_int($delta) && !(is_string($delta) && preg_match('/^-?\d+$/', $delta))) {
                $v->addError('variants', t('error.integer'));
                $delta = 0;
            }
            if ($price + (int) $delta < 0 || abs((int) $delta) > self::MAX_PRICE) {
                $v->addError('variants', t('error.invalid'));
            }
            $variantId = (int) ($item['id'] ?? 0);
            if ($variantId > 0 && !in_array($variantId, $ownIds, true)) {
                $v->addError('variants', t('error.invalid'));
            }
            $result[] = [
                'id'          => $variantId,
                'label'       => $label,
                'sku'         => $sku,
                'barcode'     => $barcode,
                'price_delta' => (int) $delta,
                'is_active'   => !array_key_exists('is_active', $item) || !empty($item['is_active']),
            ];
        }
        return $result;
    }

    // Darjah sasaran mesti sesuai dengan jenis sekolah koperasi. [] = semua darjah
    private function cleanGrades(Validator $v)
    {
        $input = is_array($this->input['grade_levels'] ?? null) ? $this->input['grade_levels'] : [];
        $allowed = (new ListCooperativeGradesJob($this->cooperative['school_type']))->run();
        $grades = array_values(array_unique(array_map('strval', array_filter($input, 'is_scalar'))));
        foreach ($grades as $grade) {
            if (!in_array($grade, $allowed, true)) {
                $v->addError('grade_levels', t('error.invalid'));
            }
        }
        return $grades;
    }

    // Terjemahan pilihan untuk bahasa aktif selain lalai. Bahasa kosong diabaikan (jatuh balik ke nama asal)
    private function cleanTranslations(Validator $v)
    {
        $input = is_array($this->input['translations'] ?? null) ? $this->input['translations'] : [];
        $codes = array_column(Database::fetchAll("SELECT code FROM languages WHERE is_active = 1 AND is_default = 0"), 'code');
        $result = [];
        foreach ($input as $code => $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            if ($name === '' && $description === '') {
                continue;
            }
            if (!in_array($code, $codes, true)) {
                $v->addError('translations', t('error.invalid'));
                continue;
            }
            if ($name === '') {
                $v->addError('translations.' . $code . '.name', t('error.required'));
            }
            if (mb_strlen($name, 'UTF-8') > 150) {
                $v->addError('translations.' . $code . '.name', t('error.max_length', ['max' => 150]));
            }
            if (mb_strlen($description, 'UTF-8') > 2000) {
                $v->addError('translations.' . $code . '.description', t('error.max_length', ['max' => 2000]));
            }
            $result[$code] = ['name' => $name, 'description' => $description];
        }
        return $result;
    }

    // SKU & barcode: tiada pendua dalam borang ini, dan tiada pada produk / varian LAIN dalam koperasi (tanpa beza huruf besar)
    private function checkCodes(Validator $v, $productId, $sku, $barcode, array $variants)
    {
        foreach (['sku' => $sku, 'barcode' => $barcode] as $column => $productCode) {
            $seen = [];
            if ($productCode !== null) {
                $seen[] = mb_strtolower($productCode);
                if ($this->codeTaken($column, $productCode, $productId)) {
                    $v->addError($column, t('error.duplicate'));
                }
            }
            foreach ($variants as $variant) {
                if ($variant[$column] === null) {
                    continue;
                }
                $key = mb_strtolower($variant[$column]);
                if (in_array($key, $seen, true) || $this->codeTaken($column, $variant[$column], $productId)) {
                    $v->addError('variants', t('error.duplicate'));
                }
                $seen[] = $key;
            }
        }
    }

    private function codeTaken($column, $code, $productId)
    {
        $coopId = (int) $this->cooperative['id'];
        $args = [$this->vendorId, $coopId, $code, (int) $productId];
        return Database::fetchValue("SELECT 1 FROM products WHERE vendor_id = ? AND cooperative_id = ? AND $column = ? AND id <> ?", $args)
            || Database::fetchValue("SELECT 1 FROM product_variants WHERE vendor_id = ? AND cooperative_id = ? AND $column = ? AND product_id <> ?", $args);
    }

    // Varian lama yang dibuang (atau baris tanpa varian bila varian dihidupkan) mesti tiada stok & sejarah
    private function checkRemovals($productId, $current, array $data, array $oldVariants)
    {
        if ($current === null) {
            return;
        }
        $coopId = (int) $this->cooperative['id'];
        $keepIds = array_column($data['variants'], 'id');
        foreach ($oldVariants as $old) {
            if (!in_array($old['id'], $keepIds, true) && ProductCatalog::variantInUse($this->vendorId, $coopId, $productId, $old['id'])) {
                throw new ValidationException(t('product.variant_locked'), ['variants' => t('product.variant_locked')]);
            }
        }
        if ($data['has_variants'] && !$current['has_variants'] && ProductCatalog::variantInUse($this->vendorId, $coopId, $productId, 0)) {
            throw new ValidationException(t('product.variant_locked'), ['has_variants' => t('product.variant_locked')]);
        }
    }

    // image_url: data URL = gambar baharu · kosong = buang · URL sedia ada = kekal
    private function imagePath($oldImage)
    {
        $value = $this->input['image_url'] ?? null;
        if (Uploader::isDataUrl($value)) {
            return (new UploadProductImageJob($this->vendorId, $this->cooperative['id'], $value))->run();
        }
        if ($value === null || $value === '') {
            return null;
        }
        return $oldImage;
    }

    // Nama fail = hash kandungan, jadi produk lain dengan gambar sama berkongsi fail — padam hanya jika tiada lagi yang memakainya
    private function deleteImageIfUnused($path)
    {
        if ($path && !Database::fetchValue("SELECT 1 FROM products WHERE vendor_id = ? AND image_path = ? LIMIT 1", [$this->vendorId, $path])) {
            Uploader::delete($path);
        }
    }

    private function insert($data, $image)
    {
        $coopId = (int) $this->cooperative['id'];
        $sort = (int) Database::fetchValue(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM products WHERE vendor_id = ? AND cooperative_id = ?",
            [$this->vendorId, $coopId]
        );
        return Database::insert(
            "INSERT INTO products (vendor_id, cooperative_id, category_id, name, description, sku, barcode, price, cost_price, unit,
                                   image_path, max_per_order, has_variants, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array_merge([$this->vendorId, $coopId], $this->columns($data, $image), [$sort])
        );
    }

    private function update($id, $data, $image)
    {
        Database::execute(
            "UPDATE products SET category_id = ?, name = ?, description = ?, sku = ?, barcode = ?, price = ?, cost_price = ?, unit = ?,
                                 image_path = ?, max_per_order = ?, has_variants = ?, is_active = ?
              WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            array_merge($this->columns($data, $image), [$this->vendorId, (int) $this->cooperative['id'], $id])
        );
    }

    private function columns($data, $image)
    {
        return [
            $data['category_id'], $data['name'], $data['description'] === '' ? null : $data['description'], $data['sku'],
            $data['barcode'], $data['price'], $data['cost_price'], $data['unit'], $image, $data['max_per_order'],
            $data['has_variants'] ? 1 : 0, $data['is_active'] ? 1 : 0,
        ];
    }

    // Varian + baris stok. Kod dikosongkan dahulu supaya pertukaran SKU/barcode antara varian tidak melanggar UNIQUE
    private function saveVariants($productId, array $data, array $oldVariants)
    {
        $coopId = (int) $this->cooperative['id'];
        Database::execute(
            "UPDATE product_variants SET sku = NULL, barcode = NULL WHERE vendor_id = ? AND product_id = ?",
            [$this->vendorId, $productId]
        );
        $keepIds = [];
        foreach ($data['variants'] as $variant) {
            $values = [$variant['label'], $variant['sku'], $variant['barcode'], $variant['price_delta'], $variant['is_active'] ? 1 : 0];
            if ($variant['id'] > 0) {
                Database::execute(
                    "UPDATE product_variants SET label = ?, sku = ?, barcode = ?, price_delta = ?, is_active = ?
                      WHERE vendor_id = ? AND product_id = ? AND id = ?",
                    array_merge($values, [$this->vendorId, $productId, $variant['id']])
                );
                $keepIds[] = $variant['id'];
            } else {
                $keepIds[] = Database::insert(
                    "INSERT INTO product_variants (vendor_id, cooperative_id, product_id, label, sku, barcode, price_delta, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    array_merge([$this->vendorId, $coopId, $productId], $values)
                );
            }
        }
        foreach ($oldVariants as $old) {
            if (!in_array($old['id'], $keepIds, true)) {
                Database::execute("DELETE FROM product_variants WHERE vendor_id = ? AND product_id = ? AND id = ?", [$this->vendorId, $productId, $old['id']]);
            }
        }
        $this->syncStockRows($productId, $data['has_variants'] ? $keepIds : [0]);
    }

    // Satu baris stok bagi setiap varian (atau variant_id 0). Baris lain (sudah disahkan kosong) dibuang
    private function syncStockRows($productId, array $variantIds)
    {
        $coopId = (int) $this->cooperative['id'];
        $minStock = CooperativeSettings::lowStockDefault($this->vendorId, $coopId);
        foreach ($variantIds as $variantId) {
            Database::execute(
                "INSERT IGNORE INTO stock_levels (vendor_id, cooperative_id, product_id, variant_id, qty_on_hand, qty_reserved, min_stock)
                 VALUES (?, ?, ?, ?, 0, 0, ?)",
                [$this->vendorId, $coopId, $productId, (int) $variantId, $minStock]
            );
        }
        $marks = implode(',', array_fill(0, count($variantIds), '?'));
        Database::execute(
            "DELETE FROM stock_levels WHERE vendor_id = ? AND cooperative_id = ? AND product_id = ? AND variant_id NOT IN ($marks)
                AND qty_on_hand = 0 AND qty_reserved = 0",
            array_merge([$this->vendorId, $coopId, $productId], array_map('intval', $variantIds))
        );
    }

    private function saveGrades($productId, array $grades)
    {
        Database::execute("DELETE FROM product_grade_levels WHERE vendor_id = ? AND product_id = ?", [$this->vendorId, $productId]);
        foreach ($grades as $grade) {
            Database::insert(
                "INSERT INTO product_grade_levels (vendor_id, product_id, grade_level_code) VALUES (?, ?, ?)",
                [$this->vendorId, $productId, $grade]
            );
        }
    }

    private function saveTranslations($productId, array $translations)
    {
        Database::execute("DELETE FROM product_translations WHERE vendor_id = ? AND product_id = ?", [$this->vendorId, $productId]);
        foreach ($translations as $code => $item) {
            Database::insert(
                "INSERT INTO product_translations (vendor_id, product_id, language_code, name, description) VALUES (?, ?, ?, ?, ?)",
                [$this->vendorId, $productId, $code, $item['name'], $item['description'] === '' ? null : $item['description']]
            );
        }
    }
}
