// Pesanan belian: mod sunting (baharu/draf) atau mod lihat/terima

var poId = Number(queryParam('id')) || null;
var poState = { po: null, options: [], loadedCoopId: null };
var poForm = document.getElementById('po-form');
var linesBody = document.getElementById('po-lines');

function optionKey(productId, variantId) {
    return productId + ':' + (variantId || 0);
}

// ---------- Mod sunting ----------

function addLine(line) {
    line = line || { product_id: '', variant_id: 0, qty_ordered: 1, unit_cost: null };
    var row = createEl('tr');

    var productCell = createEl('td');
    var select = createEl('select', 'form-select form-select-sm app-po-product');
    select.required = true;
    select.setAttribute('aria-label', T('field.product'));
    var empty = createEl('option', '', T('common.choose'));
    empty.value = '';
    select.appendChild(empty);
    poState.options.forEach(function (option) {
        var item = createEl('option', '', option.name + ' (' + option.sku + ')');
        item.value = optionKey(option.product_id, option.variant_id);
        select.appendChild(item);
    });
    select.value = line.product_id ? optionKey(line.product_id, line.variant_id) : '';
    productCell.appendChild(select);
    row.appendChild(productCell);

    var qtyCell = createEl('td');
    var qty = createEl('input', 'form-control form-control-sm app-int-input');
    qty.type = 'number';
    qty.min = '1';
    qty.required = true;
    qty.value = line.qty_ordered;
    qty.setAttribute('aria-label', T('field.qty'));
    qtyCell.appendChild(qty);
    row.appendChild(qtyCell);

    var costCell = createEl('td');
    var cost = createEl('input', 'form-control form-control-sm app-money-input');
    cost.type = 'number';
    cost.min = '0';
    cost.step = '0.01';
    cost.value = line.unit_cost === null ? '' : senToInput(line.unit_cost);
    cost.setAttribute('aria-label', T('po.unit_cost'));
    costCell.appendChild(cost);
    row.appendChild(costCell);

    row.appendChild(createEl('td', 'text-end text-nowrap js-amount'));

    var removeCell = createEl('td', 'text-end');
    removeCell.appendChild(createButton('', 'btn-outline-danger', 'bi-x-lg', function () {
        row.remove();
        updateTotal();
    }, T('btn.delete')));
    row.appendChild(removeCell);

    // Pilih produk → isi kos lalai dari harga kos produk
    select.addEventListener('change', function () {
        var option = poState.options.filter(function (item) { return optionKey(item.product_id, item.variant_id) === select.value; })[0];
        if (option && !cost.value) {
            cost.value = senToInput(option.cost_price);
        }
        updateTotal();
    });
    qty.addEventListener('input', updateTotal);
    cost.addEventListener('input', updateTotal);

    linesBody.appendChild(row);
    updateTotal();
}

function readLines() {
    return Array.prototype.map.call(linesBody.rows, function (row) {
        var parts = row.querySelector('select').value.split(':');
        return {
            product_id: Number(parts[0]) || null,
            variant_id: Number(parts[1]) || 0,
            qty_ordered: parseInt(row.querySelector('input[type="number"]').value, 10) || 0,
            unit_cost: parseRM(row.querySelectorAll('input[type="number"]')[1].value)
        };
    });
}

function updateTotal() {
    var total = 0;
    var lines = readLines();
    Array.prototype.forEach.call(linesBody.rows, function (row, index) {
        var amount = lines[index].qty_ordered * lines[index].unit_cost;
        total += amount;
        row.querySelector('.js-amount').textContent = formatRM(amount);
    });
    document.getElementById('po-total').textContent = formatRM(total);
}

function savePo(submit) {
    var noLines = linesBody.rows.length === 0;
    document.getElementById('po-lines-error').classList.toggle('d-none', !noLines);
    if (noLines || !validateForm(poForm)) {
        return;
    }
    var ask = submit ? confirmDialog(T('po.submit_confirm'), { okText: T('po.submit') }) : Promise.resolve(true);
    ask.then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        submitForm(poForm, 'vendor/purchase-orders/save', function (data) {
            showToast(submit ? T('po.submitted') : T('common.saved'), 'success');
            window.location.href = pageUrl('vendor/purchase-order.php?id=' + data.id);
        }, function (payload) {
            payload.id = poId;
            payload.supplier_id = Number(payload.supplier_id);
            payload.items = readLines();
            payload.submit = submit;
            return withCoop(payload);
        });
    });
}

function showEditMode(po) {
    poForm.classList.remove('d-none');
    document.getElementById('po-view').classList.add('d-none');
    linesBody.replaceChildren();
    if (po) {
        fillForm(poForm, po);
        po.items.forEach(addLine);
        document.getElementById('page-title').textContent = po.po_no + ' — ' + T('po.status.draft');
    } else {
        fillForm(poForm, { supplier_id: queryParam('supplier_id') || '' });
        addLine(null);
    }
    document.getElementById('btn-cancel-po').classList.toggle('d-none', !po);
}

// ---------- Mod lihat / terima ----------

