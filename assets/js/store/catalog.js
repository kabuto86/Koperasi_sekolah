// Katalog ala carte

var catalogState = { q: '', category_id: queryParam('category') || '', grade: queryParam('grade') || '', page: 1, per_page: 24 };

function productCard(product) {
    var col = createEl('div', 'col-6 col-md-4 col-lg-3');
    var link = createEl('a', 'card h-100 border-0 shadow-sm text-decoration-none text-body store-product-card');
    link.href = storeUrl('product', { id: product.id });
    link.appendChild(productImage(product.image_url, 'card-img-top'));
    var body = createEl('div', 'card-body p-2');
    body.appendChild(createEl('div', 'small fw-semibold store-product-name', product.name));
    body.appendChild(priceEl(product.price, product.regular_price, product.price_from, 'fw-bold mt-1'));
    var badges = createEl('div', 'd-flex flex-wrap gap-1 mt-1');
    if (product.on_sale) {
        badges.appendChild(createEl('span', 'badge text-bg-danger', T('store.clearance.badge')));
    }
    var stock = stockBadge(product.available);
    if (stock) {
        badges.appendChild(stock);
    }
    body.appendChild(badges);
    link.appendChild(body);
    col.appendChild(link);
    return col;
}

function loadCatalog() {
    var grid = document.getElementById('catalog-grid');
    loadingState(grid);
    storeGet('store/catalog', catalogState).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var select = document.getElementById('catalog-category');
        if (select.options.length === 1) {
            fillSelect(select, res.data.categories, 'id', function (row) { return row.name; }, true);
            select.value = catalogState.category_id;
        }
        document.getElementById('catalog-count').textContent = T('store.catalog.count', { n: res.data.total });
        if (res.data.items.length === 0) {
            emptyState(grid, 'bi-search', T('store.catalog.none'));
        } else {
            grid.replaceChildren();
            res.data.items.forEach(function (product) {
                grid.appendChild(productCard(product));
            });
        }
        renderPagination(document.getElementById('catalog-pagination'), res.data, function (page) {
            catalogState.page = page;
            loadCatalog();
            window.scrollTo(0, 0);
        });
    });
}

// Pintasan "darjah anak saya" (anak di sekolah ini)
function renderChildChips() {
    if (!Store.customer) {
        return;
    }
    storeGet('store/children/list').then(function (res) {
        var here = res.ok ? res.data.filter(function (child) { return child.school_slug === Store.slug; }) : [];
        if (here.length === 0) {
            return;
        }
        var box = document.getElementById('catalog-children');
        box.replaceChildren(createEl('span', 'small text-body-secondary align-self-center', T('store.catalog.for_child')));
        here.forEach(function (child) {
            box.appendChild(createButton(child.full_name.split(' ')[0] + ' · ' + gradeLabel(child.grade_level_code), 'btn-outline-brand rounded-pill', 'bi-person', function () {
                document.getElementById('catalog-grade').value = child.grade_level_code;
                document.getElementById('catalog-grade').dispatchEvent(new Event('change', { bubbles: true }));
            }));
        });
        box.classList.remove('d-none');
    });
}

onStoreReady(function (store) {
    var gradeSelect = document.getElementById('catalog-grade');
    fillSelect(gradeSelect, store.school.grades.map(function (code) { return { code: code }; }), 'code', function (row) { return gradeLabel(row.code); }, true);
    gradeSelect.value = catalogState.grade;
    bindListFilters('catalog-filters', catalogState, loadCatalog);
    renderChildChips();
    loadCatalog();
});
