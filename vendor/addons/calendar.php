<?php
// Modul add-on: Kalendar cuti — cuti sekolah / koperasi; tempahan online ditutup automatik pada hari tutup (PELAN 5.3)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'calendar';
$pageTitle = t('module.calendar');
$activeMenu = 'module.calendar';
$pageActions = [['id' => 'btn-add-event', 'label' => t('calendar.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/addons/calendar.js')];
$malaysia = require APP_ROOT . '/config/malaysia.php';
$eventTypes = ['school_holiday', 'coop_closed', 'school_opening', 'other'];
require __DIR__ . '/../_layout/header.php';
?>
<div class="alert d-flex align-items-center gap-2 d-none" id="cal-today" role="status">
    <i class="bi fs-4" id="cal-today-icon" aria-hidden="true"></i>
    <div id="cal-today-text"></div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="<?= e(t('calendar.navigate')) ?>">
                    <button type="button" class="btn btn-outline-secondary" id="cal-prev" aria-label="<?= e(t('calendar.prev_month')) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-outline-secondary" id="cal-current"><?= e(t('calendar.this_month')) ?></button>
                    <button type="button" class="btn btn-outline-secondary" id="cal-next" aria-label="<?= e(t('calendar.next_month')) ?>"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                </div>
                <h2 class="h6 mb-0 flex-grow-1 text-capitalize" id="cal-month" aria-live="polite">—</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0 app-calendar">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($malaysia['week_days'] as $day): ?>
                            <th scope="col" class="text-center small">
                                <span class="d-none d-md-inline"><?= e(t('day.' . $day)) ?></span>
                                <span class="d-md-none"><?= e(t('calendar.day_short.' . $day)) ?></span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="cal-grid"></tbody>
                </table>
            </div>
            <div class="card-footer bg-body small d-flex flex-wrap gap-3">
                <?php foreach ($eventTypes as $type): ?>
                <span><span class="app-cal-dot app-cal-<?= e($type) ?>" aria-hidden="true"></span> <?= e(t('calendar.type.' . $type)) ?></span>
                <?php endforeach; ?>
                <span><i class="bi bi-slash-circle text-danger" aria-hidden="true"></i> <?= e(t('calendar.blocks_orders')) ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= e(t('calendar.events_this_month')) ?></div>
            <ul class="list-group list-group-flush" id="cal-list"></ul>
        </div>
        <p class="small text-body-secondary mt-2"><?= e(t('calendar.help')) ?></p>
    </div>
</div>

<div class="modal fade" id="event-modal" tabindex="-1" aria-labelledby="event-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="event-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="event-modal-title"><?= e(t('calendar.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="event-title"><?= e(t('calendar.title')) ?></label>
                    <input type="text" class="form-control" id="event-title" name="title" required maxlength="100">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="event-type"><?= e(t('calendar.type')) ?></label>
                    <select class="form-select" id="event-type" name="event_type" required>
                        <?php foreach ($eventTypes as $type): ?>
                        <option value="<?= e($type) ?>"><?= e(t('calendar.type.' . $type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="event-start"><?= e(t('promo.starts_on')) ?></label>
                    <input type="date" class="form-control" id="event-start" name="starts_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="event-end"><?= e(t('promo.ends_on')) ?></label>
                    <input type="date" class="form-control" id="event-end" name="ends_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="event-blocks" name="blocks_orders">
                        <label class="form-check-label" for="event-blocks"><?= e(t('calendar.blocks_orders')) ?></label>
                    </div>
                    <div class="form-text"><?= e(t('calendar.blocks_help')) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto d-none" id="event-delete"><i class="bi bi-trash" aria-hidden="true"></i> <?= e(t('btn.delete')) ?></button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
