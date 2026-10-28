<?php
// POS KAUNTER — jual terus kepada murid / walk-in (PELAN 5.2 modul 9, 17).
// Aliran: pilih juruwang + PIN → buka syif (tunai permulaan) → imbas / pilih produk → bayar tunai atau QR online
// → resit 58/80mm → ... → tutup syif (kira tunai laci). Medan imbas sentiasa difokus untuk pengimbas USB.
require __DIR__ . '/../../core/bootstrap.php';

$pageTitle = t('menu.pos');
$pageScripts = [
    View::asset('lib/qrcode/qrcode.js'),
    View::asset('js/vendor/pos-cart.js'),
    View::asset('js/vendor/pos.js'),
];
$keypad = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'clear', '0', 'back'];
require __DIR__ . '/../_layout/pos-header.php';
?>
<div class="d-flex justify-content-center align-items-center py-5" id="pos-loading" role="status">
    <div class="spinner-border text-brand" aria-hidden="true"></div>
    <span class="ms-2"><?= e(t('common.loading')) ?></span>
</div>

<!-- Langkah 1: log masuk PIN juruwang -->
<section class="container py-4 d-none" id="pos-login" aria-labelledby="pos-login-title">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-6">
            <form class="card border-0 shadow-sm" id="pin-form" novalidate>
                <div class="card-body p-4">
                    <h1 class="h4 mb-1" id="pos-login-title"><i class="bi bi-person-lock text-brand" aria-hidden="true"></i> <?= e(t('pos.login_title')) ?></h1>
                    <p class="text-body-secondary small" id="pos-login-coop"></p>

                    <fieldset class="mb-3">
                        <legend class="form-label fw-semibold fs-6"><?= e(t('pos.choose_cashier')) ?></legend>
                        <div class="row g-2" id="pos-cashiers"></div>
                        <div class="alert alert-warning d-none mb-0" id="pos-no-cashier">
                            <?= e(t('pos.no_cashier')) ?>
                            <a class="alert-link" href="<?= e(View::url('vendor/staff.php')) ?>"><?= e(t('menu.staff')) ?></a>
                        </div>
                    </fieldset>

                    <label class="form-label fw-semibold" for="pos-pin"><?= e(t('pos.pin')) ?></label>
                    <input type="password" class="form-control form-control-lg text-center app-otp-input app-pin-input mb-2" id="pos-pin" name="pin"
                           inputmode="numeric" pattern="[0-9]{4,6}" minlength="4" maxlength="6" autocomplete="off" required aria-describedby="pos-pin-help">
                    <div class="invalid-feedback text-center"><?= e(t('pos.pin_format')) ?></div>
                    <div class="form-text text-center mb-3" id="pos-pin-help"><?= e(t('pos.pin_help')) ?></div>

                    <div class="app-pos-keypad mx-auto mb-3" role="group" aria-label="<?= e(t('pos.keypad')) ?>">
                        <?php foreach ($keypad as $key): ?>
                        <?php if ($key === 'clear'): ?>
                        <button type="button" class="btn btn-outline-secondary btn-lg" data-key="clear" aria-label="<?= e(t('pos.key_clear')) ?>">C</button>
                        <?php elseif ($key === 'back'): ?>
                        <button type="button" class="btn btn-outline-secondary btn-lg" data-key="back" aria-label="<?= e(t('pos.key_back')) ?>"><i class="bi bi-backspace" aria-hidden="true"></i></button>
                        <?php else: ?>
                        <button type="button" class="btn btn-light border btn-lg fw-semibold" data-key="<?= e($key) ?>"><?= e($key) ?></button>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-brand btn-lg w-100"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> <?= e(t('pos.sign_in')) ?></button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Langkah 2: buka syif -->
<section class="container py-4 d-none" id="pos-shift" aria-labelledby="pos-shift-title">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <form class="card border-0 shadow-sm" id="shift-open-form" novalidate>
                <div class="card-body p-4">
                    <h1 class="h4 mb-1" id="pos-shift-title"><i class="bi bi-cash-stack text-brand" aria-hidden="true"></i> <?= e(t('pos.open_shift')) ?></h1>
                    <p class="text-body-secondary small" id="pos-shift-who"></p>
                    <label class="form-label fw-semibold" for="opening-cash"><?= e(t('pos.opening_cash')) ?></label>
                    <div class="input-group input-group-lg has-validation mb-1">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="opening-cash" name="opening_cash" data-type="money" min="0" step="0.01" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="form-text mb-3"><?= e(t('pos.opening_cash_help')) ?></div>
                    <button type="submit" class="btn btn-brand btn-lg w-100 mb-2"><i class="bi bi-unlock" aria-hidden="true"></i> <?= e(t('pos.open_shift')) ?></button>
                    <button type="button" class="btn btn-outline-secondary w-100" id="btn-switch-cashier"><?= e(t('pos.switch_cashier')) ?></button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Langkah 3: jualan -->
