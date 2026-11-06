<?php
// Modul add-on: Apps mudah alih ibu bapa (W4) — penggunaan apps koperasi semasa, notifikasi push 30 hari, pautan muat turun
// (tetapan owner) dan poster QR untuk dicetak / dikongsi dengan ibu bapa. Data: vendor/mobile-app/stats (ModuleAccess mobile_app).
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'mobile_app';
$pageTitle = t('module.mobile_app');
$activeMenu = 'module.mobile_app';
$pageActions = [['id' => 'btn-print-poster', 'label' => t('mobileapp.print_poster'), 'icon' => 'bi-printer', 'class' => 'btn-outline-secondary']];
$pageScripts = [View::asset('lib/qrcode/qrcode.js'), View::asset('js/vendor/addons/mobile_app.js')];
$pushEvents = GetMobileAppStatsJob::PUSH_EVENTS;
$stats = ['parents' => 'bi-people', 'app_parents' => 'bi-phone', 'devices' => 'bi-bell'];
require __DIR__ . '/../_layout/header.php';
?>
<div class="row g-3 mb-3 d-print-none">
    <?php foreach ($stats as $key => $icon): ?>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi <?= e($icon) ?> fs-2 text-brand" aria-hidden="true"></i>
                <div>
                    <div class="small text-body-secondary"><?= e(t('mobileapp.stat.' . $key)) ?></div>
                    <div class="fs-4 fw-semibold" id="ma-<?= e($key) ?>">—</div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6 d-print-none">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('mobileapp.push_title')) ?></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('mobileapp.event')) ?></th>
                            <th class="text-end"><?= e(t('messaging.status.sent')) ?></th>
                            <th class="text-end"><?= e(t('messaging.status.pending')) ?></th>
                            <th class="text-end"><?= e(t('messaging.status.failed')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pushEvents as $event): ?>
                        <tr>
                            <td><?= e(t('mobileapp.event.' . $event)) ?></td>
                            <td class="text-end" id="ma-push-<?= e($event) ?>-sent">0</td>
                            <td class="text-end" id="ma-push-<?= e($event) ?>-pending">0</td>
                            <td class="text-end" id="ma-push-<?= e($event) ?>-failed">0</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-body small text-body-secondary"><?= e(t('mobileapp.push_help')) ?></div>
        </div>
        <div class="alert alert-info small" role="note">
            <i class="bi bi-info-circle" aria-hidden="true"></i> <?= e(t('mobileapp.features')) ?>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="h5 mb-1" id="ma-poster-school"></h2>
                <p class="text-body-secondary"><?= e(t('mobileapp.poster_text')) ?></p>
                <div class="alert alert-warning small d-none d-print-none" id="ma-not-published"><?= e(t('mobileapp.not_published')) ?></div>
                <div class="row g-3 justify-content-center" id="ma-links">
                    <div class="col-6 col-md-4 d-none" id="ma-android">
                        <img class="img-fluid border rounded p-2 bg-white" id="ma-android-qr" alt="<?= e(t('mobileapp.qr_android')) ?>">
                        <a class="d-block small mt-1 text-break" id="ma-android-link" target="_blank" rel="noopener"><i class="bi bi-google-play" aria-hidden="true"></i> <?= e(t('mobileapp.android')) ?></a>
                    </div>
                    <div class="col-6 col-md-4 d-none" id="ma-ios">
                        <img class="img-fluid border rounded p-2 bg-white" id="ma-ios-qr" alt="<?= e(t('mobileapp.qr_ios')) ?>">
                        <a class="d-block small mt-1 text-break" id="ma-ios-link" target="_blank" rel="noopener"><i class="bi bi-apple" aria-hidden="true"></i> <?= e(t('mobileapp.ios')) ?></a>
                    </div>
                    <div class="col-6 col-md-4">
                        <img class="img-fluid border rounded p-2 bg-white" id="ma-store-qr" alt="<?= e(t('mobileapp.qr_store')) ?>">
                        <a class="d-block small mt-1 text-break" id="ma-store-link" target="_blank" rel="noopener"><i class="bi bi-globe" aria-hidden="true"></i> <?= e(t('mobileapp.web_store')) ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
