// Borang jualan murah (modal #sale-modal) — dikongsi senarai & butiran jualan

var saleForm = document.getElementById('sale-form');

function openSaleForm(sale) {
    document.getElementById('sale-modal-title').textContent = sale ? T('clearance.edit') : T('clearance.add');
    var today = localDay(new Date());
    fillForm(saleForm, sale || { is_active: true, starts_on: today, ends_on: today });
    showModal('sale-modal');
}

// onSaved(sale) dipanggil selepas pelayan menyimpan
function bindSaleForm(onSaved) {
    bindForm(saleForm, 'vendor/clearance/save', function (sale) {
        hideModal('sale-modal');
        showToast(T('common.saved'), 'success');
        onSaved(sale);
    }, function (payload) {
        payload.id = payload.id ? Number(payload.id) : null;
        return withCoop(payload);
    });
}
