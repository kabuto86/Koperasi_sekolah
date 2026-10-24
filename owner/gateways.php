<?php
// Gateway bayaran — driver yang dibenarkan untuk vendor + gateway owner untuk kutip yuran (PELAN 13)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.gateways');
$activeMenu = 'menu.gateways';
$pageScripts = [View::asset('js/owner/gateways.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body">
                <div class="fw-semibold"><?= e(t('gateway.vendor_drivers')) ?></div>
                <div class="small text-body-secondary"><?= e(t('gateway.vendor_drivers_help')) ?></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('field.gateway')) ?></th>
                            <th class="text-center"><?= e(t('gateway.refund_support')) ?></th>
                            <th class="text-center"><?= e(t('gateway.vendors_using')) ?></th>
                            <th class="text-end"><?= e(t('gateway.allowed')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="drivers-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <form class="card border-0 shadow-sm" id="owner-gateway-form" novalidate>
            <div class="card-header bg-body">
                <div class="fw-semibold"><?= e(t('gateway.owner_gateway')) ?></div>
                <div class="small text-body-secondary"><?= e(t('gateway.owner_gateway_help')) ?></div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="owner-driver"><?= e(t('field.gateway')) ?></label>
                    <select class="form-select" id="owner-driver" name="driver_code" required></select>
                </div>
                <div id="owner-credentials"></div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="owner-sandbox" name="sandbox">
                    <label class="form-check-label" for="owner-sandbox"><?= e(t('gateway.sandbox')) ?></label>
                </div>
                <div class="form-text"><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= e(t('gateway.secret_note')) ?></div>
            </div>
            <div class="card-footer bg-body text-end">
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
