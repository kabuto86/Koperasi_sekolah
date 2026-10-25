<?php // Katalog ala carte: carian, kategori, darjah ?>
<h1 class="h5 mb-3"><?= e(t('store.nav.catalog')) ?></h1>
<form class="row g-2 mb-2" id="catalog-filters" role="search">
    <div class="col-12 col-md-6">
        <label class="visually-hidden" for="catalog-q"><?= e(t('btn.search')) ?></label>
        <input class="form-control" type="search" id="catalog-q" name="q" placeholder="<?= e(t('store.catalog.search_placeholder')) ?>">
    </div>
    <div class="col-6 col-md-3">
        <label class="visually-hidden" for="catalog-category"><?= e(t('field.category')) ?></label>
        <select class="form-select" id="catalog-category" name="category_id">
            <option value=""><?= e(t('common.all_categories')) ?></option>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label class="visually-hidden" for="catalog-grade"><?= e(t('field.grade')) ?></label>
        <select class="form-select" id="catalog-grade" name="grade">
            <option value=""><?= e(t('common.all_grades')) ?></option>
        </select>
    </div>
</form>
<div class="d-flex flex-wrap gap-2 mb-3 d-none" id="catalog-children" aria-label="<?= e(t('store.catalog.for_child')) ?>"></div>
<div class="small text-body-secondary mb-2" id="catalog-count"></div>
<div class="row g-2 g-md-3" id="catalog-grid"></div>
<nav class="mt-3" id="catalog-pagination" aria-label="<?= e(t('store.nav.catalog')) ?>"></nav>
