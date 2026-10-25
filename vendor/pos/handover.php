<?php
// SERAH BARANG — skrin kaunter (PELAN 7). Mesti sangat mudah & boleh guna papan kekunci sahaja:
// taip/imbas → Enter → (terima tunai) → pilih siapa ambil → Enter → selesai → Enter → seterusnya
require __DIR__ . '/../../core/bootstrap.php';

$pageTitle = t('menu.handover');
$activeMenu = 'menu.handover';
$pageScripts = [View::asset('js/vendor/order-common.js'), View::asset('js/vendor/handover.js')];
require __DIR__ . '/../_layout/header.php';
?>
<form class="card border-0 shadow-sm mb-3" id="handover-search" role="search">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-7">
                <label class="form-label fw-semibold" for="handover-q"><?= e(t('handover.search_label')) ?></label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-upc-scan" aria-hidden="true"></i></span>
                    <input type="search" class="form-control app-handover-input" id="handover-q" name="q" autocomplete="off" autofocus
                           placeholder="<?= e(t('handover.search_placeholder')) ?>" aria-describedby="handover-hint">
                    <button type="submit" class="btn btn-brand"><?= e(t('btn.search')) ?></button>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="handover-grade"><?= e(t('field.grade')) ?></label>
                <select class="form-select form-select-lg" id="handover-grade" name="grade"><option value=""><?= e(t('common.all_grades')) ?></option></select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small" for="handover-class"><?= e(t('field.class')) ?></label>
                <select class="form-select form-select-lg" id="handover-class" name="class_name"><option value=""><?= e(t('order.all_classes')) ?></option></select>
            </div>
        </div>
        <div class="form-text" id="handover-hint"><?= e(t('handover.hint')) ?></div>
    </div>
</form>

<div aria-live="polite">
    <div class="text-body-secondary small mb-2" id="handover-status"></div>
    <div class="row g-3" id="handover-results"></div>
</div>

<!-- Terima bayaran tunai -->
<div class="modal fade" id="cash-modal" tabindex="-1" aria-labelledby="cash-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="cash-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cash-title"><?= e(t('handover.receive_cash')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="small text-body-secondary"><?= e(t('handover.amount_due')) ?></div>
                    <div class="display-6 fw-bold" id="cash-due">—</div>
                </div>
                <label class="form-label" for="cash-received"><?= e(t('handover.amount_received')) ?></label>
                <div class="input-group input-group-lg has-validation">
                    <span class="input-group-text">RM</span>
                    <input type="number" class="form-control" id="cash-received" name="amount_received" data-type="money" min="0" step="0.01" required>
                    <div class="invalid-feedback"><?= e(t('handover.cash_not_enough')) ?></div>
                </div>
                <div class="fs-5 mt-2" id="cash-change"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning btn-lg w-100"><i class="bi bi-cash-coin" aria-hidden="true"></i> <?= e(t('handover.confirm_cash')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Serah -->
<div class="modal fade" id="handover-modal" tabindex="-1" aria-labelledby="handover-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="handover-title"><?= e(t('handover.title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <form id="handover-form" novalidate>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="app-short-code" id="ho-code">—</div>
                        <div>
                            <div class="fw-semibold" id="ho-children">—</div>
                            <div class="small text-body-secondary" id="ho-parent"></div>
                        </div>
                    </div>

                    <fieldset class="mb-3">
                        <legend class="form-label fw-semibold fs-6"><?= e(t('handover.who_collects')) ?></legend>
                        <div class="btn-group w-100" role="group">
                            <?php foreach (['student', 'parent', 'proxy'] as $type): ?>
                            <input type="radio" class="btn-check" name="picked_by_type" id="ho-type-<?= e($type) ?>" value="<?= e($type) ?>" autocomplete="off">
                            <label class="btn btn-outline-secondary btn-lg" for="ho-type-<?= e($type) ?>"><?= e(t('pickup.by.' . $type)) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <div class="mb-3">
                        <label class="form-label" for="ho-name"><?= e(t('handover.collector_name')) ?></label>
                        <input type="text" class="form-control form-control-lg" id="ho-name" name="picked_by_name" required maxlength="100" autocomplete="off">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>

                    <div class="fw-semibold mb-1"><?= e(t('handover.items_to_hand')) ?></div>
                    <div class="small text-body-secondary mb-2"><?= e(t('handover.partial_help')) ?></div>
                    <div id="ho-items"></div>

                    <div class="mt-3 d-none" id="ho-code-section">
                        <label class="form-label fw-semibold" for="ho-pin"><?= e(t('handover.code_label')) ?></label>
                        <input type="text" class="form-control form-control-lg text-center app-otp-input app-pin-input" id="ho-pin" name="code"
                               inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off">
                        <div class="form-text"><?= e(t('handover.code_help')) ?></div>
                        <div class="invalid-feedback"><?= e(t('handover.code_format')) ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-lg w-100" id="ho-submit">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i> <?= e(t('handover.confirm')) ?>
                    </button>
                </div>
            </form>

            <div class="modal-body text-center py-5 d-none" id="ho-success" role="status">
                <i class="bi bi-check-circle-fill display-1 text-success" aria-hidden="true"></i>
                <div class="fs-4 fw-bold mt-2" id="ho-success-title"></div>
                <div class="text-body-secondary" id="ho-success-detail"></div>
                <div class="alert alert-success d-inline-flex align-items-center gap-2 mt-3 mb-4">
                    <i class="bi bi-envelope-check" aria-hidden="true"></i> <?= e(t('handover.parent_notified')) ?>
                </div>
                <div><button type="button" class="btn btn-brand btn-lg px-5" id="ho-next"><?= e(t('handover.next')) ?></button></div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
