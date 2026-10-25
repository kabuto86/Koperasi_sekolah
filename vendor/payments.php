<?php
// Bayaran — gateway koperasi (kunci API sendiri, wang terus ke akaun vendor) + kaedah tunai/online (PELAN 13)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.payment_settings');
$activeMenu = 'menu.payments';
$subNavGroup = 'payments';
$subNavActive = 'vendor/payments.php';
$pageScripts = [View::asset('js/vendor/payments.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-7">
        <form class="card border-0 shadow-sm" id="gateway-form" novalidate>
            <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-semibold"><?= e(t('payment.gateway_title')) ?></div>
                    <div class="small text-body-secondary"><?= e(t('payment.gateway_help')) ?></div>
                </div>
                <span class="badge text-bg-light border" id="gateway-status"><?= e(t('common.loading')) ?></span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="gw-driver"><?= e(t('field.gateway')) ?></label>
                    <select class="form-select" id="gw-driver" name="gateway_code" required></select>
                    <div class="form-text"><?= e(t('payment.allowed_note')) ?></div>
                </div>
                <div id="gw-credentials"></div>
                <fieldset class="mb-3">
                    <legend class="form-label fs-6"><?= e(t('payment.mode')) ?></legend>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="mode" id="gw-mode-sandbox" value="sandbox" required>
                        <label class="btn btn-outline-secondary" for="gw-mode-sandbox"><i class="bi bi-cone-striped" aria-hidden="true"></i> <?= e(t('payment.mode.sandbox')) ?></label>
                        <input type="radio" class="btn-check" name="mode" id="gw-mode-live" value="live">
                        <label class="btn btn-outline-success" for="gw-mode-live"><i class="bi bi-lightning-charge" aria-hidden="true"></i> <?= e(t('payment.mode.live')) ?></label>
                    </div>
                    <div class="form-text"><?= e(t('payment.mode_help')) ?></div>
                </fieldset>
                <div class="small text-body-secondary"><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= e(t('gateway.secret_note')) ?></div>
            </div>
            <div class="card-footer bg-body d-flex flex-wrap justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btn-test-gateway"><i class="bi bi-plug" aria-hidden="true"></i> <?= e(t('payment.test')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>

    <div class="col-12 col-xl-5">
        <form class="card border-0 shadow-sm mb-3" id="methods-form" novalidate>
            <div class="card-header bg-body">
                <div class="fw-semibold"><?= e(t('payment.methods_title')) ?></div>
                <div class="small text-body-secondary"><?= e(t('payment.methods_help')) ?></div>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="pm-cash" name="accept_cash">
                    <label class="form-check-label" for="pm-cash"><i class="bi bi-cash-coin" aria-hidden="true"></i> <?= e(t('coop.accept_cash')) ?></label>
                </div>
                <p class="small text-body-secondary"><?= e(t('payment.cash_help')) ?></p>
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="pm-online" name="accept_online">
                    <label class="form-check-label" for="pm-online"><i class="bi bi-globe" aria-hidden="true"></i> <?= e(t('coop.accept_online')) ?></label>
                </div>
                <p class="small text-body-secondary mb-0"><?= e(t('payment.online_help')) ?></p>
            </div>
            <div class="card-footer bg-body text-end">
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="fw-semibold mb-2"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= e(t('payment.how_title')) ?></div>
                <ol class="small ps-3 mb-3">
                    <li><?= e(t('payment.how_1')) ?></li>
                    <li><?= e(t('payment.how_2')) ?></li>
                    <li><?= e(t('payment.how_3')) ?></li>
                </ol>
                <label class="form-label small fw-semibold" for="callback-url"><?= e(t('payment.callback_url')) ?></label>
                <input type="text" class="form-control form-control-sm font-monospace" id="callback-url" readonly>
                <div class="form-text"><?= e(t('payment.callback_help')) ?></div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
