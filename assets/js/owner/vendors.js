// Senarai vendor

var vendorList = { page: 1, per_page: 20, q: '', status: '', plan_id: '' };

function loadVendors() {
    tableLoading('vendors-body', 7);
    Api.get('owner/vendors/list', vendorList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('vendors-body', res.data.items, vendorColumns());
        renderPagination('vendors-pagination', res.data, function (page) {
            vendorList.page = page;
            loadVendors();
        });
    });
}

function vendorColumns() {
    return [
        { render: function (row) {
            return createStack(createLink(row.company_name, vendorUrl(row.id), 'fw-semibold'), row.code);
        } },
        { render: function (row) { return createStack(row.contact_name, row.email + ' · ' + row.phone); } },
        { render: function (row) { return createStack(row.plan_name, T('billing.cycle.' + row.billing_cycle)); } },
        { key: 'cooperative_count', className: 'text-center' },
        { render: function (row) { return statusBadge(row.status, 'vendor.status'); } },
        { render: function (row) {
            return row.status === 'trial' ? T('vendor.trial_until', { date: formatDate(row.trial_ends_at) }) : formatDate(row.current_period_end);
        } },
        { className: 'text-end', render: function (row) {
            return createLink(T('btn.view'), vendorUrl(row.id), 'btn btn-sm btn-outline-secondary');
        } }
    ];
}

function vendorUrl(id) {
    return pageUrl('owner/vendor.php?id=' + id);
}

function loadPlanFilter() {
    Api.get('owner/plans/options').then(function (res) {
        if (res.ok) {
            fillSelect('filter-plan', res.data, 'id', function (plan) { return plan.name; }, true);
        }
    });
}

bindListFilters('vendor-filters', vendorList, loadVendors);
loadPlanFilter();
loadVendors();
