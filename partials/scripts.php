<?php
// Skrip di hujung <body>. Susunan penting.
// Pemboleh ubah: $mockFiles (cth. ['vendor.js']), $pageScripts (senarai URL penuh)
$mockFiles = $mockFiles ?? [];
$pageScripts = $pageScripts ?? [];
?>
<script src="<?= e(View::asset('lib/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(View::asset('js/lang.js')) ?>"></script>
<script src="<?= e(View::asset('js/helpers.js')) ?>"></script>
<?php if (Config::get('use_mock')): ?>
<script src="<?= e(View::asset('js/mock/mock.js')) ?>"></script>
<?php foreach ($mockFiles as $mockFile): ?>
<script src="<?= e(View::asset('js/mock/' . $mockFile)) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
<script src="<?= e(View::asset('js/api.js')) ?>"></script>
<?php foreach ($pageScripts as $scriptUrl): ?>
<script src="<?= e($scriptUrl) ?>"></script>
<?php endforeach; ?>
</body>
</html>
