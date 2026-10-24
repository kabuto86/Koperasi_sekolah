<?php
// Senarai menu sidebar dari tatasusunan seksyen.
// Pemboleh ubah: $menuSections = [['title' => kunci, 'items' => [['key','icon','url'], ...]], ...]
//                $activeMenu = kunci item aktif
$activeMenu = $activeMenu ?? '';
?>
<nav class="app-sidebar-nav w-100" aria-label="<?= e(t('menu.section.main')) ?>">
    <?php foreach ($menuSections as $section): ?>
    <div class="app-sidebar-section"><?= e(t($section['title'])) ?></div>
    <ul class="nav flex-column">
        <?php foreach ($section['items'] as $item): ?>
        <?php $isActive = ($item['key'] === $activeMenu); ?>
        <li class="nav-item">
            <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= e(View::url($item['url'])) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= e(t($item['key'])) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endforeach; ?>
</nav>
