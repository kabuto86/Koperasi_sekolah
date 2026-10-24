<?php
// Tetapan platform — nama, SMTP, SST, tempoh trial & tangguh
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.platform_settings');
$activeMenu = 'menu.platform_settings';
$pageScripts = [View::asset('js/owner/settings.js')];
require __DIR__ . '/_layout/header.php';
?>
<form id="settings-form" novalidate>
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('settings.general')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="set-name"><?= e(t('settings.platform_name')) ?></label>
                        <input type="text" class="form-control" id="set-name" name="platform_name" required maxlength="60">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-lang"><?= e(t('settings.default_language')) ?></label>
                        <select class="form-select" id="set-lang" name="default_language">
                            <?php foreach (Config::get('languages') as $code): ?>
                            <option value="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-support-email"><?= e(t('settings.support_email')) ?></label>
                        <input type="email" class="form-control" id="set-support-email" name="support_email" required>
                        <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-support-phone"><?= e(t('settings.support_phone')) ?></label>
                        <input type="tel" class="form-control" id="set-support-phone" name="support_phone">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('settings.billing')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="set-trial"><?= e(t('settings.trial_days')) ?></label>
                        <div class="input-group has-validation">
                            <input type="number" class="form-control" id="set-trial" name="trial_days" data-type="int" min="0" max="90" required>
                            <span class="input-group-text"><?= e(t('common.days')) ?></span>
                            <div class="invalid-feedback"><?= e(t('error.range', ['min' => 0, 'max' => 90])) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-grace"><?= e(t('settings.grace_days')) ?></label>
                        <div class="input-group has-validation">
                            <input type="number" class="form-control" id="set-grace" name="grace_days" data-type="int" min="0" max="60" required>
                            <span class="input-group-text"><?= e(t('common.days')) ?></span>
                            <div class="invalid-feedback"><?= e(t('error.range', ['min' => 0, 'max' => 60])) ?></div>
                        </div>
                        <div class="form-text"><?= e(t('settings.grace_help')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-sst"><?= e(t('settings.sst_rate')) ?></label>
                        <div class="input-group has-validation">
                            <input type="number" class="form-control" id="set-sst" name="sst_rate" data-type="int" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                            <div class="invalid-feedback"><?= e(t('error.range', ['min' => 0, 'max' => 100])) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-sst-no"><?= e(t('settings.sst_registration_no')) ?></label>
                        <input type="text" class="form-control" id="set-sst-no" name="sst_registration_no">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('settings.smtp')) ?></div>
                <div class="card-body row g-3">
                    <div class="col-sm-8">
                        <label class="form-label" for="set-smtp-host"><?= e(t('settings.smtp_host')) ?></label>
                        <input type="text" class="form-control" id="set-smtp-host" name="smtp_host" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="set-smtp-port"><?= e(t('settings.smtp_port')) ?></label>
                        <input type="number" class="form-control" id="set-smtp-port" name="smtp_port" data-type="int" min="1" max="65535" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="set-smtp-enc"><?= e(t('settings.smtp_encryption')) ?></label>
                        <select class="form-select" id="set-smtp-enc" name="smtp_encryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none"><?= e(t('common.none')) ?></option>
                        </select>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label" for="set-smtp-user"><?= e(t('settings.smtp_username')) ?></label>
                        <input type="text" class="form-control" id="set-smtp-user" name="smtp_username" autocomplete="off">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="set-smtp-pass"><?= e(t('settings.smtp_password')) ?></label>
                        <input type="password" class="form-control" id="set-smtp-pass" name="smtp_password" autocomplete="new-password">
                        <div class="form-text" id="smtp-pass-help"></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-from-address"><?= e(t('settings.mail_from_address')) ?></label>
                        <input type="email" class="form-control" id="set-from-address" name="mail_from_address" required>
                        <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="set-from-name"><?= e(t('settings.mail_from_name')) ?></label>
                        <input type="text" class="form-control" id="set-from-name" name="mail_from_name">
                    </div>
                </div>
                <div class="card-footer bg-body">
                    <label class="form-label small" for="test-email-to"><?= e(t('settings.test_email')) ?></label>
                    <div class="input-group input-group-sm">
                        <input type="email" class="form-control" id="test-email-to" placeholder="nama@contoh.my">
                        <button type="button" class="btn btn-outline-secondary" id="btn-test-email"><?= e(t('btn.submit')) ?></button>
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
