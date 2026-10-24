// Papan pemuka owner

function renderDashboard(data) {
    var totalVendors = Object.keys(data.vendors).reduce(function (sum, key) {
        return sum + data.vendors[key];
    }, 0);

    document.getElementById('stat-mrr').textContent = formatRM(data.mrr);
    document.getElementById('stat-mrr-sub').textContent = T('dashboard.mrr_change', { percent: data.mrr_change_percent });
    document.getElementById('stat-vendors').textContent = data.vendors.active;
    document.getElementById('stat-vendors-sub').textContent = T('dashboard.vendors_sub', { trial: data.vendors.trial, total: totalVendors });
    document.getElementById('stat-coops').textContent = data.cooperatives_active;
    document.getElementById('stat-coops-sub').textContent = T('dashboard.coops_sub', { total: data.cooperatives_total });
    document.getElementById('stat-orders').textContent = data.orders_this_month.toLocaleString('en-MY');
    document.getElementById('stat-orders-sub').textContent = formatRM(data.orders_value_this_month);

    renderBarChart('mrr-chart', data.mrr_history.map(function (row) {
        return { label: formatMonth(row.month), value: row.amount };
    }), formatRM);

    renderVendorStatusList(data.vendors);

    renderTable('expiring-body', data.expiring, [
        { render: function (row) {
            return createStack(createLink(row.vendor_name, pageUrl('owner/vendor.php?id=' + row.vendor_id), 'fw-semibold'), row.vendor_code);
        } },
        { render: function (row) { return row.plan_name + ' · ' + T('billing.cycle.' + row.billing_cycle); } },
        { render: function (row) { return statusBadge(row.status, 'vendor.status'); } },
        { className: 'text-end', render: function (row) { return formatDate(row.current_period_end); } }
    ]);
}

function renderVendorStatusList(counts) {
    var list = document.getElementById('vendor-status-list');
    list.replaceChildren();
    ['active', 'trial', 'past_due', 'suspended', 'cancelled'].forEach(function (status) {
        var item = createEl('li', 'list-group-item d-flex justify-content-between align-items-center');
        item.appendChild(statusBadge(status, 'vendor.status'));
        item.appendChild(createEl('span', 'fw-semibold', counts[status]));
        list.appendChild(item);
    });
}

Api.get('owner/dashboard/summary').then(function (res) {
    if (!res.ok) {
        showToast(res.error, 'danger');
        return;
    }
    renderDashboard(res.data);
});
