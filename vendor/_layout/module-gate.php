<?php
// Halaman modul add-on (U6): kad "Langgan modul ini" bila koperasi semasa belum melanggan.
// Halaman set $moduleCode sebelum header; kandungan halaman dibungkus #module-content
// dan dipapar oleh assets/js/vendor/addon-common.js selepas langganan disahkan.
// Kawalan sebenar di pelayan: ModuleAccess::has($cooperativeId, $moduleCode) dalam setiap endpoint.
?>
<div class="text-center text-body-secondary py-5" id="module-checking">
    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
    <span class="ms-1"><?= e(t('common.loading')) ?></span>
</div>
<div class="card border-0 shadow-sm d-none" id="module-locked">
    <div class="card-body text-center py-5 px-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-bg-warning app-addon-lock-icon mb-3">
            <i class="bi bi-lock-fill fs-3" aria-hidden="true"></i>
        </span>
        <h2 class="h5"><?= e(t('addon.locked_title', ['name' => t('module.' . $moduleCode)])) ?></h2>
        <p class="text-body-secondary mb-1" id="module-locked-coop"></p>
        <p class="text-body-secondary mx-auto app-addon-lock-text"><?= e(t('addon.desc.' . $moduleCode)) ?></p>
        <a class="btn btn-brand" href="<?= e(View::url('vendor/addon-store.php?module=' . $moduleCode)) ?>">
            <i class="bi bi-cart-plus" aria-hidden="true"></i> <?= e(t('addon.subscribe_this')) ?>
        </a>
    </div>
</div>
<div class="d-none" id="module-content" data-module-code="<?= e($moduleCode) ?>">
