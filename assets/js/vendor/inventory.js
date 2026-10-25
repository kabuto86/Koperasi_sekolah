// Inventori koperasi semasa

var inventoryList = { page: 1, per_page: 20, q: '', category_id: '', low_only: queryParam('low_only') === '1' };
var inventoryItem = null;

function itemLabel(row) {
    return row.variant_label ? row.product_name + ' — ' + row.variant_label : row.product_name;
}

function loadInventory() {
    tableLoading('inventory-body', 7);
    Api.get('vendor/inventory/list', withCoop(inventoryList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var lowAlert = document.getElementById('low-alert');
        lowAlert.classList.toggle('d-none', res.data.low_count === 0);
        document.getElementById('low-alert-text').textContent = T('inventory.low_alert', { n: res.data.low_count });

        renderTable('inventory-body', res.data.items, [
            { render: function (row) {
                var stack = createStack(itemLabel(row), row.sku);
                if (!row.is_active) {
                    stack.appendChild(createEl('span', 'badge text-bg-secondary', T('common.inactive')));
                }
                return stack;
            } },
            { key: 'category_name' },
            { key: 'qty_on_hand', className: 'text-end' },
            { key: 'qty_reserved', className: 'text-end text-body-secondary' },
            { className: 'text-end', render: function (row) {
                var cls = row.qty_available <= 0 ? 'badge text-bg-danger' : (row.is_low ? 'badge text-bg-warning' : 'fw-semibold');
                return createEl('span', cls, row.qty_available);
            } },
            { key: 'min_stock', className: 'text-end text-body-secondary' },
            { className: 'text-end text-nowrap', render: inventoryActions }
        ]);
        renderPagination('inventory-pagination', res.data, function (page) {
            inventoryList.page = page;
            loadInventory();
        });
    });
}

function inventoryActions(row) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    wrap.appendChild(createButton('', 'btn-outline-success', 'bi-box-arrow-in-down', function () {
        openItemModal('stock-in', row);
    }, T('inventory.stock_in')));
    wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-sliders', function () {
        openItemModal('adjust', row);
    }, T('inventory.adjust')));
    wrap.appendChild(createIconLink(pageUrl('vendor/stock-movements.php?product_id=' + row.product_id + '&variant_id=' + row.variant_id),
        'bi-clock-history', T('menu.stock_movements')));
    return wrap;
}

function openItemModal(kind, row) {
    inventoryItem = row;
    var form = document.getElementById(kind + '-form');
    fillForm(form, kind === 'adjust' ? { new_qty: row.qty_on_hand, min_stock: row.min_stock } : {});
    form.querySelector('.js-item-name').textContent = itemLabel(row);
    form.querySelector('.js-item-stock').textContent = T('inventory.stock_line', {
        on_hand: row.qty_on_hand, reserved: row.qty_reserved, available: row.qty_available, unit: row.unit
    });
    updateAdjustChange();
    showModal(kind + '-modal');
}

// Papar perubahan (+/-) semasa pengguna menaip kiraan sebenar
function updateAdjustChange() {
    if (!inventoryItem) {
        return;
    }
    var value = parseInt(document.getElementById('adjust-qty').value, 10);
    var change = isNaN(value) ? 0 : value - inventoryItem.qty_on_hand;
    var box = document.getElementById('adjust-change');
    box.textContent = change === 0 ? '' : T('inventory.change', { change: (change > 0 ? '+' : '') + change });
    box.className = 'form-text ' + (change < 0 ? 'text-danger' : 'text-success');
}

function itemPayload(payload) {
    payload.product_id = inventoryItem.product_id;
    payload.variant_id = inventoryItem.variant_id;
    return withCoop(payload);
}

function loadCategoryFilter() {
    Api.get('vendor/categories/list', withCoop()).then(function (res) {
        if (res.ok) {
            fillSelect('inv-category', res.data, 'id', function (category) { return category.name; }, true);
        }
    });
}

document.getElementById('adjust-qty').addEventListener('input', updateAdjustChange);
document.getElementById('inv-low').checked = inventoryList.low_only;

bindForm('stock-in-form', 'vendor/inventory/stock-in', function () {
    hideModal('stock-in-modal');
    showToast(T('inventory.stock_in_saved'), 'success');
    loadInventory();
}, itemPayload);

bindForm('adjust-form', 'vendor/inventory/adjust', function () {
    hideModal('adjust-modal');
    showToast(T('common.saved'), 'success');
    loadInventory();
}, itemPayload);

bindListFilters('inventory-filters', inventoryList, loadInventory);

onCooperativeReady(function () {
    inventoryList.page = 1;
    loadCategoryFilter();
    loadInventory();
});
