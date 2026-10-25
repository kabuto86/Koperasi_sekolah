// Rekod pembelian: tapis anak & tahun

var ordersState = { child_id: '', year: '', page: 1, per_page: 10 };
var ordersFiltersFilled = false;

function orderCard(order) {
    var link = createEl('a', 'card border-0 shadow-sm text-decoration-none text-body store-order-card');
    link.href = storeUrl('order-detail', { id: order.id });
    var body = createEl('div', 'card-body');
    var top = createEl('div', 'd-flex justify-content-between align-items-start gap-2');
    top.appendChild(createStack(order.order_no, formatDate(order.created_at, true)));
    top.appendChild(statusBadge(order.status));
    body.appendChild(top);
    body.appendChild(createEl('div', 'small mt-2', order.children.map(function (child) {
        return child.child_name + ' (' + childClassLine(child) + ')';
    }).join(', ')));
    var bottom = createEl('div', 'd-flex justify-content-between align-items-center mt-2');
    bottom.appendChild(createEl('span', 'small text-body-secondary', T('order.short_code_line', { code: order.short_code }) + ' · '
        + T('order.item_count', { n: order.item_count }) + ' · ' + T('payment.method.' + order.payment_method)));
    bottom.appendChild(createEl('span', 'fw-bold', formatRM(order.total)));
    body.appendChild(bottom);
    link.appendChild(body);
    return link;
}

function fillOrderFilters(data) {
    if (ordersFiltersFilled) {
        return;
    }
    ordersFiltersFilled = true;
    fillSelect('orders-child', data.children, 'id', function (child) { return child.full_name; }, true);
    fillSelect('orders-year', data.years.map(function (year) { return { year: year }; }), 'year', function (row) { return String(row.year); }, true);
}

function loadOrders() {
    var list = document.getElementById('orders-list');
    loadingState(list);
    storeGet('store/orders/list', ordersState).then(function (res) {
        if (!res.ok) {
            return;
        }
        fillOrderFilters(res.data);
        if (res.data.items.length === 0) {
            emptyState(list, 'bi-receipt', T('store.orders.none'), T('store.nav.kit'), storeUrl('kit'));
        } else {
            list.replaceChildren();
            res.data.items.forEach(function (order) {
                list.appendChild(orderCard(order));
            });
        }
        renderPagination(document.getElementById('orders-pagination'), res.data, function (page) {
            ordersState.page = page;
            loadOrders();
        });
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    bindListFilters('orders-filters', ordersState, loadOrders);
    loadOrders();
});
