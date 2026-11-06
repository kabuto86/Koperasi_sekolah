<?php
// Halaman bayaran awam untuk QR kaunter POS bila gateway koperasi memerlukan borang POST (iPay88) — W2.
// Pembeli mengimbas QR di skrin juruwang → halaman ini → borang gateway dihantar automatik. Tiada sesi diperlukan:
// akses dengan rujukan bil + token rawak dalam QR (GetPosPaymentFormJob). Status dibayar hanya melalui webhook / semakan gateway.
require __DIR__ . '/../core/bootstrap.php';

$ref = $_GET['ref'] ?? '';
$token = $_GET['t'] ?? '';
$form = (is_string($ref) && is_string($token) && preg_match('/^[A-Za-z0-9-]{1,100}$/', $ref))
    ? (new GetPosPaymentFormJob($ref, $token))->run() : null;
if ($form === null) {
    http_response_code(404);
} else {
    // CSP: benarkan borang ke asal (origin) gateway ini sahaja
    $origin = implode('/', array_slice(explode('/', $form['action']), 0, 3));
    SecurityHeaders::allowFormAction($origin);
}
header('Referrer-Policy: no-referrer');
$pageTitle = t('store.payment.title');
require APP_ROOT . '/partials/head.php';
?>
<main class="container py-5 text-center" id="main">
    <?php if ($form === null): ?>
        <i class="bi bi-exclamation-circle display-5 text-danger" aria-hidden="true"></i>
        <p class="mt-3"><?= e(t('pos.pay_link_invalid')) ?></p>
    <?php else: ?>
        <p class="fw-semibold mb-1"><?= e($form['school_name']) ?></p>
        <p class="small text-body-secondary mb-1"><?= e($form['order_no']) ?></p>
        <p class="fs-3 fw-bold mb-4"><?= e(Money::format($form['amount'])) ?></p>
        <div class="spinner-border text-brand" role="status" aria-hidden="true"></div>
        <p class="mt-3"><?= e(t('store.payment.redirecting')) ?></p>
        <form method="post" action="<?= e($form['action']) ?>" id="gateway-redirect-form">
            <?php foreach ($form['fields'] as $name => $value): ?>
                <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-brand"><?= e(t('store.payment.pay')) ?></button>
        </form>
    <?php endif; ?>
</main>
<script src="<?= e(View::asset('js/store/payment-redirect.js')) ?>"></script>
</body>
</html>
