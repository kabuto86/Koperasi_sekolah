<?php
// Butiran vendor — maklumat, langganan, koperasi, invois, nota, gantung/aktifkan.
// Tiada "log masuk sebagai vendor" (PELAN 5.1)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('vendor.detail_title');
$activeMenu = 'menu.vendors';
$backUrl = View::url('owner/vendors.php');
// Gantung / aktifkan: kebenaran vendor.status; nota: vendor.note (PELAN 4.1 — API juga menolak)
$pageActions = !OwnerAuth::can('vendor.status') ? [] : [
    ['id' => 'btn-activate', 'label' => t('vendor.activate'), 'icon' => 'bi-check-circle', 'class' => 'btn-success d-none'],
    ['id' => 'btn-suspend', 'label' => t('vendor.suspend'), 'icon' => 'bi-pause-circle', 'class' => 'btn-outline-danger d-none'],
];
$pageScripts = [View::asset('js/owner/vendor-detail.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-danger d-none" id="vendor-error" role="alert"></div>

<div class="row g-3" id="vendor-detail">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><?= e(t('vendor.company_info')) ?></span>
                <span id="vendor-status"></span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5"><?= e(t('field.code')) ?></dt><dd class="col-7" id="vendor-code">—</dd>
                    <dt class="col-5"><?= e(t('field.registration_no')) ?></dt><dd class="col-7" id="vendor-registration">—</dd>
                    <dt class="col-5"><?= e(t('field.contact')) ?></dt><dd class="col-7" id="vendor-contact">—</dd>
                    <dt class="col-5"><?= e(t('field.email')) ?></dt><dd class="col-7 text-break" id="vendor-email">—</dd>
                    <dt class="col-5"><?= e(t('field.phone')) ?></dt><dd class="col-7" id="vendor-phone">—</dd>
                    <dt class="col-5"><?= e(t('field.address')) ?></dt><dd class="col-7" id="vendor-address">—</dd>
                    <dt class="col-5"><?= e(t('field.registered_at')) ?></dt><dd class="col-7 mb-0" id="vendor-created">—</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('vendor.subscription')) ?></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5"><?= e(t('field.plan')) ?></dt><dd class="col-7" id="sub-plan">—</dd>
                    <dt class="col-5"><?= e(t('field.billing_cycle')) ?></dt><dd class="col-7" id="sub-cycle">—</dd>
                    <dt class="col-5"><?= e(t('common.status')) ?></dt><dd class="col-7" id="sub-status">—</dd>
                    <dt class="col-5"><?= e(t('field.current_period')) ?></dt><dd class="col-7 mb-0" id="sub-period">—</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('menu.cooperatives')) ?></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('field.school')) ?></th>
                            <th><?= e(t('field.location')) ?></th>
                            <th><?= e(t('field.template')) ?></th>
                            <th><?= e(t('field.modules')) ?></th>
                            <th><?= e(t('common.status')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="coops-body"></tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('invoice.title_plural')) ?></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('field.invoice_no')) ?></th>
                            <th><?= e(t('field.period')) ?></th>
                            <th class="text-end"><?= e(t('field.total')) ?></th>
                            <th><?= e(t('common.status')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="invoices-body"></tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= e(t('vendor.notes')) ?></div>
            <div class="card-body">
                <?php if (OwnerAuth::can('vendor.note')): ?>
                <form id="note-form" class="mb-3" novalidate>
                    <label class="form-label visually-hidden" for="note-body"><?= e(t('vendor.add_note')) ?></label>
                    <textarea class="form-control mb-2" id="note-body" name="body" rows="2" required
                              placeholder="<?= e(t('vendor.note_placeholder')) ?>"></textarea>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    <button type="submit" class="btn btn-sm btn-brand"><?= e(t('vendor.add_note')) ?></button>
                </form>
                <?php endif; ?>
                <ul class="list-unstyled mb-0" id="notes-list"></ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="suspend-modal" tabindex="-1" aria-labelledby="suspend-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="suspend-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="suspend-title"><?= e(t('vendor.suspend')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary"><?= e(t('vendor.suspend_help')) ?></p>
                <label class="form-label" for="suspend-reason"><?= e(t('vendor.suspend_reason')) ?></label>
                <textarea class="form-control" id="suspend-reason" name="reason" rows="3" required></textarea>
                <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-danger"><?= e(t('vendor.suspend')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
