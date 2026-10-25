// Sejarah pergerakan stok

var movementList = {
    page: 1, per_page: 25, q: '', type: '', date_from: '', date_to: '',
    product_id: queryParam('product_id') || '', variant_id: queryParam('variant_id') || ''
};

var MOVEMENT_COLORS = {
    sale: 'text-bg-primary', pos_sale: 'text-bg-primary', reserve: 'text-bg-warning', release: 'text-bg-light border',
    collect: 'text-bg-dark', refund: 'text-bg-info', restock: 'text-bg-success', adjust: 'text-bg-secondary',
    stocktake: 'text-bg-secondary', po_receive: 'text-bg-success'
};

function movementReference(row) {
    if (row.ref_type === 'purchase_order') {
        return createLink(T('movement.ref.purchase_order', { id: row.ref_id }), pageUrl('vendor/purchase-order.php?id=' + row.ref_id));
    }
    if (row.ref_type) {
        return T('movement.ref.' + row.ref_type, { id: row.ref_id });
    }
    return row.note || '—';
}

function loadMovements() {
    tableLoading('movements-body', 7);
    Api.get('vendor/inventory/movements', withCoop(movementList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('movements-body', res.data.items, [
            { className: 'text-nowrap small', render: function (row) { return formatDate(row.created_at, true); } },
            { render: function (row) {
                return createStack(row.variant_label ? row.product_name + ' — ' + row.variant_label : row.product_name, row.sku);
            } },
            { render: function (row) {
                return createEl('span', 'badge ' + (MOVEMENT_COLORS[row.type] || 'text-bg-secondary'), T('movement.type.' + row.type));
            } },
            { className: 'text-end fw-semibold', render: function (row) {
                var text = row.qty_change > 0 ? '+' + row.qty_change : String(row.qty_change);
                return createEl('span', row.qty_change > 0 ? 'text-success' : (row.qty_change < 0 ? 'text-danger' : 'text-body-secondary'), text);
            } },
            { key: 'balance_after', className: 'text-end' },
            { className: 'small', render: movementReference },
            { key: 'user_name', className: 'small text-body-secondary' }
        ]);
        renderPagination('movements-pagination', res.data, function (page) {
            movementList.page = page;
            loadMovements();
        });
    });
}

document.getElementById('product-filter-note').classList.toggle('d-none', !movementList.product_id);
bindListFilters('movement-filters', movementList, loadMovements);

onCooperativeReady(function () {
    movementList.page = 1;
    loadMovements();
});
