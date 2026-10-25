// Senarai produk

var productList = { page: 1, per_page: 20, q: '', category_id: queryParam('category_id') || '', grade: '', status: '', low_only: false };

function productThumb(row) {
    var box = createEl('div', 'app-logo-box');
    showImagePreview(box, row.image_url, 'bi-box-seam');
    return box;
}

function gradeSummary(codes) {
    if (codes.length === 0) {
        return createEl('span', 'small text-body-secondary', T('product.all_grades_only'));
    }
    var wrap = createEl('div', 'd-flex flex-wrap gap-1');
    codes.forEach(function (code) {
        wrap.appendChild(createEl('span', 'badge text-bg-light border', code));
    });
    return wrap;
}

function loadProducts() {
    tableLoading('products-body', 7);
    Api.get('vendor/products/list', withCoop(productList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('products-body', res.data.items, [
            { render: function (row) {
                var wrap = createEl('div', 'd-flex align-items-center gap-2');
                wrap.appendChild(productThumb(row));
                var sub = row.sku + (row.has_variants ? ' · ' + T('product.variant_count', { n: row.variant_count }) : '');
                wrap.appendChild(createStack(createLink(row.name, pageUrl('vendor/product-form.php?id=' + row.id), 'fw-semibold'), sub));
                return wrap;
            } },
            { key: 'category_name' },
            { render: function (row) { return gradeSummary(row.grade_levels); } },
            { className: 'text-end text-nowrap', render: function (row) {
                return (row.has_variants ? T('product.from_price') + ' ' : '') + formatRM(row.price);
            } },
            { className: 'text-end', render: function (row) {
                var text = createEl('span', row.is_low ? 'fw-semibold text-danger' : '', row.stock_available + ' ' + row.unit);
                if (row.is_low) {
                    text.title = T('inventory.low');
                }
                return text;
            } },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end text-nowrap', render: productActions }
        ]);
        renderPagination('products-pagination', res.data, function (page) {
            productList.page = page;
            loadProducts();
        });
    });
}

function productActions(row) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    wrap.appendChild(createIconLink(pageUrl('vendor/product-form.php?id=' + row.id), 'bi-pencil', T('btn.edit')));
    wrap.appendChild(createButton('', row.is_active ? 'btn-outline-danger' : 'btn-outline-success', row.is_active ? 'bi-eye-slash' : 'bi-eye', function () {
        Api.post('vendor/products/set-active', withCoop({ id: row.id, is_active: !row.is_active })).then(function (res) {
            showToast(res.ok ? T('common.saved') : res.error, res.ok ? 'success' : 'danger');
            loadProducts();
        });
    }, T(row.is_active ? 'product.deactivate' : 'product.activate')));
    return wrap;
}

function loadFilterOptions() {
    Api.get('vendor/categories/list', withCoop()).then(function (res) {
        if (res.ok) {
            fillSelect('product-category', res.data, 'id', function (category) { return category.name; }, true);
            document.getElementById('product-category').value = productList.category_id;
        }
    });
    Api.get('vendor/grades/list', withCoop()).then(function (res) {
        if (!res.ok) {
            return;
        }
        var select = document.getElementById('product-grade');
        while (select.options.length > 2) {
            select.remove(2);
        }
        res.data.forEach(function (code) {
            var option = createEl('option', '', T('grade.' + code));
            option.value = code;
            select.appendChild(option);
        });
    });
}

bindListFilters('product-filters', productList, loadProducts);

onCooperativeReady(function () {
    productList.page = 1;
    loadFilterOptions();
    loadProducts();
});
