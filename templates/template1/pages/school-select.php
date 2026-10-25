<?php // Pilih sekolah: carian nama & tapis negeri ?>
<?php $malaysia = require APP_ROOT . '/config/malaysia.php'; ?>
<h1 class="h5 mb-3"><?= e(t('store.select_school')) ?></h1>
<form class="row g-2 mb-3" id="school-filters" role="search">
    <div class="col-12 col-md-7">
        <label class="visually-hidden" for="school-q"><?= e(t('store.school.search_label')) ?></label>
        <input class="form-control" type="search" id="school-q" name="q" placeholder="<?= e(t('store.school.search_placeholder')) ?>">
    </div>
    <div class="col-12 col-md-5">
        <label class="visually-hidden" for="school-state"><?= e(t('field.state')) ?></label>
        <select class="form-select" id="school-state" name="state">
            <option value=""><?= e(t('store.school.all_states')) ?></option>
            <?php foreach ($malaysia['states'] as $state): ?>
            <option value="<?= e($state) ?>"><?= e($state) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<div class="small text-body-secondary mb-2" id="school-count"></div>
<div class="list-group shadow-sm" id="school-list"></div>
<p class="small text-body-secondary mt-3"><?= e(t('store.school.not_listed')) ?></p>
