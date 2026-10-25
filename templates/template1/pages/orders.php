<?php // Rekod pembelian di sekolah ini: tapis anak & tahun ?>
<h1 class="h5 mb-3"><?= e(t('store.nav.orders')) ?></h1>
<form class="row g-2 mb-3" id="orders-filters">
    <div class="col-7 col-md-4">
        <label class="visually-hidden" for="orders-child"><?= e(t('order.child')) ?></label>
        <select class="form-select" id="orders-child" name="child_id">
            <option value=""><?= e(t('store.orders.all_children')) ?></option>
        </select>
    </div>
    <div class="col-5 col-md-3">
        <label class="visually-hidden" for="orders-year"><?= e(t('store.orders.year')) ?></label>
        <select class="form-select" id="orders-year" name="year">
            <option value=""><?= e(t('store.orders.all_years')) ?></option>
        </select>
    </div>
</form>
<div class="d-grid gap-2" id="orders-list"></div>
<nav class="mt-3" id="orders-pagination" aria-label="<?= e(t('store.nav.orders')) ?>"></nav>
