// Senarai pesanan online

var orderList = { page: 1, per_page: 20, q: queryParam('q') || '', status: queryParam('status') || '', grade: '', class_name: '', payment_method: '', date_from: '', date_to: '', pickup_slot_id: queryParam('pickup_slot_id') || '' };
var selectedOrders = [];
var STATUS_PILLS = ['', 'pending_payment', 'unpaid_cash', 'paid', 'preparing', 'ready', 'partially_collected', 'collected'];

function loadOrders() {
    tableLoading('orders-body', 7);
    selectedOrders = [];
    updateBulkBar();
    updateHeaderLinks();
    Api.get('vendor/orders/list', withCoop(orderList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderStatusPills(res.data.status_counts);
        renderTable('orders-body', res.data.items, [
            { render: selectBox },
            { render: function (row) {
                var stack = createStack(createLink(row.order_no, pageUrl('vendor/order.php?id=' + row.id), 'fw-semibold'),
                    T('order.short_code_line', { code: row.short_code }) + ' · ' + formatDate(row.created_at, true));
                if (row.pickup_slot) {
                    stack.appendChild(createEl('div', 'small text-body-secondary', T('order.slot_line', { slot: slotText(row.pickup_slot) })));
                }
                return stack;
            } },
            { render: function (row) {
                var wrap = createEl('div', 'small');
                row.children.forEach(function (child) {
                    wrap.appendChild(createEl('div', '', childLine(child)));
                });
                return wrap;
            } },
            { render: function (row) { return createStack(row.customer_name, row.customer_phone); } },
            { className: 'text-end text-nowrap', render: function (row) {
                return createStack(createEl('span', 'fw-semibold', formatRM(row.total)), T('order.item_count', { n: row.item_count }));
            } },
            { render: paymentBadge },
            { render: function (row) { return statusBadge(row.status); } }
        ]);
        renderPagination('orders-pagination', res.data, function (page) {
            orderList.page = page;
            loadOrders();
        });
    });
}

function renderStatusPills(counts) {
    var box = document.getElementById('status-pills');
    box.replaceChildren();
    STATUS_PILLS.forEach(function (status) {
        var total = status ? (counts[status] || 0) : Object.keys(counts).reduce(function (sum, key) { return sum + counts[key]; }, 0);
        var active = orderList.status === status;
        var button = createEl('button', 'btn btn-sm text-nowrap ' + (active ? 'btn-brand' : 'btn-outline-secondary'));
        button.type = 'button';
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.appendChild(document.createTextNode((status ? T('order.status.' + status) : T('common.all')) + ' '));
        button.appendChild(createEl('span', 'badge ' + (active ? 'text-bg-light' : 'text-bg-secondary'), total));
        button.addEventListener('click', function () {
            orderList.status = status;
            document.getElementById('ord-status').value = status;
            orderList.page = 1;
            loadOrders();
        });
        box.appendChild(button);
    });
}

function selectBox(row) {
    var box = createEl('input', 'form-check-input js-select');
    box.type = 'checkbox';
    box.value = row.id;
    box.setAttribute('aria-label', T('order.select', { no: row.order_no }));
    box.addEventListener('change', function () {
        selectedOrders = Array.prototype.filter.call(document.querySelectorAll('.js-select'), function (input) {
            return input.checked;
        }).map(function (input) { return Number(input.value); });
        updateBulkBar();
    });
    return box;
}

function updateBulkBar() {
    document.getElementById('bulk-bar').classList.toggle('d-none', selectedOrders.length === 0);
    document.getElementById('bulk-count').textContent = T('order.selected_count', { n: selectedOrders.length });
    document.getElementById('select-all').checked = selectedOrders.length > 0 && selectedOrders.length === document.querySelectorAll('.js-select').length;
}

function updateHeaderLinks() {
    var query = filterQuery(orderList, ['grade', 'class_name', 'pickup_slot_id']);
    document.getElementById('btn-picking').href = pageUrl('vendor/picking-list.php' + query);
    document.getElementById('btn-labels').href = pageUrl('vendor/bag-labels.php' + query);
}

document.getElementById('select-all').addEventListener('change', function (event) {
    document.querySelectorAll('.js-select').forEach(function (input) {
        input.checked = event.target.checked;
    });
    selectedOrders = event.target.checked ? Array.prototype.map.call(document.querySelectorAll('.js-select'), function (input) {
        return Number(input.value);
    }) : [];
    updateBulkBar();
});

document.querySelectorAll('[data-bulk]').forEach(function (button) {
    button.addEventListener('click', function () {
        var status = button.getAttribute('data-bulk');
        Api.post('vendor/orders/bulk-status', withCoop({ ids: selectedOrders, status: status })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('order.bulk_result', { changed: res.data.changed, skipped: res.data.skipped }), res.data.skipped ? 'warning' : 'success');
            loadOrders();
        });
    });
});

document.getElementById('ord-status').value = orderList.status;
document.getElementById('ord-q').value = orderList.q;
bindListFilters('order-filters', orderList, loadOrders);

onCooperativeReady(function () {
    orderList.page = 1;
    Promise.all([bindGradeClassFilters('ord-grade', 'ord-class'), bindSlotFilter('ord-slot', orderList.pickup_slot_id)]).then(function (results) {
        orderList.grade = document.getElementById('ord-grade').value;
        orderList.class_name = document.getElementById('ord-class').value;
        orderList.pickup_slot_id = results[1];
        loadOrders();
    });
});
