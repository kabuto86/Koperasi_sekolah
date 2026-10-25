// Pemulangan & refund

var refundList = { page: 1, per_page: 20, q: '', method: '', date_from: '', date_to: '' };
var refundForm = document.getElementById('refund-form');
var refundState = { order: null };

function loadRefunds() {
    tableLoading('refunds-body', 7);
    Api.get('vendor/refunds/list', withCoop(refundList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('refunds-body', res.data.items, [
            { render: function (row) { return createStack(row.refund_no, formatDate(row.created_at, true)); } },
            { render: function (row) {
                return createStack(createLink(row.order_no, pageUrl('vendor/order.php?id=' + row.order_id), 'fw-semibold'), row.short_code + ' · ' + (row.customer_name || T('pos.walk_in')));
            } },
            { className: 'text-end fw-semibold', render: function (row) { return formatRM(row.amount); } },
            { render: function (row) { return T('refund.method.' + row.method); } },
            { render: function (row) {
                var wrap = createEl('div', 'small');
                wrap.appendChild(createEl('div', '', T('refund.item_units', { n: row.item_count })));
                if (row.item_count && row.restock) {
                    wrap.appendChild(createEl('span', 'badge text-bg-success', T('refund.restocked')));
                }
                return wrap;
            } },
            { className: 'small', key: 'reason' },
            { className: 'small', key: 'user_name' }
        ]);
        renderPagination('refunds-pagination', res.data, function (page) {
            refundList.page = page;
            loadRefunds();
        });
    });
}

// ---------- Borang refund ----------

function openRefundModal(orderId) {
    var lookup = document.getElementById('refund-lookup');
    resetForm(lookup);
    resetForm(refundForm);
    refundForm.classList.add('d-none');
    document.getElementById('rf-submit').disabled = true;
    refundState.order = null;
    showModal('refund-modal');
    if (orderId) {
        findOrder({ order_id: orderId });
    } else {
        setTimeout(function () { document.getElementById('lookup-q').focus(); }, 300);
    }
}

function findOrder(params) {
    Api.get('vendor/refunds/order', withCoop(params)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderRefundOrder(res.data);
    });
}

function renderRefundOrder(order) {
    refundState.order = order;
    resetForm(refundForm);
    refundForm.classList.remove('d-none');
    document.getElementById('rf-submit').disabled = order.refundable_amount <= 0;
    document.getElementById('lookup-q').value = order.order_no;
    document.getElementById('rf-order-no').textContent = order.order_no + ' · ' + order.short_code;
    document.getElementById('rf-order-meta').textContent = (order.customer_name || T('pos.walk_in')) + ' · ' + T('order.status.' + order.status) + ' · ' + T('payment.method.' + order.payment_method);
    document.getElementById('rf-total').textContent = formatRM(order.total);
    document.getElementById('rf-refunded').textContent = formatRM(order.refunded_amount);
    document.getElementById('rf-refundable').textContent = formatRM(order.refundable_amount);
    document.getElementById('rf-restock').checked = true;

    renderTable('rf-items', order.items, [
        { render: function (item) {
            return createStack(item.variant_label ? item.product_name + ' — ' + item.variant_label : item.product_name, item.child_name);
        } },
        { className: 'text-end', render: function (item) { return formatRM(item.unit_price); } },
        { className: 'text-center small', render: function (item) {
            var wrap = createEl('div', '', item.collected_qty + ' / ' + item.qty);
            if (item.cancelled_qty || item.returned_qty) {
                wrap.appendChild(createEl('div', 'text-danger', T('refund.item_refunded', { cancelled: item.cancelled_qty, returned: item.returned_qty })));
            }
            return wrap;
        } },
        { className: 'text-end', render: function (item) {
            var input = createEl('input', 'form-control form-control-sm app-int-input ms-auto js-return-qty');
            input.type = 'number';
            input.min = '0';
            input.max = String(item.returnable_qty);
            input.value = '0';
            input.disabled = item.returnable_qty <= 0;
            input.setAttribute('data-item-id', item.id);
            input.setAttribute('data-price', item.unit_price);
            input.setAttribute('aria-label', T('refund.return_qty') + ': ' + item.product_name);
            input.addEventListener('input', updateRefundAmount);
            return input;
        } }
    ]);

    var method = document.getElementById('rf-method');
    var gatewayOption = method.querySelector('option[value="gateway"]');
    gatewayOption.disabled = !order.gateway_supports_refund;
    method.value = order.payment_method === 'cash' ? 'cash' : (order.gateway_supports_refund ? 'gateway' : 'bank_transfer');
    document.getElementById('rf-method-help').textContent = order.payment_method === 'online'
        ? (order.gateway_supports_refund ? T('refund.gateway_auto', { name: order.gateway_name }) : T('refund.gateway_manual', { name: order.gateway_name || '—' }))
        : '';
    updateRefundAmount();
}

function returnLines() {
    return Array.prototype.map.call(refundForm.querySelectorAll('.js-return-qty'), function (input) {
        var max = Number(input.max);
        var qty = Math.min(max, Math.max(0, parseInt(input.value, 10) || 0));
        return { item_id: Number(input.getAttribute('data-item-id')), qty: qty, price: Number(input.getAttribute('data-price')) };
    }).filter(function (line) { return line.qty > 0; });
}

// Amaun cadangan = harga barang dipulang (tidak melebihi baki boleh direfund). Boleh diubah (cth. refund separa)
function updateRefundAmount() {
    var sum = returnLines().reduce(function (total, line) { return total + line.qty * line.price; }, 0);
    var amount = Math.min(sum, refundState.order.refundable_amount);
    document.getElementById('rf-amount').value = amount ? senToInput(amount) : '';
}

refundForm.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!refundState.order || !validateForm(refundForm)) {
        return;
    }
    var payload = withCoop(formToObject(refundForm));
    payload.order_id = refundState.order.id;
    payload.items = returnLines().map(function (line) { return { item_id: line.item_id, qty: line.qty }; });

    confirmDialog(T('refund.confirm_message', { amount: formatRM(payload.amount), order: refundState.order.order_no }), { okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/refunds/create', payload).then(function (res) {
            if (!res.ok) {
                showFormErrors(refundForm, res.errors);
                showToast(res.error, 'danger');
                return;
            }
            hideModal('refund-modal');
            showToast(T('refund.created', { no: res.data.refund_no }), 'success');
            loadRefunds();
        });
    });
});

document.getElementById('refund-lookup').addEventListener('submit', function (event) {
    event.preventDefault();
    var q = document.getElementById('lookup-q').value.trim();
    if (q) {
        findOrder({ q: q });
    }
});

document.getElementById('btn-new-refund').addEventListener('click', function () {
    openRefundModal(null);
});

bindListFilters('refund-filters', refundList, loadRefunds);

var refundOrderParam = queryParam('order_id');
onCooperativeReady(function () {
    refundList.page = 1;
    loadRefunds();
    // Dari butiran pesanan: buka borang terus (sekali sahaja)
    if (refundOrderParam) {
        openRefundModal(Number(refundOrderParam));
        refundOrderParam = null;
    }
});
