// Butiran jualan murah: item & harga khas. Harga jualan disahkan semula di pelayan (mesti < harga biasa)

var saleState = { id: Number(queryParam('id')), sale: null, products: [], product: null, loadedCoopId: null };
var itemForm = document.getElementById('item-form');
var itemProduct = document.getElementById('item-product');
var itemVariant = document.getElementById('item-variant');

function renderSale(sale) {
    saleState.sale = sale;
    document.getElementById('sale-name').textContent = sale.name;
    document.getElementById('sale-period').textContent = dateRangeText(sale.starts_on, sale.ends_on);
    document.getElementById('sale-state').replaceChildren(statusBadge(sale.state, 'clearance.state'));
    renderTable('items-body', sale.items, [
        { render: function (row) {
            return createStack(row.product_name + (row.variant_label ? ' — ' + row.variant_label : (row.has_variants ? ' — ' + T('clearance.all_variants') : '')), row.sku);
        } },
        { className: 'text-end text-nowrap text-body-secondary text-decoration-line-through', render: function (row) { return formatRM(row.regular_price); } },
        { className: 'text-end text-nowrap', render: function (row) {
            var stack = createStack(formatRM(row.sale_price));
            stack.firstChild.classList.add('fw-semibold', 'text-danger');
            stack.appendChild(createEl('span', 'badge text-bg-danger', T('store.clearance.saving', { n: row.discount_percent })));
            return stack;
        } },
        { className: 'text-center', render: function (row) { return row.qty_limit ? String(row.qty_limit) : T('plan.unlimited'); } },
        { className: 'text-center', render: function (row) { return row.available === null ? '—' : String(row.available); } },
        { className: 'text-end text-nowrap', render: function (row) {
            var wrap = createEl('div', 'd-inline-flex gap-1');
            wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                openItemForm(row);
            }, T('btn.edit')));
            wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
                deleteItem(row);
            }, T('btn.delete')));
            return wrap;
        } }
    ]);
}

function loadSale() {
    tableLoading('items-body', 6);
    Api.get('vendor/clearance/get', withCoop({ id: saleState.id })).then(function (res) {
        if (!res.ok && !res.module_locked) {
            showToast(res.error, 'danger');
            window.location.href = pageUrl('vendor/addons/clearance.php');
            return;
        }
        if (moduleResponse(res)) {
            renderSale(res.data);
        }
    });
}

function loadProducts() {
    return Api.get('vendor/products/list', withCoop({ per_page: 100, status: 'active' })).then(function (res) {
        if (!res.ok) {
            return;
        }
        saleState.products = res.data.items.filter(function (product) { return product.is_active; });
        fillSelect(itemProduct, saleState.products, 'id', function (product) {
            return product.name + ' (' + product.sku + ')';
        }, true);
    });
}

// Harga biasa produk/varian dipilih → teks bantuan
function updateRegularHint() {
    var hint = document.getElementById('item-regular');
    var product = saleState.product;
    if (!product) {
        hint.textContent = '';
        return;
    }
    var variant = product.variants.filter(function (row) { return row.id === Number(itemVariant.value); })[0];
    var regular = product.price + (variant ? variant.price_delta : 0);
    hint.textContent = T('clearance.regular_hint', { amount: formatRM(regular) });
}

// Produk bervarian: pilih satu saiz atau "semua saiz" (harga biasa terendah)
function selectProduct(productId, variantId) {
    saleState.product = null;
    var box = document.getElementById('item-variant-box');
    box.classList.add('d-none');
    itemVariant.replaceChildren(createEl('option', '', T('clearance.all_variants')));
    itemVariant.firstChild.value = '';
    updateRegularHint();
    if (!productId) {
        return Promise.resolve();
    }
    return Api.get('vendor/products/get', withCoop({ id: productId })).then(function (res) {
        if (!res.ok) {
            return;
        }
        saleState.product = res.data;
        if (res.data.has_variants) {
            res.data.variants.filter(function (row) { return row.is_active; }).forEach(function (variant) {
                var option = createEl('option', '', variant.label + ' — ' + formatRM(res.data.price + variant.price_delta));
                option.value = variant.id;
                itemVariant.appendChild(option);
            });
            itemVariant.value = variantId ? String(variantId) : '';
            box.classList.remove('d-none');
        }
        updateRegularHint();
    });
}

function openItemForm(item) {
    document.getElementById('item-modal-title').textContent = item ? T('clearance.edit_item') : T('clearance.add_item');
    fillForm(itemForm, item || {});
    if (item) {
        document.getElementById('item-price').value = senToInput(item.sale_price);
    }
    selectProduct(item ? item.product_id : null, item ? item.variant_id : null);
    showModal('item-modal');
}

function deleteItem(item) {
    confirmDialog(T('clearance.delete_item_confirm', { name: item.product_name }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/clearance/item-delete', withCoop({ sale_id: saleState.id, id: item.id })).then(function (res) {
            if (moduleResponse(res)) {
                showToast(T('common.deleted'), 'success');
                loadSale();
            }
        });
    });
}

itemProduct.addEventListener('change', function () {
    selectProduct(Number(itemProduct.value) || null, null);
});
itemVariant.addEventListener('change', updateRegularHint);

document.getElementById('btn-add-item').addEventListener('click', function () {
    openItemForm(null);
});
document.getElementById('btn-edit-sale').addEventListener('click', function () {
    openSaleForm(saleState.sale);
});

bindSaleForm(loadSale);

bindForm(itemForm, 'vendor/clearance/item-save', function () {
    hideModal('item-modal');
    showToast(T('common.saved'), 'success');
    loadSale();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    payload.sale_id = saleState.id;
    payload.product_id = Number(payload.product_id) || null;
    payload.variant_id = Number(payload.variant_id) || null;
    return withCoop(payload);
});

// Jualan milik satu koperasi: tukar koperasi → kembali ke senarai
onModuleReady(function (coop) {
    if (saleState.loadedCoopId !== null && saleState.loadedCoopId !== coop.id) {
        window.location.href = pageUrl('vendor/addons/clearance.php');
        return;
    }
    saleState.loadedCoopId = coop.id;
    loadProducts();
    loadSale();
});
