// Modul jualan murah — senarai jualan koperasi semasa

var saleList = { page: 1, per_page: 20, q: '', state: '' };

function loadSales() {
    tableLoading('sales-body', 5);
    Api.get('vendor/clearance/list', withCoop(saleList)).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        renderTable('sales-body', res.data.items, [
            { render: function (row) {
                return createLink(row.name, pageUrl('vendor/addons/clearance-sale.php?id=' + row.id), 'fw-semibold text-decoration-none');
            } },
            { className: 'small text-nowrap', render: function (row) { return dateRangeText(row.starts_on, row.ends_on); } },
            { className: 'text-center', key: 'item_count' },
            { render: function (row) { return statusBadge(row.state, 'clearance.state'); } },
            { className: 'text-end text-nowrap', render: function (row) {
                var wrap = createEl('div', 'd-inline-flex gap-1');
                wrap.appendChild(createIconLinkText(pageUrl('vendor/addons/clearance-sale.php?id=' + row.id), 'bi-list-ul', T('clearance.manage_items'), 'btn-outline-secondary'));
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                    openSaleForm(row);
                }, T('btn.edit')));
                wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
                    deleteSale(row);
                }, T('btn.delete')));
                return wrap;
            } }
        ]);
        renderPagination('sales-pagination', res.data, function (page) {
            saleList.page = page;
            loadSales();
        });
    });
}

function deleteSale(sale) {
    confirmDialog(T('clearance.delete_confirm', { name: sale.name, n: sale.item_count }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/clearance/delete', withCoop({ id: sale.id })).then(function (res) {
            if (moduleResponse(res)) {
                showToast(T('common.deleted'), 'success');
                loadSales();
            }
        });
    });
}

document.getElementById('btn-add-sale').addEventListener('click', function () {
    openSaleForm(null);
});

// Jualan baharu → terus ke halaman item
bindSaleForm(function (sale) {
    if (!saleForm.elements.id.value) {
        window.location.href = pageUrl('vendor/addons/clearance-sale.php?id=' + sale.id);
        return;
    }
    loadSales();
});

bindListFilters('sale-filters', saleList, loadSales);

onModuleReady(function () {
    saleList.page = 1;
    loadSales();
});
