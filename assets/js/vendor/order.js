// Butiran pesanan

var orderId = Number(queryParam('id'));
var orderPage = { order: null, loadedCoopId: null };

// Butang tukar status yang dibenarkan (pelayan tetap menyemak peralihan)
var NEXT_STATUS = {
    paid: ['preparing'],
    unpaid_cash: ['preparing'],
    preparing: ['ready'],
    ready: ['preparing']
};
var CANCELLABLE = ['pending_payment', 'unpaid_cash'];

function setText(id, value) {
    document.getElementById(id).textContent = (value === null || value === undefined || value === '') ? '—' : value;
}

function loadOrder() {
    Api.get('vendor/orders/get', withCoop({ id: orderId })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            document.getElementById('order-detail').classList.add('d-none');
            return;
        }
        orderPage.order = res.data;
        renderOrder(res.data);
    });
}

function renderOrder(order) {
    document.getElementById('page-title').textContent = T('order.order') + ' ' + order.order_no;
    setText('ord-short', order.short_code);
    setText('ord-no', order.order_no);
    setText('ord-created', formatDate(order.created_at, true));
    document.getElementById('ord-status').replaceChildren(statusBadge(order.status));
    setText('ord-subtotal', formatRM(order.subtotal));
    setText('ord-discount', order.discount ? '-' + formatRM(order.discount) : formatRM(0));
    setText('ord-total', formatRM(order.total));
    var isPos = order.channel === 'pos';
    setText('ord-customer', order.customer_name || (isPos ? T('pos.walk_in') : ''));
    setText('ord-phone', order.customer_phone);
    setText('ord-email', order.customer_email);
    document.getElementById('ord-payment').replaceChildren(paymentBadge(order));
    setText('ord-pickup-by', isPos ? T('pos.counter_sale') : T('pickup.by.' + order.pickup_by_type) + ': ' + order.pickup_by_name);
    // Slot ambil (modul pembukaan sekolah)
    var slot = order.pickup_slot;
    document.getElementById('ord-slot-label').classList.toggle('d-none', !slot);
    document.getElementById('ord-slot').classList.toggle('d-none', !slot);
    setText('ord-slot', slot ? formatDate(slot.slot_date) + ' · ' + slot.time_start + '–' + slot.time_end
        + ' · ' + (slot.grade_level_code ? T('grade.' + slot.grade_level_code) : T('store.slot.all_grades')) : '—');
    setText('ord-code', order.requires_code ? (order.pickup_locked ? T('order.code_locked') : T('order.code_required')) : T('order.code_not_required'));
    setText('ord-expires', order.expires_at ? formatDate(order.expires_at, true) : '—');

    document.getElementById('btn-label').href = pageUrl('vendor/bag-labels.php?order_id=' + order.id);
    document.getElementById('btn-label').classList.toggle('d-none', isPos);
    var canHandover = ['ready', 'partially_collected'].indexOf(order.status) !== -1;
    var handover = document.getElementById('btn-handover');
    handover.classList.toggle('d-none', !canHandover);
    handover.href = pageUrl('vendor/pos/handover.php?q=' + encodeURIComponent(order.short_code));

    renderGroups(order.groups);
    renderStatusActions(order);
    renderLogs(order.logs);
    renderPickups(order.pickups);
}

// Item dikumpul ikut anak
function renderGroups(groups) {
    var box = document.getElementById('ord-groups');
    box.replaceChildren();
    groups.forEach(function (group) {
        var card = createEl('div', 'card border-0 shadow-sm mb-3');
        var header = createEl('div', 'card-header bg-body d-flex align-items-center gap-2');
        header.appendChild(createIcon('bi-person-badge text-brand'));
        // Jualan POS tiada anak — satu kumpulan "Jualan kaunter"
        header.appendChild(createEl('span', 'fw-semibold', group.child_id ? group.child_name : T('pos.counter_sale')));
        if (group.child_id) {
            header.appendChild(createEl('span', 'badge text-bg-light border', childClass(group)));
        }
        card.appendChild(header);

        var wrap = createEl('div', 'table-responsive');
        var table = createEl('table', 'table align-middle mb-0');
        var head = createEl('thead', 'table-light');
        var headRow = createEl('tr');
        [T('field.product'), T('field.qty'), T('order.collected'), T('field.unit_price'), T('field.amount')].forEach(function (label, index) {
            headRow.appendChild(createEl('th', index === 0 ? '' : 'text-end', label));
        });
        head.appendChild(headRow);
        table.appendChild(head);
        var body = createEl('tbody');
        table.appendChild(body);
        wrap.appendChild(table);
        card.appendChild(wrap);
        box.appendChild(card);

        renderTable(body, group.items, [
            { render: function (item) {
                var stack = createStack(item.variant_label ? item.product_name + ' — ' + item.variant_label : item.product_name, item.sku);
                if (item.source === 'kit') {
                    stack.appendChild(createEl('span', 'badge text-bg-info', T('order.from_kit')));
                }
                return stack;
            } },
            { key: 'qty', className: 'text-end' },
            { className: 'text-end', render: function (item) {
                var done = item.collected_qty + item.cancelled_qty >= item.qty;
                var wrap = createEl('div');
                wrap.appendChild(createEl('span', done ? 'text-success fw-semibold' : (item.collected_qty ? 'text-warning-emphasis' : 'text-body-secondary'),
                    item.collected_qty + ' / ' + item.qty));
                if (item.cancelled_qty || item.returned_qty) {
                    wrap.appendChild(createEl('div', 'small text-danger', T('refund.item_refunded', { cancelled: item.cancelled_qty, returned: item.returned_qty })));
                }
                return wrap;
            } },
            { className: 'text-end', render: function (item) { return formatRM(item.unit_price); } },
            { className: 'text-end', render: function (item) { return formatRM(item.line_total); } }
        ]);
    });
}

