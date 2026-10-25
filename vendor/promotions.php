<?php
// Promosi asas — kupon, diskaun peratus/tetap, tempoh (PELAN 5.2 modul 13)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.promotions');
$activeMenu = 'menu.promotions';
$pageActions = [['id' => 'btn-add-promo', 'label' => t('promo.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/promotions.js')];
$promoStates = ['active', 'scheduled', 'expired', 'used_up', 'inactive'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="promo-filters" role="search">
            <div class="col-12 col-md-8">
                <label class="visually-hidden" for="promo-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="promo-q" name="q" placeholder="<?= e(t('promo.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="promo-state"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="promo-state" name="state">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($promoStates as $state): ?>
                    <option value="<?= e($state) ?>"><?= e(t('promo.state.' . $state)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.name')) ?></th>
                    <th><?= e(t('promo.discount')) ?></th>
                    <th><?= e(t('field.period')) ?></th>
                    <th class="text-center"><?= e(t('promo.usage')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="promos-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="promos-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('promo.help')) ?></p>

<div class="modal fade" id="promo-modal" tabindex="-1" aria-labelledby="promo-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="promo-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="promo-title"><?= e(t('promo.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="promo-name"><?= e(t('field.name')) ?></label>
                    <input type="text" class="form-control" id="promo-name" name="name" required maxlength="100">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="promo-code"><?= e(t('promo.code')) ?></label>
                    <input type="text" class="form-control font-monospace text-uppercase" id="promo-code" name="code" maxlength="20" pattern="[A-Za-z0-9\-]{3,20}">
                    <div class="invalid-feedback"><?= e(t('promo.code_format')) ?></div>
                    <div class="form-text"><?= e(t('promo.code_help')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="promo-type"><?= e(t('promo.type')) ?></label>
                    <select class="form-select" id="promo-type" name="type" required>
                        <option value="percent"><?= e(t('promo.type.percent')) ?></option>
                        <option value="fixed"><?= e(t('promo.type.fixed')) ?></option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="promo-value"><?= e(t('promo.value')) ?></label>
                    <div class="input-group">
                        <span class="input-group-text d-none" id="promo-value-rm">RM</span>
                        <input type="text" inputmode="decimal" class="form-control" id="promo-value" name="value" required>
                        <span class="input-group-text" id="promo-value-pct">%</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="promo-min"><?= e(t('promo.min_spend')) ?></label>
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="text" inputmode="decimal" class="form-control" id="promo-min" name="min_spend" data-type="money" placeholder="0.00">
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="promo-max"><?= e(t('promo.max_uses')) ?></label>
                    <input type="number" class="form-control" id="promo-max" name="max_uses" data-type="int" min="1" placeholder="<?= e(t('plan.unlimited')) ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="promo-start"><?= e(t('promo.starts_on')) ?></label>
                    <input type="date" class="form-control" id="promo-start" name="starts_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="promo-end"><?= e(t('promo.ends_on')) ?></label>
                    <input type="date" class="form-control" id="promo-end" name="ends_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="promo-active" name="is_active">
                        <label class="form-check-label" for="promo-active"><?= e(t('common.active')) ?></label>
                    </div>
                    <div class="form-text"><?= e(t('promo.server_note')) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
