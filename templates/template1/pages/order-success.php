<?php // Pesanan berjaya: no. pendek besar, QR, cetak slip untuk dimasukkan dalam beg ?>
<div class="store-screen">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm text-center mb-3">
                <div class="card-body p-4">
                    <i class="bi display-4 d-block" id="success-icon" aria-hidden="true"></i>
                    <h1 class="h4 mt-2" id="success-title"><?= e(t('common.loading')) ?></h1>
                    <p class="text-body-secondary mb-3" id="success-text"></p>
                    <div class="small text-body-secondary"><?= e(t('order.short_code')) ?></div>
                    <div class="store-big-code" id="success-code"></div>
                    <div class="my-3" id="success-qr"></div>
                    <div class="small text-body-secondary mb-3" id="success-order-no"></div>
                    <div class="alert alert-info small text-start" id="success-pickup"></div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning btn-lg d-none" type="button" id="success-pay"><i class="bi bi-credit-card" aria-hidden="true"></i> <?= e(t('store.order.pay_now')) ?></button>
                        <button class="btn btn-brand btn-lg" type="button" id="success-print"><i class="bi bi-printer" aria-hidden="true"></i> <?= e(t('store.slip.print')) ?></button>
                        <a class="btn btn-outline-secondary" href="#" id="success-detail"><?= e(t('store.order.view_detail')) ?></a>
                        <a class="btn btn-link" href="<?= e(View::storeUrl($schoolSlug)) ?>"><?= e(t('store.order.back_home')) ?></a>
                    </div>
                </div>
            </div>
            <h2 class="h6 text-body-secondary"><?= e(t('store.slip.preview')) ?></h2>
            <p class="small text-body-secondary"><?= e(t('store.slip.help')) ?></p>
        </div>
    </div>
</div>
<div class="store-slip-grid" id="slip-area"></div>
<?php require __DIR__ . '/../partials/gateway-modal.php'; ?>
