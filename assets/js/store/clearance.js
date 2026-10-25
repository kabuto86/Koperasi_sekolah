// Jualan murah (modul add-on 'clearance'): harga jualan dikira di pelayan bila masuk troli

function clearanceCard(item) {
    var col = createEl('div', 'col-6 col-md-4 col-lg-3');
    var link = createEl('a', 'card h-100 border-0 shadow-sm text-decoration-none text-body store-product-card');
    link.href = storeUrl('product', { id: item.product_id });
    link.appendChild(productImage(item.image_url, 'card-img-top'));
    var body = createEl('div', 'card-body p-2');
    body.appendChild(createEl('div', 'small fw-semibold store-product-name', item.name + (item.variant_label ? ' — ' + item.variant_label : '')));
    body.appendChild(priceEl(item.sale_price, item.regular_price, false, 'fw-bold mt-1'));
    var badges = createEl('div', 'd-flex flex-wrap gap-1 mt-1');
    var saving = Math.round((1 - item.sale_price / item.regular_price) * 100);
    if (saving > 0) {
        badges.appendChild(createEl('span', 'badge text-bg-danger', T('store.clearance.saving', { n: saving })));
    }
    if (item.qty_limit) {
        badges.appendChild(createEl('span', 'badge text-bg-light border', T('store.clearance.limit', { n: item.qty_limit })));
    }
    var stock = stockBadge(item.available);
    if (stock) {
        badges.appendChild(stock);
    }
    body.appendChild(badges);
    link.appendChild(body);
    col.appendChild(link);
    return col;
}

function loadClearance() {
    var box = document.getElementById('clearance-list');
    loadingState(box);
    storeGet('store/clearance/list').then(function (res) {
        if (!res.ok) {
            emptyState(box, 'bi-tags', res.error, T('store.nav.home'), storeUrl('home'));
            return;
        }
        if (res.data.length === 0) {
            emptyState(box, 'bi-tags', T('store.clearance.none'));
            return;
        }
        box.replaceChildren();
        res.data.forEach(function (sale) {
            var section = createEl('section', 'mb-4');
            var head = createEl('div', 'd-flex flex-wrap justify-content-between align-items-baseline mb-2');
            head.appendChild(createEl('h2', 'h6 mb-0', sale.name));
            head.appendChild(createEl('span', 'small text-danger', T('store.clearance.ends', { date: formatDate(sale.ends_at) })));
            section.appendChild(head);
            var grid = createEl('div', 'row g-2 g-md-3');
            sale.items.forEach(function (item) {
                grid.appendChild(clearanceCard(item));
            });
            section.appendChild(grid);
            box.appendChild(section);
        });
    });
}

onStoreReady(loadClearance);
