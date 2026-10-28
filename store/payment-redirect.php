<?php
// Gateway yang memerlukan borang POST (iPay88): borang dibina pelayan semasa bil dicipta (StartOnlinePaymentJob)
// dan dihantar secara automatik oleh assets/js/store/payment-redirect.js. Hanya bil milik ibu bapa sesi.
require __DIR__ . '/../core/bootstrap.php';

CustomerAuth::start();
$customerId = CustomerAuth::id();
$ref = $_GET['ref'] ?? '';
$form = ($customerId !== null && is_string($ref) && preg_match('/^[A-Za-z0-9-]{1,100}$/', $ref))
    ? (new GetPaymentFormJob($customerId, $ref))->run() : null;
if ($form === null) {
    http_response_code(404);
} else {
    // CSP: benarkan borang ke asal (origin) gateway ini sahaja
    $origin = implode('/', array_slice(explode('/', $form['action']), 0, 3));
    SecurityHeaders::allowFormAction($origin);
}
$pageTitle = t('store.payment.title');
require APP_ROOT . '/partials/head.php';
?>
<main class="container py-5 text-center" id="main">
    <?php if ($form === null): ?>
        <i class="bi bi-exclamation-circle display-5 text-danger" aria-hidden="true"></i>
        <p class="mt-3"><?= e(t('error.not_found')) ?></p>
        <a class="btn btn-outline-secondary" href="<?= e(View::storeUrl('', 'home')) ?>"><?= e(t('btn.back')) ?></a>
    <?php else: ?>
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
