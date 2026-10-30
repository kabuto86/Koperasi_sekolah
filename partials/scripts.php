<?php
// Skrip di hujung <body>. Susunan penting.
// Pemboleh ubah: $pageScripts (senarai URL penuh)
$pageScripts = $pageScripts ?? [];
?>
<script src="<?= e(View::asset('lib/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(View::asset('js/lang.js')) ?>"></script>
<script src="<?= e(View::asset('js/helpers.js')) ?>"></script>
<script src="<?= e(View::asset('js/api.js')) ?>"></script>
<?php foreach ($pageScripts as $scriptUrl): ?>
<script src="<?= e($scriptUrl) ?>"></script>
<?php endforeach; ?>
</body>
</html>
