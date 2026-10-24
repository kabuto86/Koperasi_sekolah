<?php
// Bekas untuk showToast() dan confirmDialog() dalam helpers.js
?>
<div id="app-toasts" class="toast-container position-fixed bottom-0 end-0 p-3 app-toasts"></div>

<div class="modal fade" id="app-confirm" tabindex="-1" aria-labelledby="app-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="app-confirm-title"><?= e(t('common.confirm_title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body" id="app-confirm-message"><?= e(t('common.confirm_default')) ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="button" class="btn btn-primary" id="app-confirm-ok"><?= e(t('btn.confirm')) ?></button>
            </div>
        </div>
    </div>
</div>
