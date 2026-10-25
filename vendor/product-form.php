<?php
// Borang produk — maklumat, harga, gambar, darjah sasaran, varian, terjemahan pilihan
require __DIR__ . '/../core/bootstrap.php';

$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? t('product.edit') : t('product.add');
$activeMenu = 'menu.products';
$backUrl = View::url('vendor/products.php');
$pageScripts = [View::asset('js/vendor/product-form.js')];
$translationLanguages = array_values(array_diff(Config::get('languages'), ['ms']));
require __DIR__ . '/_layout/header.php';
?>
<form id="product-form" novalidate>
    <input type="hidden" name="id">
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('product.section_info')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="product-name"><?= e(t('field.name')) ?></label>
                        <input type="text" class="form-control" id="product-name" name="name" required maxlength="150">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="product-category"><?= e(t('field.category')) ?></label>
                        <select class="form-select" id="product-category" name="category_id" required>
                            <option value=""><?= e(t('common.choose')) ?></option>
                        </select>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="product-unit"><?= e(t('product.unit')) ?></label>
                        <input type="text" class="form-control" id="product-unit" name="unit" required maxlength="20" list="unit-options">
                        <datalist id="unit-options">
                            <option value="buah"><option value="helai"><option value="pasang"><option value="kotak"><option value="set"><option value="pek">
                        </datalist>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="product-description"><?= e(t('field.description')) ?></label>
                        <textarea class="form-control" id="product-description" name="description" rows="2"></textarea>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="product-sku"><?= e(t('product.sku')) ?></label>
                        <input type="text" class="form-control font-monospace" id="product-sku" name="sku" maxlength="40">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="product-barcode"><?= e(t('product.barcode')) ?></label>
                        <input type="text" class="form-control font-monospace" id="product-barcode" name="barcode" maxlength="40" inputmode="numeric">
                        <div class="form-text"><?= e(t('product.barcode_help')) ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><?= e(t('product.section_variants')) ?></span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="product-has-variants" name="has_variants">
                        <label class="form-check-label" for="product-has-variants"><?= e(t('product.has_variants')) ?></label>
                    </div>
                </div>
                <div class="card-body small text-body-secondary" id="variants-help"><?= e(t('product.variants_help')) ?></div>
                <div class="d-none" id="variants-section">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= e(t('product.variant_label')) ?></th>
                                    <th><?= e(t('product.sku')) ?></th>
                                    <th><?= e(t('product.barcode')) ?></th>
                                    <th><?= e(t('product.price_delta')) ?></th>
                                    <th class="text-end"><?= e(t('product.final_price')) ?></th>
                                    <th><?= e(t('common.active')) ?></th>
                                    <th><span class="visually-hidden"><?= e(t('common.actions')) ?></span></th>
                                </tr>
                            </thead>
                            <tbody id="variants-body"></tbody>
                        </table>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-variant">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('product.add_variant')) ?>
                        </button>
                        <div class="text-danger small mt-2 d-none" id="variants-error"><?= e(t('product.variants_required')) ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body">
                    <button class="btn btn-link p-0 text-decoration-none fw-semibold" type="button" data-bs-toggle="collapse"
                            data-bs-target="#translations-section" aria-expanded="false" aria-controls="translations-section">
                        <i class="bi bi-translate" aria-hidden="true"></i> <?= e(t('product.section_translations')) ?>
                    </button>
                    <div class="small text-body-secondary"><?= e(t('product.translations_help')) ?></div>
                </div>
                <div class="collapse" id="translations-section">
                    <div class="card-body row g-3">
                        <?php foreach ($translationLanguages as $code): ?>
                        <div class="col-md-4">
                            <label class="form-label" for="tr-name-<?= e($code) ?>" lang="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></label>
                            <input type="text" class="form-control mb-2" id="tr-name-<?= e($code) ?>" name="translations.<?= e($code) ?>.name"
                                   lang="<?= e($code) ?>" placeholder="<?= e(t('field.name')) ?>" maxlength="150">
                            <textarea class="form-control" name="translations.<?= e($code) ?>.description" rows="2" lang="<?= e($code) ?>"
                                      placeholder="<?= e(t('field.description')) ?>" aria-label="<?= e(t('field.description') . ' — ' . t('lang.' . $code)) ?>"></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('product.section_price')) ?></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="product-price"><?= e(t('product.selling_price')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="product-price" name="price" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="product-cost"><?= e(t('product.cost_price')) ?></label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="product-cost" name="cost_price" data-type="money" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="form-text mb-3" id="margin-hint"></div>
                    <label class="form-label" for="product-max"><?= e(t('product.max_per_order')) ?></label>
                    <input type="number" class="form-control" id="product-max" name="max_per_order" data-type="int" min="1">
                    <div class="form-text"><?= e(t('product.max_per_order_help')) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('product.image')) ?></div>
                <div class="card-body text-center">
                    <div class="app-product-image mx-auto mb-2" id="image-preview"><i class="bi bi-image" aria-hidden="true"></i></div>
                    <label class="btn btn-sm btn-outline-secondary" for="image-file">
                        <i class="bi bi-upload" aria-hidden="true"></i> <?= e(t('upload.choose_image')) ?>
                    </label>
                    <input type="file" class="visually-hidden" id="image-file" accept="image/png,image/jpeg,image/webp">
                    <button type="button" class="btn btn-sm btn-link text-danger" id="image-remove"><?= e(t('upload.remove')) ?></button>
                    <div class="form-text"><?= e(t('upload.image_help')) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('product.grades')) ?></div>
                <div class="card-body">
                    <div class="row row-cols-2 g-1" id="grade-checks"></div>
                    <div class="form-text"><?= e(t('product.grades_help')) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="product-active" name="is_active">
                        <label class="form-check-label" for="product-active"><?= e(t('product.active_help')) ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(View::url('vendor/products.php')) ?>"><?= e(t('btn.cancel')) ?></a>
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= e(t('btn.save')) ?></button>
    </div>
</form>
<?php require __DIR__ . '/_layout/footer.php'; ?>
