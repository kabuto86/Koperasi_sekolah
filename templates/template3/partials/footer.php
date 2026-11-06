<?php // Template 3 (Klasik): kaki halaman biru tua dengan lambang & garis emas ?>
<footer class="py-4 text-center small store-footer store-t3-footer d-print-none">
    <div class="container">
        <div class="mb-2 fs-4" aria-hidden="true"><i class="bi bi-mortarboard"></i></div>
        <div class="mb-1 d-none" data-school-contact></div>
        <?= e(t('app.footer', ['year' => date('Y')])) ?>
    </div>
</footer>
