<?php
// Navigasi kecil antara halaman berkaitan (cth. Produk | Kategori).
// Pemboleh ubah: $subNav = [['key' => 'menu.products', 'url' => 'vendor/products.php', 'icon' => 'bi-box-seam']],
//                $subNavActive = url aktif
?>
<ul class="nav nav-pills nav-fill flex-nowrap overflow-auto mb-3 app-sub-nav">
    <?php foreach ($subNav as $item): ?>
    <?php $isActive = ($item['url'] === $subNavActive); ?>
    <li class="nav-item">
        <a class="nav-link text-nowrap<?= $isActive ? ' active' : '' ?>" href="<?= e(View::url($item['url'])) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i> <?= e(t($item['key'])) ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
