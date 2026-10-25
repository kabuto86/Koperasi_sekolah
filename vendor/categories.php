<?php
// Kategori produk koperasi semasa
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.categories');
$activeMenu = 'menu.products';
$subNavGroup = 'products';
$subNavActive = 'vendor/categories.php';
$pageActions = [['id' => 'btn-add-category', 'label' => t('category.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/categories.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.name')) ?></th>
                    <th class="text-center"><?= e(t('category.products')) ?></th>
                    <th class="text-center"><?= e(t('field.sort_order')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="categories-body"></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="category-modal" tabindex="-1" aria-labelledby="category-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="category-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="category-title"><?= e(t('category.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="mb-3">
                    <label class="form-label" for="category-name"><?= e(t('field.name')) ?></label>
                    <input type="text" class="form-control" id="category-name" name="name" required maxlength="60">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="category-sort"><?= e(t('field.sort_order')) ?></label>
                        <input type="number" class="form-control" id="category-sort" name="sort_order" data-type="int" min="0">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="category-active" name="is_active">
                            <label class="form-check-label" for="category-active"><?= e(t('common.active')) ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