function showViewMode(po) {
    poForm.classList.add('d-none');
    document.getElementById('po-view').classList.remove('d-none');
    document.getElementById('page-title').textContent = po.po_no;
    document.getElementById('view-supplier').textContent = po.supplier_name;
    document.getElementById('view-status').replaceChildren(statusBadge(po.status, 'po.status'));
    document.getElementById('view-ordered').textContent = po.ordered_at ? formatDate(po.ordered_at, true) : '—';
    document.getElementById('view-note').textContent = po.note || '—';
    document.getElementById('view-total').textContent = formatRM(po.total);

    var canReceive = ['ordered', 'partial'].indexOf(po.status) !== -1;
    document.querySelectorAll('.js-receive-col').forEach(function (el) {
        el.classList.toggle('d-none', !canReceive);
    });
    var nothingReceived = po.items.every(function (item) { return item.qty_received === 0; });
    document.getElementById('btn-cancel-po').classList.toggle('d-none', !(po.status === 'ordered' && nothingReceived));
    document.getElementById('btn-print-po').classList.remove('d-none');

    var columns = [
        { render: function (item) { return createStack(item.variant_label ? item.product_name + ' — ' + item.variant_label : item.product_name, item.sku); } },
        { key: 'qty_ordered', className: 'text-end' },
        { className: 'text-end', render: function (item) {
            var done = item.qty_received >= item.qty_ordered;
            return createEl('span', done ? 'text-success fw-semibold' : '', item.qty_received);
        } },
        { className: 'text-end', render: function (item) { return formatRM(item.unit_cost); } },
        { className: 'text-end', render: function (item) { return formatRM(item.amount); } }
    ];
    if (canReceive) {
        columns.push({ render: receiveInput });
    }
    renderTable('view-lines', po.items, columns);
}

function receiveInput(item) {
    var remaining = item.qty_ordered - item.qty_received;
    var input = createEl('input', 'form-control form-control-sm app-int-input js-receive');
    input.type = 'number';
    input.min = '0';
    input.max = remaining;
    input.value = remaining;
    input.disabled = remaining === 0;
    input.setAttribute('data-item-id', item.id);
    input.setAttribute('aria-label', T('po.receive_now') + ': ' + item.product_name);
    return input;
}

document.getElementById('receive-form').addEventListener('submit', function (event) {
    event.preventDefault();
    if (!validateForm(event.target)) {
        return;
    }
    var lines = Array.prototype.map.call(document.querySelectorAll('.js-receive'), function (input) {
        return { item_id: Number(input.getAttribute('data-item-id')), qty: parseInt(input.value, 10) || 0 };
    });
    var total = lines.reduce(function (sum, line) { return sum + line.qty; }, 0);
    confirmDialog(T('po.receive_confirm', { n: total }), { okText: T('po.receive'), okClass: 'btn-success' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/purchase-orders/receive', withCoop({ id: poId, lines: lines })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('po.received_toast', { n: total }), 'success');
            event.target.classList.remove('was-validated');
            poState.po = res.data;
            showViewMode(res.data);
        });
    });
});

// ---------- Muat ----------

function loadPo() {
    var options = Api.get('vendor/products/options', withCoop());
    var suppliers = Api.get('vendor/suppliers/list', { per_page: 100, status: 'active' });
    Promise.all([options, suppliers]).then(function (results) {
        poState.options = results[0].ok ? results[0].data : [];
        fillSelect('po-supplier', results[1].ok ? results[1].data.items : [], 'id', function (supplier) { return supplier.name; }, true);
        if (!poId) {
            showEditMode(null);
            return;
        }
        Api.get('vendor/purchase-orders/get', withCoop({ id: poId })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            poState.po = res.data;
            if (res.data.status === 'draft') {
                showEditMode(res.data);
            } else {
                showViewMode(res.data);
            }
        });
    });
}

document.getElementById('btn-add-line').addEventListener('click', function () {
    addLine(null);
});
document.getElementById('btn-save-draft').addEventListener('click', function () {
    savePo(false);
});
document.getElementById('btn-submit-po').addEventListener('click', function () {
    savePo(true);
});
poForm.addEventListener('submit', function (event) {
    event.preventDefault();
});
document.getElementById('btn-print-po').addEventListener('click', function () {
    window.print();
});
document.getElementById('btn-cancel-po').addEventListener('click', function () {
    confirmDialog(T('po.cancel_confirm', { no: poState.po.po_no }), { okText: T('po.cancel'), okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/purchase-orders/cancel', withCoop({ id: poId })).then(function (res) {
            showToast(res.ok ? T('po.cancelled_toast') : res.error, res.ok ? 'success' : 'danger');
            if (res.ok) {
                poState.po = res.data;
                showViewMode(res.data);
            }
        });
    });
});

// PO milik satu koperasi: tukar koperasi → kembali ke senarai
onCooperativeReady(function (coop) {
    if (poState.loadedCoopId !== null && poState.loadedCoopId !== coop.id) {
        window.location.href = pageUrl('vendor/purchase-orders.php');
        return;
    }
    poState.loadedCoopId = coop.id;
    loadPo();
});
