<?php
// Borang tambah / sunting koperasi (PELAN 5.2 modul 2)
require __DIR__ . '/../core/bootstrap.php';

$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? t('coop.edit') : t('coop.add');
$activeMenu = 'menu.my_cooperatives';
$backUrl = View::url('vendor/cooperatives.php');
$pageScripts = [View::asset('js/vendor/cooperative-form.js')];
$malaysia = require APP_ROOT . '/config/malaysia.php';
require __DIR__ . '/_layout/header.php';
?>
<div class="alert d-none" id="pricing-alert" role="status"></div>

<form id="coop-form" novalidate>
    <input type="hidden" name="id">
    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('coop.section_school')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="coop-name"><?= e(t('coop.school_name')) ?></label>
                        <input type="text" class="form-control" id="coop-name" name="school_name" required maxlength="120">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="coop-code"><?= e(t('coop.school_code')) ?></label>
                        <input type="text" class="form-control text-uppercase" id="coop-code" name="school_code" required maxlength="10" pattern="[A-Za-z]{3}[0-9]{4}">
                        <div class="form-text"><?= e(t('coop.school_code_help')) ?></div>
                        <div class="invalid-feedback"><?= e(t('coop.school_code_format')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="coop-type"><?= e(t('field.school_type')) ?></label>
                        <select class="form-select" id="coop-type" name="school_type" required>
                            <option value=""><?= e(t('common.choose')) ?></option>
                            <?php foreach ($malaysia['school_types'] as $type): ?>
                            <option value="<?= e($type) ?>"><?= e(t('school_type.' . $type)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="coop-state"><?= e(t('field.state')) ?></label>
                        <select class="form-select" id="coop-state" name="state" required>
                            <option value=""><?= e(t('common.choose')) ?></option>
                            <?php foreach ($malaysia['states'] as $state): ?>
                            <option value="<?= e($state) ?>"><?= e($state) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="coop-district"><?= e(t('field.district')) ?></label>
                        <input type="text" class="form-control" id="coop-district" name="district" required maxlength="60">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="coop-address"><?= e(t('field.address')) ?></label>
                        <textarea class="form-control" id="coop-address" name="address" rows="2" required></textarea>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="coop-phone"><?= e(t('field.phone')) ?></label>
                        <input type="tel" class="form-control" id="coop-phone" name="phone" maxlength="20">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('coop.section_hours')) ?></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= e(t('coop.day')) ?></th>
                                <th><?= e(t('coop.open')) ?></th>
                                <th><?= e(t('coop.from')) ?></th>
                                <th><?= e(t('coop.to')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($malaysia['week_days'] as $day): ?>
                            <tr>
                                <td><?= e(t('day.' . $day)) ?></td>
                                <td>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input js-day-open" type="checkbox" role="switch" id="day-<?= e($day) ?>-open"
                                               name="opening_hours.<?= e($day) ?>.open" data-day="<?= e($day) ?>" aria-label="<?= e(t('coop.open') . ': ' . t('day.' . $day)) ?>">
                                    </div>
                                </td>
                                <td><input type="time" class="form-control form-control-sm app-time-input" name="opening_hours.<?= e($day) ?>.from"
                                           aria-label="<?= e(t('coop.from') . ': ' . t('day.' . $day)) ?>"></td>
                                <td><input type="time" class="form-control form-control-sm app-time-input" name="opening_hours.<?= e($day) ?>.to"
                                           aria-label="<?= e(t('coop.to') . ': ' . t('day.' . $day)) ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('coop.section_store')) ?></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="coop-slug"><?= e(t('coop.slug')) ?></label>
                        <input type="text" class="form-control font-monospace" id="coop-slug" name="slug" required maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                        <div class="form-text text-break" id="slug-status"><?= e(t('coop.slug_help')) ?></div>
                        <div class="invalid-feedback"><?= e(t('coop.slug_format')) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="form-label d-block"><?= e(t('coop.logo')) ?></span>
                        <div class="d-flex align-items-center gap-3">
                            <div class="app-logo-box app-logo-box-lg" id="logo-preview"><i class="bi bi-building" aria-hidden="true"></i></div>
                            <div>
                                <label class="btn btn-sm btn-outline-secondary mb-1" for="logo-file">
                                    <i class="bi bi-upload" aria-hidden="true"></i> <?= e(t('upload.choose_image')) ?>
                                </label>
                                <input type="file" class="visually-hidden" id="logo-file" accept="image/png,image/jpeg,image/webp">
                                <button type="button" class="btn btn-sm btn-link text-danger d-block p-0" id="logo-remove"><?= e(t('upload.remove')) ?></button>
                                <div class="form-text"><?= e(t('upload.image_help')) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="coop-template"><?= e(t('field.template')) ?></label>
                        <select class="form-select" id="coop-template" name="template_id" required></select>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div>
                        <label class="form-label" for="coop-language"><?= e(t('coop.default_language')) ?></label>
                        <select class="form-select" id="coop-language" name="default_language">
                            <?php foreach (Config::get('languages') as $code): ?>
                            <option value="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= e(t('coop.default_language_help')) ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('coop.section_payment')) ?></div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="coop-cash" name="accept_cash">
                        <label class="form-check-label" for="coop-cash"><?= e(t('coop.accept_cash')) ?></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="coop-online" name="accept_online">
                        <label class="form-check-label" for="coop-online"><?= e(t('coop.accept_online')) ?></label>
                        <div class="invalid-feedback"><?= e(t('coop.payment_required')) ?></div>
                    </div>
                    <div class="form-text"><?= e(t('coop.payment_help')) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(View::url('vendor/cooperatives.php')) ?>"><?= e(t('btn.cancel')) ?></a>
        <button type="submit" class="btn btn-brand" id="coop-submit"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= e(t('btn.save')) ?></button>
    </div>
</form>
<?php require __DIR__ . '/_layout/footer.php'; ?>
