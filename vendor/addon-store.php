<?php
// Kedai modul add-on & template premium — dilanggan per koperasi, harga prorata (PELAN 5.3, 14)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.addon_store');
$activeMenu = 'menu.subscription';
$subNavGroup = 'billing';
$subNavActive = 'vendor/addon-store.php';
$pageScripts = [View::asset('js/vendor/addon-store.js')];
$addonIcons = [];
foreach (require __DIR__ . '/_layout/addons.php' as $addon) {
    $addonIcons[$addon['code']] = $addon['icon'];
}
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><i class="bi bi-building" aria-hidden="true"></i> <span id="store-coop"></span> · <?= e(t('addon.per_coop_note')) ?></p>

<h2 class="h6 text-uppercase text-body-secondary mb-2"><?= e(t('addon.modules_title')) ?></h2>
<div class="row g-3 mb-4" id="module-cards" data-icons="<?= e(json_encode($addonIcons)) ?>"></div>

<h2 class="h6 text-uppercase text-body-secondary mb-2"><?= e(t('addon.templates_title')) ?></h2>
<div class="row g-3" id="template-cards"></div>

<div class="modal fade" id="quote-modal" tabindex="-1" aria-labelledby="quote-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="quote-title"><?= e(t('addon.subscribe_title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="fw-semibold" id="quote-item"></div>
                <div class="small text-body-secondary mb-3" id="quote-coop"></div>
                <dl class="row small mb-2">
                    <dt class="col-7 fw-normal"><?= e(t('addon.full_price')) ?></dt>
                    <dd class="col-5 text-end mb-1" id="quote-unit"></dd>
                    <dt class="col-7 fw-normal"><?= e(t('addon.prorata_period')) ?></dt>
                    <dd class="col-5 text-end mb-1" id="quote-days"></dd>
                    <dt class="col-7 fw-normal"><?= e(t('addon.prorata_amount')) ?></dt>
                    <dd class="col-5 text-end mb-1" id="quote-amount"></dd>
                    <dt class="col-7 fw-normal" id="quote-sst-label"></dt>
                    <dd class="col-5 text-end mb-1" id="quote-tax"></dd>
                    <dt class="col-7"><?= e(t('addon.pay_now')) ?></dt>
                    <dd class="col-5 text-end fw-bold fs-6 mb-1" id="quote-total"></dd>
                </dl>
                <p class="small text-body-secondary mb-0" id="quote-note"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="button" class="btn btn-brand" id="btn-confirm-subscribe"><i class="bi bi-cart-check" aria-hidden="true"></i> <?= e(t('addon.confirm_subscribe')) ?></button>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
