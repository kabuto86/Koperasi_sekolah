// Senarai pesanan belian

var poList = { page: 1, per_page: 20, q: '', supplier_id: '', status: '' };

function loadPurchaseOrders() {
    tableLoading('po-body', 6);
    Api.get('vendor/purchase-orders/list', withCoop(poList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('po-body', res.data.items, [
            { render: function (row) {
                return createStack(createLink(row.po_no, pageUrl('vendor/purchase-order.php?id=' + row.id), 'fw-semibold'), row.note);
            } },
            { key: 'supplier_name' },
            { key: 'item_count', className: 'text-center' },
            { className: 'text-end', render: function (row) { return formatRM(row.total); } },
            { render: function (row) { return row.ordered_at ? formatDate(row.ordered_at) : '—'; } },
            { render: function (row) { return statusBadge(row.status, 'po.status'); } }
        ]);
        renderPagination('po-pagination', res.data, function (page) {
            poList.page = page;
            loadPurchaseOrders();
        });
    });
}

Api.get('vendor/suppliers/list', { per_page: 100 }).then(function (res) {
    if (res.ok) {
        fillSelect('po-supplier', res.data.items, 'id', function (supplier) { return supplier.name; }, true);
    }
});

bindListFilters('po-filters', poList, loadPurchaseOrders);
onCooperativeReady(function () {
    poList.page = 1;
    loadPurchaseOrders();
});
