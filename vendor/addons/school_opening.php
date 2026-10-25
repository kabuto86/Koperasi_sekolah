<?php
// Modul add-on: Pembukaan sekolah — musim pre-order kit, tarikh tutup tempahan, slot ambil ikut darjah & kapasiti (PELAN 5.3)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'school_opening';
$pageTitle = t('module.school_opening');
$activeMenu = 'module.school_opening';
$pageActions = [['id' => 'btn-add-season', 'label' => t('opening.add_season'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/addons/school_opening.js')];
require __DIR__ . '/../_layout/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= e(t('opening.seasons')) ?></div>
            <div class="list-group list-group-flush" id="season-list"></div>
        </div>
        <p class="small text-body-secondary mt-2"><?= e(t('opening.help')) ?></p>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1 min-w-0">
                    <h2 class="h6 mb-0" id="slot-title"><?= e(t('opening.slots')) ?></h2>
                    <div class="small text-body-secondary" id="slot-summary"></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-edit-season" disabled>
                    <i class="bi bi-pencil" aria-hidden="true"></i> <?= e(t('opening.edit_season')) ?>
                </button>
                <button type="button" class="btn btn-sm btn-brand" id="btn-add-slot" disabled>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('opening.add_slot')) ?>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('opening.slot_date')) ?></th>
                            <th><?= e(t('field.time')) ?></th>
                            <th><?= e(t('field.grade')) ?></th>
                            <th class="app-col-progress"><?= e(t('opening.booked')) ?></th>
                            <th class="text-end"><?= e(t('common.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="slots-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="season-modal" tabindex="-1" aria-labelledby="season-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="season-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="season-modal-title"><?= e(t('opening.add_season')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-sm-6">
                    <label class="form-label" for="season-year"><?= e(t('opening.school_year')) ?></label>
                    <input type="number" class="form-control" id="season-year" name="school_year" data-type="int" min="2020" max="2100" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="season-pickup"><?= e(t('opening.pickup_starts_on')) ?></label>
                    <input type="date" class="form-control" id="season-pickup" name="pickup_starts_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="season-opens"><?= e(t('opening.opens_at')) ?></label>
                    <input type="date" class="form-control" id="season-opens" name="opens_at" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="season-closes"><?= e(t('opening.closes_at')) ?></label>
                    <input type="date" class="form-control" id="season-closes" name="closes_at" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="season-note"><?= e(t('opening.note')) ?></label>
                    <textarea class="form-control" id="season-note" name="note" rows="2" maxlength="300"></textarea>
                    <div class="form-text"><?= e(t('opening.note_help')) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto d-none" id="season-delete"><i class="bi bi-trash" aria-hidden="true"></i> <?= e(t('btn.delete')) ?></button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="slot-modal" tabindex="-1" aria-labelledby="slot-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="slot-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="slot-modal-title"><?= e(t('opening.add_slot')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="slot-date"><?= e(t('opening.slot_date')) ?></label>
                    <input type="date" class="form-control" id="slot-date" name="slot_date" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-6">
                    <label class="form-label" for="slot-start"><?= e(t('opening.time_start')) ?></label>
                    <input type="time" class="form-control" id="slot-start" name="time_start" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-6">
                    <label class="form-label" for="slot-end"><?= e(t('opening.time_end')) ?></label>
                    <input type="time" class="form-control" id="slot-end" name="time_end" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="slot-capacity"><?= e(t('opening.capacity')) ?></label>
                    <input type="number" class="form-control" id="slot-capacity" name="capacity" data-type="int" min="1" max="1000" required>
                    <div class="form-text"><?= e(t('opening.capacity_help')) ?></div>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <fieldset class="col-12">
                    <legend class="form-label fs-6"><?= e(t('field.grade')) ?></legend>
                    <div class="d-flex flex-wrap gap-2" id="slot-grades"></div>
                    <div class="form-text" id="slot-grades-help"></div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
