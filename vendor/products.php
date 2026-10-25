<?php
// Senarai produk koperasi semasa — carian, tapis kategori/darjah/status/stok rendah
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.products_list');
$activeMenu = 'menu.products';
$subNavGroup = 'products';
$subNavActive = 'vendor/products.php';
$pageActions = [['id' => 'btn-add-product', 'label' => t('product.add'), 'icon' => 'bi-plus-lg', 'href' => View::url('vendor/product-form.php')]];
$pageScripts = [View::asset('js/vendor/products.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="product-filters" role="search">
            <div class="col-12 col-lg-4">
                <label class="visually-hidden" for="product-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="product-q" name="q" placeholder="<?= e(t('product.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="product-category"><?= e(t('field.category')) ?></label>
                <select class="form-select" id="product-category" name="category_id">
                    <option value=""><?= e(t('common.all_categories')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="product-grade"><?= e(t('field.grade')) ?></label>
                <select class="form-select" id="product-grade" name="grade">
                    <option value=""><?= e(t('common.all_grades')) ?></option>
                    <option value="all"><?= e(t('product.all_grades_only')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="product-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="product-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <option value="active"><?= e(t('common.active')) ?></option>
                    <option value="inactive"><?= e(t('common.inactive')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2 d-flex align-items-center">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="product-low" name="low_only">
                    <label class="form-check-label" for="product-low"><?= e(t('inventory.low_only')) ?></label>
                </div>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.product')) ?></th>
                    <th><?= e(t('field.category')) ?></th>
                    <th><?= e(t('product.grades')) ?></th>
                    <th class="text-end"><?= e(t('field.price')) ?></th>
                    <th class="text-end"><?= e(t('inventory.available')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="products-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="products-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
