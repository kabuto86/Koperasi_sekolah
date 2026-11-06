<?php
// Modul add-on: Keahlian koperasi — ahli, syer (beli / keluar) dan dividen tahunan (W4, PELAN 5.3).
// Data: vendor/membership/* (ModuleAccess membership). Amaun & dividen dikira di pelayan. Tiada no. IC (PDPA).
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'membership';
$pageTitle = t('module.membership');
$activeMenu = 'module.membership';
$pageActions = [['id' => 'btn-add-member', 'label' => t('membership.add'), 'icon' => 'bi-person-plus']];
$pageScripts = [View::asset('js/vendor/addons/membership.js')];
$memberTypes = ['student', 'teacher', 'staff', 'parent', 'other'];
$memberStatuses = ['active', 'inactive'];
$thisYear = (int) date('Y');
require __DIR__ . '/../_layout/header.php';
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-body-secondary"><?= e(t('membership.active_members')) ?></div>
            <div class="fs-4 fw-semibold" id="mb-stat-members">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-body-secondary"><?= e(t('membership.total_shares')) ?></div>
            <div class="fs-4 fw-semibold" id="mb-stat-shares">—</div>
        </div></div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-members" data-bs-toggle="tab" data-bs-target="#pane-members" type="button" role="tab"
                aria-controls="pane-members" aria-selected="true"><i class="bi bi-people" aria-hidden="true"></i> <?= e(t('membership.members')) ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-dividends" data-bs-toggle="tab" data-bs-target="#pane-dividends" type="button" role="tab"
                aria-controls="pane-dividends" aria-selected="false"><i class="bi bi-cash-coin" aria-hidden="true"></i> <?= e(t('membership.dividends')) ?></button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-members" role="tabpanel" aria-labelledby="tab-members" tabindex="0">
        <div class="card border-0 shadow-sm">
            <div class="card-body border-bottom">
                <form class="row g-2" id="mb-filters" role="search">
                    <div class="col-12 col-md-6">
                        <label class="visually-hidden" for="mb-q"><?= e(t('btn.search')) ?></label>
                        <input type="search" class="form-control" id="mb-q" name="q" placeholder="<?= e(t('membership.search_placeholder')) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="visually-hidden" for="mb-type-filter"><?= e(t('membership.member_type')) ?></label>
                        <select class="form-select" id="mb-type-filter" name="member_type">
                            <option value=""><?= e(t('membership.all_types')) ?></option>
                            <?php foreach ($memberTypes as $type): ?>
                            <option value="<?= e($type) ?>"><?= e(t('membership.type.' . $type)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="visually-hidden" for="mb-status-filter"><?= e(t('common.status')) ?></label>
                        <select class="form-select" id="mb-status-filter" name="status">
                            <option value=""><?= e(t('common.all_statuses')) ?></option>
                            <?php foreach ($memberStatuses as $status): ?>
                            <option value="<?= e($status) ?>"><?= e(t('membership.status.' . $status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('membership.member_no')) ?></th>
                            <th><?= e(t('field.full_name')) ?></th>
                            <th><?= e(t('membership.member_type')) ?></th>
                            <th><?= e(t('membership.joined_on')) ?></th>
                            <th class="text-end"><?= e(t('membership.share_balance')) ?></th>
                            <th><?= e(t('common.status')) ?></th>
                            <th class="text-end"><?= e(t('common.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="mb-body"></tbody>
                </table>
            </div>
            <div class="card-footer bg-body" id="mb-pagination"></div>
        </div>
        <p class="small text-body-secondary mt-2"><?= e(t('membership.help')) ?></p>
    </div>

    <div class="tab-pane fade" id="pane-dividends" role="tabpanel" aria-labelledby="tab-dividends" tabindex="0">
        <div class="row g-3">
            <div class="col-12 col-xl-5">
                <form class="card border-0 shadow-sm" id="dv-form" novalidate>
                    <div class="card-header bg-body fw-semibold"><?= e(t('membership.declare_title')) ?></div>
                    <div class="card-body row g-3">
                        <div class="col-6">
                            <label class="form-label" for="dv-year"><?= e(t('membership.dividend_year')) ?></label>
                            <input type="number" class="form-control" id="dv-year" name="year" data-type="int" min="2000" max="<?= e($thisYear) ?>"
                                   value="<?= e($thisYear - 1) ?>" required>
                            <div class="invalid-feedback"><?= e(t('membership.year_invalid')) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="dv-rate"><?= e(t('membership.dividend_rate')) ?></label>
                            <div class="input-group has-validation">
                                <input type="text" inputmode="decimal" class="form-control" id="dv-rate" name="rate" required>
                                <span class="input-group-text">%</span>
                                <div class="invalid-feedback"><?= e(t('membership.rate_invalid')) ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-label"><?= e(t('membership.payout')) ?></div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payout" id="dv-payout-cash" value="cash" checked>
                                <label class="form-check-label" for="dv-payout-cash"><?= e(t('membership.payout.cash')) ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payout" id="dv-payout-reinvest" value="reinvest">
                                <label class="form-check-label" for="dv-payout-reinvest"><?= e(t('membership.payout.reinvest')) ?></label>
                            </div>
                        </div>
                        <div class="col-12 small text-body-secondary"><?= e(t('membership.dividend_help')) ?></div>
                    </div>
                    <div class="card-footer bg-body d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="btn-dv-preview"><i class="bi bi-calculator" aria-hidden="true"></i> <?= e(t('membership.preview')) ?></button>
                        <button type="submit" class="btn btn-brand"><i class="bi bi-check2-circle" aria-hidden="true"></i> <?= e(t('membership.declare')) ?></button>
                    </div>
                </form>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-body fw-semibold"><?= e(t('membership.dividend_history')) ?></div>
                    <ul class="list-group list-group-flush" id="dv-list"></ul>
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm d-none" id="dv-detail">
                    <div class="card-header bg-body d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-semibold flex-grow-1" id="dv-detail-title"></span>
                        <span id="dv-detail-status"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-dv-csv"><i class="bi bi-filetype-csv" aria-hidden="true"></i> <?= e(t('btn.export_csv')) ?></button>
                        <button type="button" class="btn btn-sm btn-success d-none" id="btn-dv-pay"><i class="bi bi-cash" aria-hidden="true"></i> <?= e(t('membership.mark_paid')) ?></button>
                        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btn-dv-delete"><i class="bi bi-trash" aria-hidden="true"></i> <?= e(t('btn.delete')) ?></button>
                    </div>
                    <div class="card-body small text-body-secondary" id="dv-detail-meta"></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= e(t('membership.member_no')) ?></th>
                                    <th><?= e(t('field.full_name')) ?></th>
                                    <th class="text-end"><?= e(t('membership.share_balance_year_end')) ?></th>
                                    <th class="text-end"><?= e(t('membership.dividend_amount')) ?></th>
                                </tr>
                            </thead>
                            <tbody id="dv-lines"></tbody>
                            <tfoot class="table-light fw-semibold"><tr id="dv-foot"></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="member-modal" tabindex="-1" aria-labelledby="member-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="member-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="member-modal-title"><?= e(t('membership.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="mf-name"><?= e(t('field.full_name')) ?></label>
                    <input type="text" class="form-control" id="mf-name" name="full_name" required maxlength="150">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="mf-type"><?= e(t('membership.member_type')) ?></label>
                    <select class="form-select" id="mf-type" name="member_type" required>
                        <?php foreach ($memberTypes as $type): ?>
                        <option value="<?= e($type) ?>"><?= e(t('membership.type.' . $type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6" id="mf-class-box">
                    <label class="form-label" for="mf-class"><?= e(t('field.class')) ?></label>
                    <input type="text" class="form-control" id="mf-class" name="class_name" maxlength="50">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="mf-phone"><?= e(t('field.phone')) ?></label>
                    <input type="tel" class="form-control" id="mf-phone" name="phone" maxlength="20">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="mf-email"><?= e(t('field.email')) ?></label>
                    <input type="email" class="form-control" id="mf-email" name="email" maxlength="191">
                    <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="mf-joined"><?= e(t('membership.joined_on')) ?></label>
                    <input type="date" class="form-control" id="mf-joined" name="joined_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="mf-status"><?= e(t('common.status')) ?></label>
                    <select class="form-select" id="mf-status" name="status">
                        <?php foreach ($memberStatuses as $status): ?>
                        <option value="<?= e($status) ?>"><?= e(t('membership.status.' . $status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="mf-note"><?= e(t('field.note')) ?></label>
                    <input type="text" class="form-control" id="mf-note" name="note" maxlength="255">
                </div>
                <div class="col-12 small text-body-secondary"><?= e(t('membership.pdpa_note')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="shares-modal" tabindex="-1" aria-labelledby="shares-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="shares-modal-title"><?= e(t('membership.shares')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <div class="flex-grow-1" id="sh-member"></div>
                    <div class="text-end">
                        <div class="small text-body-secondary"><?= e(t('membership.share_balance')) ?></div>
                        <div class="fs-4 fw-semibold" id="sh-balance">—</div>
                    </div>
                </div>
                <form class="row g-2 align-items-end border rounded p-2 mb-3" id="tx-form" novalidate>
                    <div class="col-6 col-md-3">
                        <label class="form-label small" for="tx-type"><?= e(t('membership.tx_type')) ?></label>
                        <select class="form-select" id="tx-type" name="type">
                            <option value="buy"><?= e(t('membership.tx.buy')) ?></option>
                            <option value="withdraw"><?= e(t('membership.tx.withdraw')) ?></option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small" for="tx-amount"><?= e(t('report.col.amount')) ?></label>
                        <input type="text" inputmode="decimal" class="form-control" id="tx-amount" name="amount" data-type="money" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small" for="tx-date"><?= e(t('report.col.date')) ?></label>
                        <input type="date" class="form-control" id="tx-date" name="transaction_date" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button type="submit" class="btn btn-brand"><?= e(t('membership.record')) ?></button>
                    </div>
                    <div class="col-12">
                        <label class="visually-hidden" for="tx-note"><?= e(t('field.note')) ?></label>
                        <input type="text" class="form-control form-control-sm" id="tx-note" name="note" maxlength="255" placeholder="<?= e(t('field.note')) ?>">
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= e(t('report.col.date')) ?></th>
                                <th><?= e(t('membership.tx_type')) ?></th>
                                <th class="text-end"><?= e(t('report.col.amount')) ?></th>
                                <th><?= e(t('field.note')) ?></th>
                            </tr>
                        </thead>
                        <tbody id="sh-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
