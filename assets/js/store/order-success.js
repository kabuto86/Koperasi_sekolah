// Pesanan berjaya: no. pendek, QR, arahan ambil, cetak slip

var STATUS_VIEW = {
    unpaid_cash: { icon: 'bi-check-circle-fill text-success', title: 'store.success.title_cash', text: 'store.success.text_cash' },
    paid: { icon: 'bi-check-circle-fill text-success', title: 'store.success.title_paid', text: 'store.success.text_paid' },
    pending_payment: { icon: 'bi-hourglass-split text-warning', title: 'store.success.title_pending', text: 'store.success.text_pending' }
};

// Arahan ambil barang ikut siapa ambil (PELAN 7)
function pickupInstruction(order) {
    var key = 'store.success.pickup.' + order.pickup_by_type;
    var text = T(key, { name: order.pickup_by_name, code: order.short_code });
    if (order.requires_code) {
        text += ' ' + T('store.success.pickup_code_note');
    }
    if (order.pickup_slot) {
        text += ' ' + T('store.slot.success_note', { slot: slotText(order.pickup_slot) });
    }
    return text;
}

function renderSuccess(order) {
    var view = STATUS_VIEW[order.status] || { icon: 'bi-receipt text-brand', title: 'store.success.title_other', text: 'store.success.text_other' };
    document.getElementById('success-icon').className = 'bi display-4 d-block ' + view.icon;
    document.getElementById('success-title').textContent = T(view.title);
    document.getElementById('success-text').textContent = T(view.text, { expires: formatDate(order.expires_at), total: formatRM(order.total), status: T('order.status.' + order.status) });
    document.getElementById('success-code').textContent = order.short_code;
    document.getElementById('success-qr').replaceChildren(qrImage(order.qr_token, T('order.qr_alt', { code: order.short_code }), 'store-qr'));
    document.getElementById('success-order-no').textContent = T('store.receipt.order_no', { no: order.order_no, code: order.short_code });
    document.getElementById('success-pickup').textContent = pickupInstruction(order);
    document.getElementById('success-detail').href = storeUrl('order-detail', { id: order.id });
    var pay = document.getElementById('success-pay');
    pay.classList.toggle('d-none', !order.can_pay);
    pay.onclick = function () { payOrder(order.id); };
    renderSlips(document.getElementById('slip-area'), order);
}

function payOrder(orderId) {
    storePost('store/orders/pay', { id: orderId }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        storeStartPayment(res.data.order_id, res.data.payment);
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    document.getElementById('success-print').addEventListener('click', function () { storePrint('slip'); });
    storeGet('store/orders/get', { id: queryParam('order') }).then(function (res) {
        if (!res.ok) {
            storeShowError(res.error);
            document.querySelector('.store-screen').classList.add('d-none');
            return;
        }
        renderSuccess(res.data);
    });
});
