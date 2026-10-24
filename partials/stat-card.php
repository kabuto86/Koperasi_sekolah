<?php
// Kad statistik untuk papan pemuka. Nilai diisi oleh JS melalui id.
// Pemboleh ubah: $label, $icon, $valueId, $subId (pilihan), $tone (pilihan: brand|success|warning|danger|info)
$tone = $tone ?? 'brand';
$subId = $subId ?? null;
?>
<div class="card border-0 shadow-sm h-100">
    <div class="card-body d-flex align-items-start gap-3">
        <div class="app-stat-icon app-stat-<?= e($tone) ?>"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></div>
        <div class="min-w-0">
            <div class="small text-body-secondary"><?= e($label) ?></div>
            <div class="fs-4 fw-bold text-truncate" id="<?= e($valueId) ?>">—</div>
            <?php if ($subId): ?>
            <div class="small text-body-secondary" id="<?= e($subId) ?>"></div>
            <?php endif; ?>
        </div>
    </div>
</div>
