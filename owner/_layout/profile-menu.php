<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" aria-label="<?= e(t('common.profile')) ?>">
        <i class="bi bi-person-circle" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= e(View::url('owner/profile.php')) ?>"><i class="bi bi-person me-2" aria-hidden="true"></i><?= e(t('common.profile')) ?></a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= e(View::url('owner/login.php')) ?>"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i><?= e(t('btn.logout')) ?></a></li>
    </ul>
</div>
