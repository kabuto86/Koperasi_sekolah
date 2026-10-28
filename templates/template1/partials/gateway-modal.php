<?php // Bayaran online (B8): ringkasan sebelum ibu bapa dibawa ke halaman gateway vendor (redirect_url) ?>
<div class="modal fade" id="gateway-modal" tabindex="-1" aria-labelledby="gateway-title" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="gateway-title"><?= e(t('store.payment.title')) ?></h2>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-shield-lock display-6 text-brand" aria-hidden="true"></i>
                <p class="mt-2 mb-1" id="gateway-text"></p>
                <div class="fs-3 fw-bold" id="gateway-amount"></div>
                <div class="small text-body-secondary" id="gateway-ref"></div>
                <div class="alert alert-secondary small mt-3 mb-0"><?= e(t('store.payment.redirect_note')) ?></div>
                <div class="alert alert-warning small mt-2 mb-0 d-none" id="gateway-sandbox"><?= e(t('store.payment.sandbox_note')) ?></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" id="gateway-cancel"><?= e(t('store.payment.cancel')) ?></button>
                <a class="btn btn-brand" id="gateway-pay" href="#"><?= e(t('store.payment.pay')) ?> <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</div>
