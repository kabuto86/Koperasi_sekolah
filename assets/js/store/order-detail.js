// Butiran pesanan ibu bapa

var detailOrder = null;

function infoRow(dl, label, value) {
    dl.appendChild(createEl('dt', 'col-5 fw-normal text-body-secondary', label));
    dl.appendChild(createEl('dd', 'col-7 mb-1', value));
}

function renderDetailGroups(order) {
    var box = document.getElementById('detail-groups');
    box.replaceChildren();
    order.groups.forEach(function (group, index) {
        var section = createEl('div', index ? 'mt-3 pt-3 border-top' : '');
        section.appendChild(childGroupHeader(group));
        var list = createEl('ul', 'list-unstyled small mb-0');
        group.items.forEach(function (item) {
            var row = createEl('li', 'd-flex justify-content-between gap-2 py-1');
            var name = createEl('div', 'min-w-0');
            name.appendChild(createEl('div', '', item.qty + ' × ' + item.product_name + (item.variant_label ? ' — ' + item.variant_label : '')));
            var notes = [];
            if (item.collected_qty > 0) {
                notes.push(T('store.order.collected_qty', { n: item.collected_qty, total: item.qty }));
            }
            if (item.cancelled_qty > 0 || item.returned_qty > 0) {
                notes.push(T('store.order.refunded_qty', { n: item.cancelled_qty + item.returned_qty }));
            }
            if (notes.length) {
                name.appendChild(createEl('div', 'text-body-secondary', notes.join(' · ')));
            }
            row.appendChild(name);
            row.appendChild(createEl('span', 'text-nowrap', formatRM(item.line_total)));
            list.appendChild(row);
        });
        section.appendChild(list);
        box.appendChild(section);
    });
}

function renderDetailInfo(order) {
    var info = document.getElementById('detail-info');
    info.replaceChildren();
    infoRow(info, T('field.payment_method'), T('payment.method.' + order.payment_method));
    infoRow(info, T('order.payment'), T('payment.status.' + order.payment_status));
    if (order.gateway_ref && order.payment_status === 'paid') {
        infoRow(info, T('field.reference'), order.gateway_ref);
    }
    if (order.status === 'unpaid_cash' && order.expires_at) {
        infoRow(info, T('order.expires_at'), formatDate(order.expires_at));
    }
    infoRow(info, T('order.pickup_by'), T('pickup.by.' + order.pickup_by_type) + (order.pickup_by_name ? ': ' + order.pickup_by_name : ''));
    if (order.pickup_slot) {
        infoRow(info, T('store.slot.title'), slotText(order.pickup_slot));
    }
    if (order.requires_code) {
        infoRow(info, T('order.pickup_code'), T('store.order.code_needed'));
    }
    renderTotals(document.getElementById('detail-totals'), order);
}

function renderPickups(order) {
    var list = document.getElementById('detail-pickups');
    list.replaceChildren();
    document.getElementById('detail-pickups-card').classList.toggle('d-none', order.pickups.length === 0);
    order.pickups.forEach(function (pickup) {
        var row = createEl('li', 'mb-2');
        row.appendChild(createEl('div', 'fw-semibold', T('store.order.picked_line', {
            who: T('pickup.by.' + pickup.picked_by_type), name: pickup.picked_by_name, time: formatDate(pickup.created_at, true) })));
        row.appendChild(createEl('div', 'text-body-secondary', pickup.items.map(function (item) {
            return item.qty + ' × ' + item.product_name + (item.variant_label ? ' (' + item.variant_label + ')' : '');
        }).join(', ')));
        list.appendChild(row);
    });
}

function renderLogs(order) {
    var list = document.getElementById('detail-logs');
    list.replaceChildren();
    order.logs.forEach(function (log) {
        var row = createEl('li', 'd-flex gap-2 mb-2');
        row.appendChild(createIcon('bi-circle-fill text-brand small mt-1'));
        row.appendChild(createStack(T('order.status.' + log.to_status), formatDate(log.created_at, true)));
        list.appendChild(row);
    });
}

function renderDetail(order) {
    detailOrder = order;
    document.getElementById('detail-title').textContent = T('store.order.title', { no: order.order_no });
    document.getElementById('detail-date').textContent = formatDate(order.created_at, true);
    document.getElementById('detail-status').replaceChildren(statusBadge(order.status));
    var active = ['cancelled', 'expired', 'refunded', 'collected'].indexOf(order.status) === -1;
    document.getElementById('detail-code-card').classList.toggle('d-none', !active);
    document.getElementById('detail-print-slip').classList.toggle('d-none', !active);
    document.getElementById('detail-code').textContent = order.short_code;
    document.getElementById('detail-qr').replaceChildren(qrImage(order.qr_token, T('order.qr_alt', { code: order.short_code }), 'store-qr'));
    document.getElementById('detail-pay').classList.toggle('d-none', !order.can_pay);
    document.getElementById('detail-cancel').classList.toggle('d-none', !order.can_cancel);
    renderDetailGroups(order);
    renderDetailInfo(order);
    renderPickups(order);
    renderLogs(order);
    renderSlips(document.getElementById('slip-area'), order);
    renderReceipt(document.getElementById('receipt-area'), order);
}

function cancelOrder() {
    confirmDialog(T('store.order.cancel_confirm', { no: detailOrder.order_no }), { okClass: 'btn-danger', okText: T('order.cancel') }).then(function (yes) {
        if (!yes) {
            return;
        }
        storePost('store/orders/cancel', { id: detailOrder.id }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('order.cancelled_toast'));
            renderDetail(res.data);
        });
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    document.getElementById('detail-print-slip').addEventListener('click', function () { storePrint('slip'); });
    document.getElementById('detail-print-receipt').addEventListener('click', function () { storePrint('receipt'); });
    document.getElementById('detail-cancel').addEventListener('click', cancelOrder);
    document.getElementById('detail-pay').addEventListener('click', function () {
        storePost('store/orders/pay', { id: detailOrder.id }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            storeStartPayment(res.data.order_id, res.data.payment);
        });
    });
    storeGet('store/orders/get', { id: queryParam('id') }).then(function (res) {
        if (!res.ok) {
            emptyState(document.getElementById('detail-view'), 'bi-receipt', res.error, T('store.nav.orders'), storeUrl('orders'));
            return;
        }
        renderDetail(res.data);
    });
});
