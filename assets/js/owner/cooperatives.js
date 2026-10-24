// Semua koperasi (baca sahaja)

var coopList = { page: 1, per_page: 20, q: '', vendor_id: '', school_type: '', status: '' };

function loadCooperatives() {
    tableLoading('coops-body', 6);
    Api.get('owner/cooperatives/list', coopList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('coops-body', res.data.items, [
            { render: function (row) { return createStack(row.school_name, row.school_type + ' · ' + row.school_code + ' · /' + row.slug); } },
            { render: function (row) {
                return createStack(createLink(row.vendor_name, pageUrl('owner/vendor.php?id=' + row.vendor_id)), row.vendor_code);
            } },
            { render: function (row) { return createStack(row.district, row.state); } },
            { key: 'template_name' },
            { render: function (row) { return moduleList(row.modules); } },
            { render: function (row) { return statusBadge(row.status, 'coop.status'); } }
        ]);
        renderPagination('coops-pagination', res.data, function (page) {
            coopList.page = page;
            loadCooperatives();
        });
    });
}

function moduleList(codes) {
    var wrap = createEl('div', 'd-flex flex-wrap gap-1');
    if (codes.length === 0) {
        wrap.appendChild(createEl('span', 'text-body-secondary', '—'));
    }
    codes.forEach(function (code) {
        wrap.appendChild(createEl('span', 'badge text-bg-light border', T('module.' + code)));
    });
    return wrap;
}

Api.get('owner/vendors/options').then(function (res) {
    if (res.ok) {
        fillSelect('filter-vendor', res.data, 'id', function (vendor) { return vendor.code + ' — ' + vendor.company_name; }, true);
    }
});

bindListFilters('coop-filters', coopList, loadCooperatives);
loadCooperatives();
