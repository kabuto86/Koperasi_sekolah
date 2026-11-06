<?php
// Bayar invois langganan melalui gateway owner yang memerlukan borang POST (iPay88) — W2. Borang dibina pelayan semasa bil
// dicipta (PayInvoiceJob) dan dihantar automatik oleh assets/js/store/payment-redirect.js. Hanya bil milik vendor sesi.
require __DIR__ . '/../core/bootstrap.php';

VendorAuth::requirePage('menu.subscription');
$ref = $_GET['ref'] ?? '';
$form = (is_string($ref) && preg_match('/^[A-Za-z0-9-]{1,100}$/', $ref))
    ? (new GetInvoicePaymentFormJob(VendorAuth::vendorId(), $ref))->run() : null;
if ($form === null) {
    http_response_code(404);
} else {
    $origin = implode('/', array_slice(explode('/', $form['action']), 0, 3));
    SecurityHeaders::allowFormAction($origin);
}
$pageTitle = t('billing.pay_now');
require APP_ROOT . '/partials/head.php';
?>
<main class="container py-5 text-center" id="main">
    <?php if ($form === null): ?>
        <i class="bi bi-exclamation-circle display-5 text-danger" aria-hidden="true"></i>
        <p class="mt-3"><?= e(t('error.not_found')) ?></p>
        <a class="btn btn-outline-secondary" href="<?= e(View::url('vendor/invoices.php')) ?>"><?= e(t('btn.back')) ?></a>
    <?php else: ?>
        <p class="fw-semibold mb-1"><?= e($form['invoice_no']) ?></p>
        <p class="fs-3 fw-bold mb-4"><?= e(Money::format($form['amount'])) ?></p>
        <div class="spinner-border text-brand" role="status" aria-hidden="true"></div>
        <p class="mt-3"><?= e(t('store.payment.redirecting')) ?></p>
        <form method="post" action="<?= e($form['action']) ?>" id="gateway-redirect-form">
            <?php foreach ($form['fields'] as $name => $value): ?>
                <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-brand"><?= e(t('billing.pay_now')) ?></button>
        </form>
    <?php endif; ?>
</main>
<script src="<?= e(View::asset('js/store/payment-redirect.js')) ?>"></script>
</body>
</html>
