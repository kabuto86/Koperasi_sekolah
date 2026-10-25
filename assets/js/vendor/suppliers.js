// Pembekal (peringkat vendor, bukan koperasi)

var supplierForm = document.getElementById('supplier-form');
var supplierList = { page: 1, per_page: 20, q: '', status: '' };

function loadSuppliers() {
    tableLoading('suppliers-body', 5);
    Api.get('vendor/suppliers/list', supplierList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('suppliers-body', res.data.items, [
            { render: function (row) { return createStack(row.name, row.address); } },
            { render: function (row) { return createStack(row.contact_name || '—', [row.phone, row.email].filter(Boolean).join(' · ')); } },
            { key: 'open_po_count', className: 'text-center' },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end text-nowrap', render: function (row) {
                var wrap = createEl('div', 'd-inline-flex gap-1');
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                    openSupplierForm(row);
                }, T('btn.edit')));
                wrap.appendChild(createIconLink(pageUrl('vendor/purchase-order.php?supplier_id=' + row.id), 'bi-file-earmark-plus', T('po.create')));
                return wrap;
            } }
        ]);
        renderPagination('suppliers-pagination', res.data, function (page) {
            supplierList.page = page;
            loadSuppliers();
        });
    });
}

function openSupplierForm(supplier) {
    document.getElementById('supplier-title').textContent = supplier ? T('supplier.edit') : T('supplier.add');
    fillForm(supplierForm, supplier || { is_active: true });
    showModal('supplier-modal');
}

document.getElementById('btn-add-supplier').addEventListener('click', function () {
    openSupplierForm(null);
});

bindForm(supplierForm, 'vendor/suppliers/save', function () {
    hideModal('supplier-modal');
    showToast(T('common.saved'), 'success');
    loadSuppliers();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return payload;
});

bindListFilters('supplier-filters', supplierList, loadSuppliers);
loadSuppliers();
