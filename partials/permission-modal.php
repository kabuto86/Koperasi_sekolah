<?php
// Modal "Kebenaran" kakitangan (PELAN 4.1) — dikongsi halaman kakitangan owner & vendor.
// Logik dalam assets/js/permission-editor.js (PermissionEditor.open). Kumpulan kotak semak dijana oleh JS.
?>
<div class="modal fade" id="permission-modal" tabindex="-1" aria-labelledby="permission-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="permission-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="permission-modal-title"><?= e(t('permission.title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-1" id="permission-user"></p>
                <p class="small text-body-secondary" id="permission-help"><?= e(t('permission.help')) ?></p>
                <div class="alert alert-info small d-none" id="permission-readonly" role="status"></div>
                <div class="row g-3" id="permission-groups"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-auto" id="permission-reset">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> <?= e(t('permission.reset')) ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand" id="permission-save"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
