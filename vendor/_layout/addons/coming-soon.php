<?php
// Modul add-on yang sudah dilanggan tetapi halamannya belum dibina
?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 px-3">
        <i class="bi bi-tools fs-1 text-body-secondary" aria-hidden="true"></i>
        <h2 class="h5 mt-2"><?= e(t('addon.page_coming_title')) ?></h2>
        <p class="text-body-secondary mx-auto app-addon-lock-text mb-0"><?= e(t('addon.desc.' . $moduleCode)) ?></p>
    </div>
</div>
