// Kategori produk

var categoryForm = document.getElementById('category-form');

function loadCategories() {
    tableLoading('categories-body', 5);
    Api.get('vendor/categories/list', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('categories-body', res.data, [
            { key: 'name', className: 'fw-semibold' },
            { className: 'text-center', render: function (row) {
                return createLink(String(row.product_count), pageUrl('vendor/products.php?category_id=' + row.id));
            } },
            { key: 'sort_order', className: 'text-center' },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end text-nowrap', render: categoryActions }
        ]);
    });
}

function categoryActions(row) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
        openCategoryForm(row);
    }, T('btn.edit')));
    wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
        confirmDialog(T('category.delete_confirm', { name: row.name }), { okText: T('btn.delete'), okClass: 'btn-danger' }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }
            Api.post('vendor/categories/delete', withCoop({ id: row.id })).then(function (res) {
                showToast(res.ok ? T('common.deleted') : res.error, res.ok ? 'success' : 'danger');
                loadCategories();
            });
        });
    }, T('btn.delete')));
    return wrap;
}

function openCategoryForm(category) {
    document.getElementById('category-title').textContent = category ? T('category.edit') : T('category.add');
    fillForm(categoryForm, category || { is_active: true, sort_order: 0 });
    showModal('category-modal');
}

document.getElementById('btn-add-category').addEventListener('click', function () {
    openCategoryForm(null);
});

bindForm(categoryForm, 'vendor/categories/save', function () {
    hideModal('category-modal');
    showToast(T('common.saved'), 'success');
    loadCategories();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return withCoop(payload);
});

onCooperativeReady(loadCategories);
