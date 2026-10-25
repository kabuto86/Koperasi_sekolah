<?php
// Tetapan koperasi — e-mel amaran, bahasa, resit, cara ambil barang A–E, tempoh luput tunai (PELAN 5.2 modul 16, PELAN 7)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.settings');
$activeMenu = 'menu.settings';
$pageScripts = [View::asset('js/vendor/settings.js')];
$pickupMethods = ['A' => 'bi-search', 'B' => 'bi-123', 'C' => 'bi-qr-code-scan', 'D' => 'bi-printer', 'E' => 'bi-key'];
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><i class="bi bi-building" aria-hidden="true"></i> <span id="settings-coop"></span></p>
<form id="settings-form" novalidate>
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><i class="bi bi-bell" aria-hidden="true"></i> <?= e(t('settings_v.alerts')) ?></div>
                <div class="card-body">
                    <label class="form-label" for="set-emails"><?= e(t('settings_v.alert_emails')) ?></label>
                    <textarea class="form-control font-monospace" id="set-emails" name="alert_emails" data-type="lines" rows="3" required></textarea>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    <div class="form-text"><?= e(t('settings_v.alert_emails_help')) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><i class="bi bi-hand-index" aria-hidden="true"></i> <?= e(t('settings_v.pickup')) ?></div>
                <div class="card-body">
                    <p class="small text-body-secondary"><?= e(t('settings_v.pickup_help')) ?></p>
                    <div class="list-group mb-3" id="set-methods">
                        <?php foreach ($pickupMethods as $code => $icon): ?>
                        <label class="list-group-item d-flex gap-3 align-items-start" for="set-method-<?= e($code) ?>">
                            <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" role="switch" id="set-method-<?= e($code) ?>"
                                   name="pickup_methods" value="<?= e($code) ?>" data-multi>
                            <span>
                                <span class="fw-semibold d-block"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i> <?= e(t('pickup.method.' . $code)) ?></span>
                                <span class="small text-body-secondary"><?= e(t('pickup.method_help.' . $code)) ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <label class="form-label" for="set-code-above"><?= e(t('settings_v.code_required_above')) ?></label>
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="text" inputmode="decimal" class="form-control" id="set-code-above" name="pickup_code_required_above" data-type="money" placeholder="0.00">
                    </div>
                    <div class="form-text"><?= e(t('settings_v.code_required_help')) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><i class="bi bi-hourglass-split" aria-hidden="true"></i> <?= e(t('settings_v.orders_stock')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="set-expiry"><?= e(t('settings_v.cash_expiry')) ?></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="set-expiry" name="cash_order_expiry_days" data-type="int" min="1" max="14" required>
                            <span class="input-group-text"><?= e(t('common.days')) ?></span>
                        </div>
                        <div class="form-text"><?= e(t('settings_v.cash_expiry_help')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-low"><?= e(t('settings_v.low_stock_default')) ?></label>
                        <input type="number" class="form-control" id="set-low" name="low_stock_default" data-type="int" min="0" required>
                        <div class="form-text"><?= e(t('settings_v.low_stock_help')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><i class="bi bi-translate" aria-hidden="true"></i> <?= e(t('settings_v.language')) ?></div>
                <div class="card-body">
                    <label class="form-label" for="set-lang"><?= e(t('coop.default_language')) ?></label>
                    <select class="form-select" id="set-lang" name="default_language" required>
                        <?php foreach (Config::get('languages') as $code): ?>
                        <option value="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><?= e(t('coop.default_language_help')) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><i class="bi bi-receipt" aria-hidden="true"></i> <?= e(t('settings_v.receipt')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="set-header"><?= e(t('settings_v.receipt_header')) ?></label>
                        <input type="text" class="form-control" id="set-header" name="receipt_header" maxlength="80">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="set-footer"><?= e(t('settings_v.receipt_footer')) ?></label>
                        <textarea class="form-control" id="set-footer" name="receipt_footer" rows="2" maxlength="200"></textarea>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-paper"><?= e(t('settings_v.receipt_paper')) ?></label>
                        <select class="form-select" id="set-paper" name="receipt_paper">
                            <option value="58">58 mm</option>
                            <option value="80">80 mm</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-tax"><?= e(t('settings_v.tax_rate')) ?></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="set-tax" name="tax_rate" data-type="int" min="0" max="10" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="set-logo" name="receipt_show_logo">
                            <label class="form-check-label" for="set-logo"><?= e(t('settings_v.receipt_logo')) ?></label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="small fw-semibold mb-1"><?= e(t('settings_v.receipt_preview')) ?></div>
                        <div class="border rounded bg-body-tertiary p-3 small font-monospace text-center" id="receipt-preview">
                            <div class="fw-bold" id="rp-header"></div>
                            <div class="text-body-secondary" id="rp-school"></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between"><span><?= e(t('settings_v.sample_item')) ?></span><span>RM 1.80</span></div>
                            <div class="d-flex justify-content-between fw-bold"><span><?= e(t('field.total')) ?></span><span>RM 1.80</span></div>
                            <hr class="my-2">
                            <div class="text-wrap" id="rp-footer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= e(t('btn.save')) ?></button>
    </div>
</form>
<?php require __DIR__ . '/_layout/footer.php'; ?>
