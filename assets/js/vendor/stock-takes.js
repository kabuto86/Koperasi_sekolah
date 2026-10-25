// Senarai stock take

function takeStatusBadge(status) {
    return createEl('span', 'badge ' + (status === 'open' ? 'text-bg-warning' : 'text-bg-success'), T('stocktake.status.' + status));
}

function loadTakes() {
    tableLoading('takes-body', 6);
    Api.get('vendor/stock-takes/list', withCoop({ per_page: 50 })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('takes-body', res.data.items, [
            { render: function (row) { return createStack(formatDate(row.started_at, true), row.started_by); } },
            { render: function (row) { return takeStatusBadge(row.status); } },
            { className: 'text-center', render: function (row) { return row.counted_count + ' / ' + row.item_count; } },
            { className: 'text-end', render: function (row) {
                return createEl('span', row.variance_total < 0 ? 'text-danger' : '', (row.variance_total > 0 ? '+' : '') + row.variance_total);
            } },
            { render: function (row) { return row.completed_at ? formatDate(row.completed_at, true) : '—'; } },
            { className: 'text-end', render: function (row) {
                return createLink(T(row.status === 'open' ? 'stocktake.continue' : 'btn.view'), pageUrl('vendor/stock-take.php?id=' + row.id),
                    'btn btn-sm ' + (row.status === 'open' ? 'btn-brand' : 'btn-outline-secondary'));
            } }
        ]);
    });
}

document.getElementById('btn-start-take').addEventListener('click', function () {
    confirmDialog(T('stocktake.start_confirm'), { okText: T('stocktake.start') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/stock-takes/start', withCoop()).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            window.location.href = pageUrl('vendor/stock-take.php?id=' + res.data.id);
        });
    });
});

onCooperativeReady(loadTakes);
