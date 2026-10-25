// Kiraan stock take. Enter = ke baris seterusnya. Kiraan disimpan dalam memori halaman sehingga "Simpan"

var takeId = Number(queryParam('id'));
var takeState = { data: null, counts: {}, dirty: false, loadedCoopId: null };

function loadTake() {
    Api.get('vendor/stock-takes/get', withCoop({ id: takeId })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        takeState.data = res.data;
        takeState.counts = {};
        res.data.items.forEach(function (item) {
            takeState.counts[item.id] = item.qty_counted;
        });
        takeState.dirty = false;
        renderTake();
    });
}

function isOpen() {
    return takeState.data && takeState.data.status === 'open';
}

function renderSummary() {
    var items = takeState.data.items;
    var counted = items.filter(function (item) { return takeState.counts[item.id] !== null; });
    var variance = counted.reduce(function (sum, item) { return sum + takeState.counts[item.id] - item.qty_system; }, 0);
    document.getElementById('take-status').replaceChildren(createEl('span', 'badge ' + (isOpen() ? 'text-bg-warning' : 'text-bg-success'),
        T('stocktake.status.' + takeState.data.status)));
    document.getElementById('take-started').textContent = formatDate(takeState.data.started_at, true) + ' · ' + takeState.data.started_by;
    document.getElementById('take-progress').textContent = counted.length + ' / ' + items.length;
    var varianceBox = document.getElementById('take-variance');
    varianceBox.textContent = (variance > 0 ? '+' : '') + variance;
    varianceBox.className = 'fw-semibold ' + (variance < 0 ? 'text-danger' : (variance > 0 ? 'text-success' : ''));
    document.getElementById('btn-save-counts').classList.toggle('d-none', !isOpen());
    document.getElementById('btn-complete').classList.toggle('d-none', !isOpen());
}

function visibleItems() {
    var q = document.getElementById('take-q').value.trim().toLowerCase();
    var uncountedOnly = document.getElementById('take-uncounted').checked;
    return takeState.data.items.filter(function (item) {
        var text = (item.product_name + ' ' + (item.variant_label || '') + ' ' + item.sku).toLowerCase();
        return (!q || text.indexOf(q) !== -1) && (!uncountedOnly || takeState.counts[item.id] === null);
    });
}

function renderTake() {
    renderSummary();
    renderTable('take-body', visibleItems(), [
        { render: function (item) {
            return createStack(item.variant_label ? item.product_name + ' — ' + item.variant_label : item.product_name, item.sku);
        } },
        { key: 'category_name', className: 'small' },
        { key: 'qty_system', className: 'text-end' },
        { render: countInput },
        { className: 'text-end fw-semibold js-variance', render: varianceText }
    ]);
}

function varianceText(item) {
    var counted = takeState.counts[item.id];
    if (counted === null) {
        return createEl('span', 'text-body-secondary', '—');
    }
    var diff = counted - item.qty_system;
    return createEl('span', diff < 0 ? 'text-danger' : (diff > 0 ? 'text-success' : ''), (diff > 0 ? '+' : '') + diff);
}

function countInput(item) {
    var input = createEl('input', 'form-control form-control-sm app-int-input js-count');
    input.type = 'number';
    input.min = '0';
    input.inputMode = 'numeric';
    input.value = takeState.counts[item.id] === null ? '' : takeState.counts[item.id];
    input.disabled = !isOpen();
    input.setAttribute('aria-label', T('stocktake.counted_qty') + ': ' + item.product_name + (item.variant_label ? ' ' + item.variant_label : ''));
    input.addEventListener('input', function () {
        var value = parseInt(input.value, 10);
        takeState.counts[item.id] = isNaN(value) || value < 0 ? null : value;
        takeState.dirty = true;
        input.closest('tr').querySelector('.js-variance').replaceChildren(varianceText(item));
        renderSummary();
    });
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            var inputs = Array.prototype.slice.call(document.querySelectorAll('.js-count'));
            var next = inputs[inputs.indexOf(input) + 1];
            if (next) {
                next.focus();
                next.select();
            }
        }
    });
    return input;
}

function saveCounts() {
    var counts = Object.keys(takeState.counts).map(function (itemId) {
        return { item_id: Number(itemId), qty_counted: takeState.counts[itemId] };
    });
    return Api.post('vendor/stock-takes/save-counts', withCoop({ id: takeId, counts: counts })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return false;
        }
        takeState.dirty = false;
        return true;
    });
}

document.getElementById('btn-save-counts').addEventListener('click', function () {
    saveCounts().then(function (ok) {
        if (ok) {
            showToast(T('stocktake.counts_saved'), 'success');
        }
    });
});

document.getElementById('btn-complete').addEventListener('click', function () {
    var items = takeState.data.items;
    var counted = items.filter(function (item) { return takeState.counts[item.id] !== null; }).length;
    var message = T('stocktake.complete_confirm', { counted: counted, uncounted: items.length - counted });
    confirmDialog(message, { okText: T('stocktake.complete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        saveCounts().then(function (ok) {
            if (!ok) {
                return;
            }
            Api.post('vendor/stock-takes/complete', withCoop({ id: takeId })).then(function (res) {
                showToast(res.ok ? T('stocktake.completed_toast') : res.error, res.ok ? 'success' : 'danger');
                loadTake();
            });
        });
    });
});

document.getElementById('take-q').addEventListener('input', debounce(renderTake, 250));
document.getElementById('take-uncounted').addEventListener('change', renderTake);

// Amaran jika keluar dengan kiraan belum disimpan
window.addEventListener('beforeunload', function (event) {
    if (takeState.dirty) {
        event.preventDefault();
        event.returnValue = '';
    }
});

onCooperativeReady(function (coop) {
    if (takeState.loadedCoopId !== null && takeState.loadedCoopId !== coop.id) {
        window.location.href = pageUrl('vendor/stock-takes.php');
        return;
    }
    takeState.loadedCoopId = coop.id;
    loadTake();
});
