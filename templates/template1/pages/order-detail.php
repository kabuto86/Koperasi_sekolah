<?php // Butiran pesanan: status, item ikut anak, siapa ambil, rekod serahan, resit ?>
<div class="store-screen">
    <a class="btn btn-sm btn-link px-0 mb-2" href="<?= e(View::storeUrl($schoolSlug, 'orders')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e(t('store.nav.orders')) ?></a>
    <div id="detail-view">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h1 class="h5 mb-1" id="detail-title"><?= e(t('common.loading')) ?></h1>
                <div class="small text-body-secondary" id="detail-date"></div>
            </div>
            <div id="detail-status"></div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <section class="card border-0 shadow-sm mb-3">
                    <div class="card-body" id="detail-groups"></div>
                </section>
                <section class="card border-0 shadow-sm mb-3 d-none" id="detail-pickups-card">
                    <div class="card-body">
                        <h2 class="h6"><?= e(t('order.pickups')) ?></h2>
                        <ul class="list-unstyled small mb-0" id="detail-pickups"></ul>
                    </div>
                </section>
                <section class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6"><?= e(t('order.history')) ?></h2>
                        <ol class="list-unstyled small mb-0 store-timeline" id="detail-logs"></ol>
                    </div>
                </section>
            </div>
            <div class="col-12 col-lg-5">
                <section class="card border-0 shadow-sm mb-3 text-center d-none" id="detail-code-card">
                    <div class="card-body">
                        <div class="small text-body-secondary"><?= e(t('store.order.show_at_counter')) ?></div>
                        <div class="store-big-code" id="detail-code"></div>
                        <div class="my-2" id="detail-qr"></div>
                    </div>
                </section>
                <section class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6"><?= e(t('order.payment_pickup')) ?></h2>
                        <dl class="row small mb-2" id="detail-info"></dl>
                        <dl class="row small mb-0 border-top pt-2" id="detail-totals"></dl>
                    </div>
                </section>
                <div class="d-grid gap-2">
                    <button class="btn btn-warning d-none" type="button" id="detail-pay"><i class="bi bi-credit-card" aria-hidden="true"></i> <?= e(t('store.order.pay_now')) ?></button>
                    <button class="btn btn-brand" type="button" id="detail-print-slip"><i class="bi bi-printer" aria-hidden="true"></i> <?= e(t('store.slip.print')) ?></button>
                    <button class="btn btn-outline-secondary" type="button" id="detail-print-receipt"><i class="bi bi-receipt" aria-hidden="true"></i> <?= e(t('store.receipt.print')) ?></button>
                    <button class="btn btn-outline-danger d-none" type="button" id="detail-cancel"><i class="bi bi-x-circle" aria-hidden="true"></i> <?= e(t('order.cancel')) ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="store-slip-grid d-none d-print-grid" id="slip-area"></div>
<div class="store-receipt d-none" id="receipt-area"></div>
<?php require __DIR__ . '/../partials/gateway-modal.php'; ?>
