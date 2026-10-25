<?php
// Tajuk halaman panel + pautan kembali + butang tindakan.
// Pemboleh ubah: $pageTitle, $backUrl (pilihan),
//   $pageActions (pilihan): [['id' => 'btn-add', 'label' => t('btn.add'), 'icon' => 'bi-plus-lg', 'class' => 'btn-brand', 'href' => null]]
$backUrl = $backUrl ?? null;
$pageActions = $pageActions ?? [];
?>
<?php if ($backUrl): ?>
<a class="d-inline-block small mb-2 text-decoration-none d-print-none" href="<?= e($backUrl) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e(t('btn.back')) ?>
</a>
<?php endif; ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="h4 mb-0" id="page-title"><?= e($pageTitle) ?></h1>
    <?php if ($pageActions): ?>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($pageActions as $action): ?>
        <?php $actionClass = 'btn btn-sm ' . ($action['class'] ?? 'btn-brand'); ?>
        <?php if (!empty($action['href'])): ?>
        <a class="<?= e($actionClass) ?>" id="<?= e($action['id']) ?>" href="<?= e($action['href']) ?>">
            <i class="bi <?= e($action['icon'] ?? 'bi-plus-lg') ?>" aria-hidden="true"></i> <?= e($action['label']) ?>
        </a>
        <?php else: ?>
        <button type="button" class="<?= e($actionClass) ?>" id="<?= e($action['id']) ?>">
            <i class="bi <?= e($action['icon'] ?? 'bi-plus-lg') ?>" aria-hidden="true"></i> <?= e($action['label']) ?>
        </button>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
