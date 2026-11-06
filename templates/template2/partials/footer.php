<?php // Template 2 (Ceria): kaki halaman dengan ikon alat tulis ?>
<footer class="py-4 text-center small store-footer store-t2-footer d-print-none">
    <div class="container">
        <div class="mb-2 fs-5 store-t2-footer-icons" aria-hidden="true">
            <i class="bi bi-pencil"></i> <i class="bi bi-backpack2"></i> <i class="bi bi-book"></i> <i class="bi bi-palette"></i>
        </div>
        <div class="mb-1 d-none" data-school-contact></div>
        <?= e(t('app.footer', ['year' => date('Y')])) ?>
    </div>
</footer>
