<?php
// Modul add-on: Notifikasi WhatsApp/SMS — tetapan & templat mesej "sedia diambil" / "telah diambil" (PELAN 5.3).
// Peringkat 1: UI sahaja; penghantaran sebenar (notification_queue + cron) dalam B13.
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'whatsapp_sms';
$pageTitle = t('module.whatsapp_sms');
$activeMenu = 'module.whatsapp_sms';
$pageScripts = [View::asset('js/vendor/addons/whatsapp_sms.js')];
$providers = ['whatsapp_cloud', 'twilio', 'isms'];
$messageEvents = ['order_ready', 'order_collected', 'cash_reminder'];
$logStatuses = ['sent', 'pending', 'failed'];
require __DIR__ . '/../_layout/header.php';
?>
<div class="alert alert-info small d-flex gap-2" role="note">
    <i class="bi bi-info-circle" aria-hidden="true"></i>
    <div><?= e(t('messaging.ui_only_note')) ?></div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <form class="card border-0 shadow-sm" id="msg-form" novalidate>
            <div class="card-header bg-body fw-semibold"><?= e(t('messaging.settings')) ?></div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="msg-enabled" name="is_enabled">
                        <label class="form-check-label fw-semibold" for="msg-enabled"><?= e(t('messaging.enabled')) ?></label>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="msg-channel"><?= e(t('messaging.channel')) ?></label>
                    <select class="form-select" id="msg-channel" name="channel" required>
                        <option value="whatsapp"><?= e(t('messaging.channel.whatsapp')) ?></option>
                        <option value="sms"><?= e(t('messaging.channel.sms')) ?></option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="msg-provider"><?= e(t('messaging.provider')) ?></label>
                    <select class="form-select" id="msg-provider" name="provider" required>
                        <?php foreach ($providers as $provider): ?>
                        <option value="<?= e($provider) ?>"><?= e(t('messaging.provider.' . $provider)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="msg-sender"><?= e(t('messaging.sender')) ?></label>
                    <input type="text" class="form-control font-monospace" id="msg-sender" name="sender" inputmode="numeric" maxlength="13" placeholder="60123456789">
                    <div class="invalid-feedback"><?= e(t('messaging.sender_format')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="msg-key"><?= e(t('messaging.api_key')) ?></label>
                    <input type="password" class="form-control" id="msg-key" name="api_key" autocomplete="new-password">
                    <div class="form-text" id="msg-key-help"></div>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>

                <div class="col-12">
                    <h3 class="h6 mt-2 mb-1"><?= e(t('messaging.templates')) ?></h3>
                    <p class="small text-body-secondary mb-2"><?= e(t('messaging.templates_help')) ?></p>
                    <div class="d-flex flex-wrap gap-1 mb-3" id="msg-placeholders" aria-label="<?= e(t('messaging.placeholders')) ?>"></div>
                </div>
                <?php foreach ($messageEvents as $event): ?>
                <div class="col-12">
                    <div class="border rounded p-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="msg-event-<?= e($event) ?>" name="events.<?= e($event) ?>">
                            <label class="form-check-label fw-semibold" for="msg-event-<?= e($event) ?>"><?= e(t('messaging.event.' . $event)) ?></label>
                        </div>
                        <label class="visually-hidden" for="msg-tpl-<?= e($event) ?>"><?= e(t('messaging.template_for', ['event' => t('messaging.event.' . $event)])) ?></label>
                        <textarea class="form-control js-msg-template" id="msg-tpl-<?= e($event) ?>" name="templates.<?= e($event) ?>" rows="2" maxlength="500" required></textarea>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        <div class="form-text"><?= e(t('messaging.preview')) ?>: <span class="js-msg-preview" data-event="<?= e($event) ?>"></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-body text-end">
                <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>

    <div class="col-12 col-xl-5">
        <form class="card border-0 shadow-sm mb-3" id="msg-test-form" novalidate>
            <div class="card-header bg-body fw-semibold"><?= e(t('messaging.test')) ?></div>
            <div class="card-body">
                <label class="form-label" for="msg-test-phone"><?= e(t('field.phone')) ?></label>
                <div class="input-group has-validation">
                    <input type="tel" class="form-control" id="msg-test-phone" name="phone" placeholder="012-3456789" required>
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-send" aria-hidden="true"></i> <?= e(t('messaging.send_test')) ?></button>
                    <div class="invalid-feedback"><?= e(t('store.auth.phone_invalid')) ?></div>
                </div>
                <div class="form-text"><?= e(t('messaging.test_help')) ?></div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body d-flex align-items-center gap-2">
                <span class="fw-semibold flex-grow-1"><?= e(t('messaging.log')) ?></span>
                <form id="msg-log-filters">
                    <label class="visually-hidden" for="msg-log-status"><?= e(t('common.status')) ?></label>
                    <select class="form-select form-select-sm" id="msg-log-status" name="status">
                        <option value=""><?= e(t('common.all_statuses')) ?></option>
                        <?php foreach ($logStatuses as $status): ?>
                        <option value="<?= e($status) ?>"><?= e(t('messaging.status.' . $status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <ul class="list-group list-group-flush small" id="msg-log"></ul>
            <div class="card-footer bg-body" id="msg-log-pagination"></div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
