// Langganan & invois

var subscriptionList = { page: 1, per_page: 20, q: '', status: '', billing_cycle: '' };
var invoiceList = { page: 1, per_page: 20, q: '', status: '' };

function vendorCell(row) {
    return createStack(createLink(row.vendor_name, pageUrl('owner/vendor.php?id=' + row.vendor_id), 'fw-semibold'), row.vendor_code);
}

function loadSubscriptions() {
    tableLoading('subscriptions-body', 5);
    Api.get('owner/subscriptions/list', subscriptionList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('subscriptions-body', res.data.items, [
            { render: vendorCell },
            { render: function (row) { return createStack(row.plan_name, T('billing.cycle.' + row.billing_cycle)); } },
            { key: 'cooperative_count', className: 'text-center' },
            { render: function (row) { return formatDate(row.current_period_start) + ' – ' + formatDate(row.current_period_end); } },
            { render: function (row) { return statusBadge(row.status, 'vendor.status'); } }
        ]);
        renderPagination('subscriptions-pagination', res.data, function (page) {
            subscriptionList.page = page;
            loadSubscriptions();
        });
    });
}

function loadInvoices() {
    tableLoading('invoices-body', 6);
    Api.get('owner/invoices/list', invoiceList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('invoices-body', res.data.items, [
            { render: function (row) { return createLink(row.invoice_no, pageUrl('owner/invoice.php?id=' + row.id), 'fw-semibold'); } },
            { render: vendorCell },
            { render: function (row) { return formatDate(row.period_start) + ' – ' + formatDate(row.period_end); } },
            { render: function (row) { return row.due_at ? formatDate(row.due_at) : '—'; } },
            { className: 'text-end', render: function (row) { return formatRM(row.total); } },
            { render: function (row) { return statusBadge(row.status, 'invoice.status'); } }
        ]);
        renderPagination('invoices-pagination', res.data, function (page) {
            invoiceList.page = page;
            loadInvoices();
        });
    });
}

// Buka tab invois terus melalui #invoices (cth. dari halaman butiran invois)
if (window.location.hash === '#invoices') {
    bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-invoices')).show();
}

bindListFilters('subscription-filters', subscriptionList, loadSubscriptions);
bindListFilters('invoice-filters', invoiceList, loadInvoices);
loadSubscriptions();
loadInvoices();
