<?php
// Label beg — nama anak, kelas, no. pendek, QR (untuk diletak pada beg barang)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('order.bag_labels');
$activeMenu = 'menu.orders';
$backUrl = View::url('vendor/orders.php');
$pageActions = [['id' => 'btn-print', 'label' => t('btn.print'), 'icon' => 'bi-printer', 'class' => 'btn-brand d-print-none']];
$extraCss = [View::asset('css/print-label.css')];
$pageScripts = [View::asset('lib/qrcode/qrcode.js'), View::asset('js/vendor/order-common.js'), View::asset('js/vendor/bag-labels.js')];
require __DIR__ . '/_layout/header.php';
?>
<form class="row g-2 mb-3 d-print-none" id="label-filters">
    <div class="col-6 col-md-3">
        <label class="form-label small" for="label-grade"><?= e(t('field.grade')) ?></label>
        <select class="form-select" id="label-grade" name="grade"><option value=""><?= e(t('common.all_grades')) ?></option></select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small" for="label-class"><?= e(t('field.class')) ?></label>
        <select class="form-select" id="label-class" name="class_name"><option value=""><?= e(t('order.all_classes')) ?></option></select>
    </div>
    <div class="col-12 col-md-3 js-slot-filter d-none">
        <label class="form-label small" for="label-slot"><?= e(t('store.slot.title')) ?></label>
        <select class="form-select" id="label-slot" name="pickup_slot_id"><option value=""><?= e(t('order.all_slots')) ?></option></select>
    </div>
    <div class="col-12 col-md-3 d-flex align-items-end small text-body-secondary" id="label-count"></div>
</form>
<div class="app-label-grid" id="label-grid"></div>
<p class="small text-body-secondary mt-3 d-print-none"><?= e(t('order.labels_help')) ?></p>
<?php require __DIR__ . '/_layout/footer.php'; ?>
