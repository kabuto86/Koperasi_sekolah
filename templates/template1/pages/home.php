<?php // Halaman utama store — kandungan penuh dibina dalam U5 ?>
<section class="p-4 mb-3 rounded-3 store-hero">
    <h1 class="h4 fw-bold mb-2" data-school-welcome><?= e(t('common.loading')) ?></h1>
    <p class="mb-0"><?= e(t('store.welcome_text')) ?></p>
</section>

<div class="alert alert-danger d-none" id="store-error" role="alert"></div>

<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-body-secondary py-5">
        <i class="bi bi-backpack display-6 d-block mb-2" aria-hidden="true"></i>
        <?= e(t('common.coming_soon')) ?>
    </div>
</div>