<section class="d-none" id="pos-sale" aria-label="<?= e(t('pos.tab_sale')) ?>">
    <div class="row g-0 app-pos-layout">
        <div class="col-12 col-lg-7 col-xl-8 d-flex flex-column app-pos-catalog">
            <div class="alert alert-warning rounded-0 border-0 border-bottom mb-0 py-2 small d-none" id="pos-closed" role="status">
                <i class="bi bi-calendar-x" aria-hidden="true"></i> <span id="pos-closed-text"></span>
            </div>
            <form class="p-2 p-lg-3 pb-2 border-bottom bg-body" id="pos-scan-form" role="search">
                <label class="visually-hidden" for="pos-scan"><?= e(t('pos.scan_label')) ?></label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-upc-scan" aria-hidden="true"></i></span>
                    <input type="search" class="form-control app-handover-input" id="pos-scan" name="q" autocomplete="off"
                           placeholder="<?= e(t('pos.scan_placeholder')) ?>" aria-describedby="pos-scan-hint">
                    <button type="submit" class="btn btn-brand"><i class="bi bi-plus-lg" aria-hidden="true"></i><span class="d-none d-sm-inline"> <?= e(t('btn.add')) ?></span></button>
                </div>
                <div class="form-text" id="pos-scan-hint"><?= e(t('pos.scan_hint')) ?></div>
                <div class="d-flex flex-nowrap overflow-auto gap-2 mt-2 pb-1" id="pos-categories" role="group" aria-label="<?= e(t('field.category')) ?>"></div>
            </form>
            <div class="flex-grow-1 overflow-auto p-2 p-lg-3 app-pos-scroll">
                <div class="row g-2" id="pos-products" aria-live="polite"></div>
                <div class="text-center text-body-secondary py-5 d-none" id="pos-no-products"><?= e(t('pos.no_products')) ?></div>
            </div>
        </div>

        <!-- Troli: lajur kanan (≥ 992px) atau panel tarik keluar (telefon / tablet menegak) -->
        <aside class="col-lg-5 col-xl-4 offcanvas-lg offcanvas-end bg-body border-start app-pos-cart" tabindex="-1" id="pos-cart" aria-labelledby="pos-cart-title-sm">
            <div class="offcanvas-header border-bottom py-2">
                <h2 class="offcanvas-title fs-5" id="pos-cart-title-sm"><?= e(t('pos.cart')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#pos-cart" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="d-flex flex-column h-100 w-100">
                <div class="d-flex align-items-center justify-content-between px-3 pt-3 pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="fs-5 mb-0 d-none d-lg-block" id="pos-cart-title"><i class="bi bi-cart3" aria-hidden="true"></i> <?= e(t('pos.cart')) ?></h2>
                        <span class="badge rounded-pill text-bg-brand" id="pos-cart-count">0</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-cart"><i class="bi bi-trash" aria-hidden="true"></i> <?= e(t('pos.clear_cart')) ?></button>
                </div>
                <div class="flex-grow-1 overflow-auto px-3 app-pos-scroll" id="pos-lines">
                    <div class="text-center text-body-secondary py-5" id="pos-cart-empty">
                        <i class="bi bi-cart display-6 d-block mb-2" aria-hidden="true"></i><?= e(t('pos.cart_empty')) ?>
                    </div>
                </div>
                <div class="border-top px-3 pt-2 pb-3 bg-body-tertiary">
                    <button class="btn btn-sm btn-link px-0 mb-1 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#pos-extras"
                            aria-expanded="false" aria-controls="pos-extras">
                        <i class="bi bi-ticket-perforated" aria-hidden="true"></i> <?= e(t('pos.extras')) ?>
                    </button>
                    <div class="collapse" id="pos-extras">
                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <label class="form-label small mb-1" for="pos-customer"><?= e(t('pos.customer_name')) ?></label>
                                <input type="text" class="form-control form-control-sm" id="pos-customer" maxlength="100" autocomplete="off" placeholder="<?= e(t('pos.walk_in')) ?>">
                            </div>
                            <div class="col-7">
                                <label class="form-label small mb-1" for="pos-coupon"><?= e(t('pos.coupon')) ?></label>
                                <div class="input-group input-group-sm has-validation">
                                    <input type="text" class="form-control text-uppercase" id="pos-coupon" maxlength="20" autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-coupon"><?= e(t('pos.apply')) ?></button>
                                    <div class="invalid-feedback" id="pos-coupon-error"></div>
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label small mb-1" for="pos-manual-discount"><?= e(t('pos.manual_discount')) ?></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">RM</span>
                                    <input type="number" class="form-control" id="pos-manual-discount" min="0" step="0.10" inputmode="decimal">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="small text-success d-none mb-2" id="pos-promo-line"></div>
                    <dl class="row small mb-2 gx-2">
                        <dt class="col-7 fw-normal"><?= e(t('field.subtotal')) ?></dt>
                        <dd class="col-5 text-end mb-1" id="pos-subtotal">RM 0.00</dd>
                        <dt class="col-7 fw-normal"><?= e(t('order.discount')) ?></dt>
                        <dd class="col-5 text-end mb-1 text-success" id="pos-discount">RM 0.00</dd>
                        <dt class="col-7 fw-normal d-none" id="pos-tax-label"></dt>
                        <dd class="col-5 text-end mb-1 d-none" id="pos-tax">RM 0.00</dd>
                    </dl>
                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                        <span class="fw-semibold"><?= e(t('field.total')) ?></span>
                        <span class="fs-2 fw-bold" id="pos-total">RM 0.00</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 d-grid">
                            <button type="button" class="btn btn-success btn-lg app-pos-pay" id="btn-pay-cash" disabled>
                                <i class="bi bi-cash-coin" aria-hidden="true"></i> <?= e(t('payment.method.cash')) ?> <kbd class="d-none d-xl-inline">F2</kbd>
                            </button>
                        </div>
                        <div class="col-6 d-grid">
                            <button type="button" class="btn btn-brand btn-lg app-pos-pay" id="btn-pay-online" disabled>
                                <i class="bi bi-qr-code" aria-hidden="true"></i> <?= e(t('pos.pay_qr')) ?> <kbd class="d-none d-xl-inline">F4</kbd>
                            </button>
                        </div>
                    </div>
                    <div class="small text-body-secondary mt-2 d-none" id="pos-online-off"><?= e(t('pos.online_unavailable')) ?></div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Bar troli di telefon / tablet menegak -->
    <div class="d-lg-none fixed-bottom p-2 bg-body border-top app-pos-cartbar">
        <button type="button" class="btn btn-brand btn-lg w-100 d-flex justify-content-between" data-bs-toggle="offcanvas" data-bs-target="#pos-cart" aria-controls="pos-cart">
            <span><i class="bi bi-cart3" aria-hidden="true"></i> <?= e(t('pos.cart')) ?> (<span id="pos-cartbar-count">0</span>)</span>
            <span id="pos-cartbar-total">RM 0.00</span>
        </button>
    </div>
</section>

<!-- Pilih varian -->
<div class="modal fade d-print-none" id="variant-modal" tabindex="-1" aria-labelledby="variant-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <form class="modal-content" id="variant-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="variant-title"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <fieldset>
                    <legend class="form-label fw-semibold fs-6"><?= e(t('pos.choose_variant')) ?></legend>
                    <div class="row g-2" id="variant-options"></div>
                </fieldset>
                <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="variant-minus" aria-label="<?= e(t('pos.qty_minus')) ?>"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                    <label class="visually-hidden" for="variant-qty"><?= e(t('field.qty')) ?></label>
                    <input type="number" class="form-control form-control-lg text-center app-int-input" id="variant-qty" min="1" value="1">
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="variant-plus" aria-label="<?= e(t('pos.qty_plus')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-brand btn-lg w-100" id="variant-add" disabled><?= e(t('pos.choose_variant')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Bayar tunai -->
<div class="modal fade d-print-none" id="cash-modal" tabindex="-1" aria-labelledby="cash-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="cash-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cash-title"><i class="bi bi-cash-coin" aria-hidden="true"></i> <?= e(t('pos.pay_cash')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="small text-body-secondary"><?= e(t('handover.amount_due')) ?></div>
                    <div class="display-5 fw-bold" id="cash-due">—</div>
                </div>
                <div class="small fw-semibold mb-1"><?= e(t('pos.quick_cash')) ?></div>
                <div class="d-flex flex-wrap gap-2 mb-3" id="cash-quick"></div>
                <label class="form-label" for="cash-received"><?= e(t('handover.amount_received')) ?></label>
                <div class="input-group input-group-lg has-validation">
                    <span class="input-group-text">RM</span>
                    <input type="number" class="form-control" id="cash-received" name="amount_received" min="0" step="0.01" inputmode="decimal" required>
                    <div class="invalid-feedback"><?= e(t('handover.cash_not_enough')) ?></div>
                </div>
                <div class="fs-4 fw-semibold mt-3 text-center" id="cash-change" aria-live="polite"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success btn-lg w-100" id="cash-confirm"><i class="bi bi-check2-circle" aria-hidden="true"></i> <?= e(t('pos.confirm_payment')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Bayar online (QR gateway) -->
<div class="modal fade d-print-none" id="online-modal" tabindex="-1" aria-labelledby="online-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="online-title"><i class="bi bi-qr-code" aria-hidden="true"></i> <?= e(t('pos.pay_qr')) ?></h2>
            </div>
            <div class="modal-body text-center">
                <div class="small text-body-secondary"><?= e(t('handover.amount_due')) ?></div>
                <div class="display-6 fw-bold mb-2" id="online-due">—</div>
                <img class="app-qr border rounded p-2 bg-white mb-2" id="online-qr" alt="<?= e(t('pos.qr_alt')) ?>">
                <div class="small text-body-secondary mb-1" id="online-gateway"></div>
                <div class="small font-monospace mb-3" id="online-ref"></div>
                <div class="d-flex justify-content-center align-items-center gap-2" id="online-waiting" role="status">
                    <div class="spinner-border spinner-border-sm text-brand" aria-hidden="true"></div>
                    <span><?= e(t('pos.waiting_payment')) ?></span>
                </div>
                <p class="small text-body-secondary mt-2 mb-0"><?= e(t('pos.qr_help')) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger w-100" id="online-cancel"><?= e(t('pos.cancel_payment')) ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Resit -->
<div class="modal fade d-print-none" id="receipt-modal" tabindex="-1" aria-labelledby="receipt-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="receipt-title"><i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> <span id="receipt-heading"></span></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body bg-body-tertiary">
                <div class="fs-5 fw-semibold text-center mb-2" id="receipt-change"></div>
                <div class="d-flex justify-content-center" id="receipt-paper"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-lg flex-grow-1" id="receipt-print-btn"><i class="bi bi-printer" aria-hidden="true"></i> <?= e(t('pos.print_receipt')) ?></button>
                <button type="button" class="btn btn-brand btn-lg flex-grow-1" id="receipt-new" data-bs-dismiss="modal"><?= e(t('pos.new_sale')) ?> <kbd>Enter</kbd></button>
            </div>
        </div>
    </div>
</div>

<!-- Tutup syif -->
<div class="modal fade d-print-none" id="close-shift-modal" tabindex="-1" aria-labelledby="close-shift-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="close-shift-title"><i class="bi bi-door-closed" aria-hidden="true"></i> <?= e(t('pos.close_shift')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <dl class="row small mb-3" id="shift-summary"></dl>
                <form id="shift-close-form" novalidate>
                    <label class="form-label fw-semibold" for="closing-cash"><?= e(t('pos.closing_cash')) ?></label>
                    <div class="input-group input-group-lg has-validation mb-1">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="closing-cash" name="closing_cash" data-type="money" min="0" step="0.01" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="fw-semibold mb-3" id="closing-variance" aria-live="polite"></div>
                    <label class="form-label" for="closing-note"><?= e(t('field.note')) ?></label>
                    <textarea class="form-control mb-3" id="closing-note" name="note" rows="2" maxlength="255"></textarea>
                    <button type="submit" class="btn btn-danger btn-lg w-100"><?= e(t('pos.close_shift_confirm')) ?></button>
                </form>
                <div class="d-none" id="shift-closed-box">
                    <div class="alert alert-success"><?= e(t('pos.shift_closed')) ?></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-grow-1" id="shift-print"><i class="bi bi-printer" aria-hidden="true"></i> <?= e(t('pos.print_shift')) ?></button>
                        <button type="button" class="btn btn-brand flex-grow-1" data-bs-dismiss="modal"><?= e(t('btn.close')) ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Jualan syif ini -->
<div class="offcanvas offcanvas-end d-print-none" tabindex="-1" id="sales-panel" aria-labelledby="sales-panel-title">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title fs-5" id="sales-panel-title"><?= e(t('pos.shift_sales')) ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?= e(t('btn.close')) ?>"></button>
    </div>
    <div class="offcanvas-body p-0">
        <dl class="row small px-3 pt-3 mb-0" id="sales-summary"></dl>
        <div class="list-group list-group-flush" id="sales-list"></div>
    </div>
</div>
<?php require __DIR__ . '/../_layout/pos-footer.php'; ?>
