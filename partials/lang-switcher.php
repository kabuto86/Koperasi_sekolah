<?php
// Pemilih bahasa (ms/en/zh/ta). Menukar ?lang= dan dimuat semula oleh PHP.
// Pemboleh ubah: $switcherClass (pilihan, cth. 'btn-outline-light')
$switcherClass = $switcherClass ?? 'btn-outline-secondary';
?>
<div class="dropdown">
    <button class="btn btn-sm <?= e($switcherClass) ?> dropdown-toggle" type="button"
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= e(t('lang.label')) ?>">
        <i class="bi bi-translate" aria-hidden="true"></i>
        <span class="text-uppercase"><?= e(Lang::code()) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach (Config::get('languages') as $code): ?>
        <li>
            <a class="dropdown-item<?= $code === Lang::code() ? ' active' : '' ?>"
               href="<?= e(Lang::switchUrl($code)) ?>" lang="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