function renderStatusActions(order) {
    var box = document.getElementById('status-actions');
    box.replaceChildren();
    (NEXT_STATUS[order.status] || []).forEach(function (status) {
        box.appendChild(createButton(T('order.mark_as', { status: T('order.status.' + status) }), status === 'ready' ? 'btn-success' : 'btn-brand', null, function () {
            changeStatus(status, '');
        }));
    });
    if (CANCELLABLE.indexOf(order.status) !== -1) {
        box.appendChild(createButton(T('order.cancel'), 'btn-outline-danger', null, function () {
            resetForm(document.getElementById('cancel-form'));
            showModal('cancel-modal');
        }));
    }
    if (order.payment_status === 'paid' && order.status !== 'refunded' && order.refunded_amount < order.total) {
        box.appendChild(createIconLinkText(pageUrl('vendor/refunds.php?order_id=' + order.id), 'bi-arrow-return-left', T('refund.create'), 'btn-outline-danger'));
        box.appendChild(createEl('div', 'small text-body-secondary', T('order.refund_hint')));
    }
    if (order.refunded_amount > 0) {
        box.appendChild(createEl('div', 'small text-danger', T('refund.order_refunded', { amount: formatRM(order.refunded_amount) })));
    }
    if (box.children.length === 0) {
        box.appendChild(createEl('div', 'small text-body-secondary', T('order.no_actions')));
    }
}

function changeStatus(status, note) {
    return Api.post('vendor/orders/set-status', withCoop({ id: orderId, status: status, note: note })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return res;
        }
        showToast(T('order.status_changed', { status: T('order.status.' + status) }), 'success');
        orderPage.order = res.data;
        renderOrder(res.data);
        return res;
    });
}

function renderLogs(logs) {
    var list = document.getElementById('ord-logs');
    list.replaceChildren();
    logs.slice().reverse().forEach(function (log) {
        var item = createEl('li', 'mb-2');
        var title = log.from_status === log.to_status ? (log.note || T('order.status.' + log.to_status)) : T('order.status.' + log.to_status);
        item.appendChild(createEl('div', 'fw-semibold', title));
        var meta = formatDate(log.created_at, true) + ' · ' + log.actor_name;
        item.appendChild(createEl('div', 'text-body-secondary', meta));
        if (log.note && log.from_status !== log.to_status) {
            item.appendChild(createEl('div', 'fst-italic', log.note));
        }
        list.appendChild(item);
    });
}

function renderPickups(pickups) {
    var list = document.getElementById('ord-pickups');
    list.replaceChildren();
    if (pickups.length === 0) {
        list.appendChild(createEl('li', 'list-group-item text-body-secondary', T('order.no_pickups')));
    }
    pickups.forEach(function (pickup) {
        var item = createEl('li', 'list-group-item');
        item.appendChild(createEl('div', 'fw-semibold', T('order.pickup_line', {
            who: T('pickup.by.' + pickup.picked_by_type), name: pickup.picked_by_name
        })));
        item.appendChild(createEl('div', 'text-body-secondary', formatDate(pickup.created_at, true) + ' · ' + T('order.method', { method: pickup.method }) + ' · ' + pickup.user_name));
        pickup.items.forEach(function (row) {
            item.appendChild(createEl('div', '', row.qty + ' × ' + (row.variant_label ? row.product_name + ' — ' + row.variant_label : row.product_name) + ' (' + row.child_name + ')'));
        });
        list.appendChild(item);
    });
}

bindForm('cancel-form', 'vendor/orders/set-status', function (data) {
    hideModal('cancel-modal');
    showToast(T('order.cancelled_toast'), 'success');
    orderPage.order = data;
    renderOrder(data);
}, function (payload) {
    payload.id = orderId;
    payload.status = 'cancelled';
    return withCoop(payload);
});

onCooperativeReady(function (coop) {
    if (orderPage.loadedCoopId !== null && orderPage.loadedCoopId !== coop.id) {
        window.location.href = pageUrl('vendor/orders.php');
        return;
    }
    orderPage.loadedCoopId = coop.id;
    loadOrder();
});
