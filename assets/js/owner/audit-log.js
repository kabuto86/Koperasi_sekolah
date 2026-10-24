// Log audit (baca sahaja)

var auditList = { page: 1, per_page: 20, q: '', actor_type: '', date_from: '', date_to: '' };

function loadAuditLogs() {
    tableLoading('audit-body', 6);
    Api.get('owner/audit-logs/list', auditList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('audit-body', res.data.items, [
            { className: 'text-nowrap', render: function (row) { return formatDate(row.created_at, true); } },
            { render: function (row) {
                var sub = T('audit.actor.' + row.actor_type) + (row.vendor_code ? ' · ' + row.vendor_code : '');
                return createStack(createEl('span', '', row.actor_name), sub);
            } },
            { render: function (row) { return createEl('code', 'small', row.action); } },
            { render: function (row) { return row.entity + ' #' + row.entity_id; } },
            { key: 'detail', className: 'small' },
            { key: 'ip_address', className: 'small text-body-secondary font-monospace' }
        ]);
        renderPagination('audit-pagination', res.data, function (page) {
            auditList.page = page;
            loadAuditLogs();
        });
    });
}

bindListFilters('audit-filters', auditList, loadAuditLogs);
loadAuditLogs();
