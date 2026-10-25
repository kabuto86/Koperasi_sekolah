// Rekod bayaran (online & tunai) koperasi semasa

var recordList = { page: 1, per_page: 20, q: '', method: '', status: '', date_from: '', date_to: '' };
var GATEWAY_NAMES = { toyyibpay: 'ToyyibPay', billplz: 'Billplz', senangpay: 'SenangPay', ipay88: 'iPay88', stripe: 'Stripe' };

function loadRecords() {
    tableLoading('records-body', 6);
    Api.get('vendor/payments/list', withCoop(recordList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderSummary(res.data.summary);
        renderTable('records-body', res.data.items, [
            { className: 'small text-nowrap', render: function (row) { return formatDate(row.paid_at || row.created_at, true); } },
            { render: function (row) {
                return createStack(createLink(row.order_no, pageUrl('vendor/order.php?id=' + row.order_id), 'fw-semibold'), row.short_code + ' · ' + row.customer_name);
            } },
            { render: function (row) {
                return createStack(T('payment.method.' + row.method), row.method === 'online' ? GATEWAY_NAMES[row.gateway_code] || row.gateway_code : T('payment.received_by', { name: row.received_by }));
            } },
            { className: 'small font-monospace', render: function (row) { return row.gateway_ref || '—'; } },
            { className: 'text-end', render: function (row) {
                return createStack(formatRM(row.amount), row.refunded_amount ? T('payment.refunded_line', { amount: formatRM(row.refunded_amount) }) : '');
            } },
            { render: function (row) { return statusBadge(row.status, 'payment.record'); } }
        ]);
        renderPagination('records-pagination', res.data, function (page) {
            recordList.page = page;
            loadRecords();
        });
    });
}

function renderSummary(summary) {
    document.getElementById('sum-online').textContent = formatRM(summary.online_total);
    document.getElementById('sum-cash').textContent = formatRM(summary.cash_total);
    document.getElementById('sum-refunded').textContent = formatRM(summary.refunded_total);
    document.getElementById('sum-pending').textContent = String(summary.pending_count);
}

bindListFilters('record-filters', recordList, loadRecords);

onCooperativeReady(function () {
    recordList.page = 1;
    loadRecords();
});
