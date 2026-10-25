// Skrin serah barang (PELAN 7).
// Aliran papan kekunci: taip / imbas → Enter → (1 hasil: terus buka) → Tab/Enter → selesai → Enter → kembali ke carian.
// Pengimbas QR/barcode USB bertindak seperti papan kekunci: menaip token + Enter.

var handoverInput = document.getElementById('handover-q');
var handoverState = { results: [], order: null, afterCash: false };

function itemLabel(item) {
    return item.variant_label ? item.product_name + ' — ' + item.variant_label : item.product_name;
}

function remainingQty(item) {
    return item.qty - item.collected_qty - (item.cancelled_qty || 0);
}

function childrenText(order) {
    return order.groups.map(childLine).join(' / ');
}

// ---------- Carian ----------

function runSearch(autoOpen) {
    var params = withCoop({
        q: handoverInput.value.trim(),
        grade: document.getElementById('handover-grade').value,
        class_name: document.getElementById('handover-class').value
    });
    var status = document.getElementById('handover-status');
    if (!params.q && !params.grade && !params.class_name) {
        handoverState.results = [];
        document.getElementById('handover-results').replaceChildren();
        status.textContent = T('handover.start_hint');
        return;
    }
    status.textContent = T('common.loading');
    Api.get('vendor/handover/search', params).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        handoverState.results = res.data.items;
        renderResults();
        status.textContent = res.data.items.length ? T('handover.result_count', { n: res.data.items.length }) : T('handover.no_result');
        // Satu hasil (biasanya imbasan QR / no. pendek) → terus ke langkah seterusnya
        if (autoOpen && res.data.items.length === 1) {
            startHandover(res.data.items[0]);
        } else if (autoOpen && res.data.items.length > 1) {
            var firstButton = document.querySelector('#handover-results .js-primary');
            if (firstButton) {
                firstButton.focus();
            }
        }
    });
}

function renderResults() {
    var box = document.getElementById('handover-results');
    box.replaceChildren();
    handoverState.results.forEach(function (order) {
        box.appendChild(resultCard(order));
    });
}

function resultCard(order) {
    var col = createEl('div', 'col-12 col-md-6 col-xxl-4');
    var card = createEl('div', 'card border-0 shadow-sm h-100 app-handover-card');
    var body = createEl('div', 'card-body');

    var head = createEl('div', 'd-flex align-items-start gap-3 mb-2');
    head.appendChild(createEl('div', 'app-short-code', order.short_code));
    var who = createEl('div', 'flex-grow-1 min-w-0');
    order.groups.forEach(function (group) {
        who.appendChild(createEl('div', 'fw-bold fs-5 lh-sm', group.child_name));
        who.appendChild(createEl('div', 'text-body-secondary small', childClass(group)));
    });
    head.appendChild(who);
    body.appendChild(head);

    body.appendChild(createEl('div', 'small mb-2', T('handover.parent_line', { name: order.customer_name })));
    var badges = createEl('div', 'd-flex flex-wrap gap-1 mb-2');
    badges.appendChild(statusBadge(order.status));
    badges.appendChild(createEl('span', 'badge ' + (order.payment_status === 'paid' ? 'text-bg-success' : 'text-bg-warning'),
        T('payment.status.' + order.payment_status)));
    if (order.requires_code) {
        badges.appendChild(createEl('span', 'badge text-bg-dark', T('handover.code_badge')));
    }
    body.appendChild(badges);

    var list = createEl('ul', 'small mb-0 ps-3');
    order.groups.forEach(function (group) {
        group.items.forEach(function (item) {
            if (remainingQty(item) > 0) {
                list.appendChild(createEl('li', '', remainingQty(item) + ' × ' + itemLabel(item)));
            }
        });
    });
    body.appendChild(list);
    card.appendChild(body);

    var footer = createEl('div', 'card-footer bg-body d-grid');
    footer.appendChild(primaryAction(order));
    card.appendChild(footer);
    col.appendChild(card);
    return col;
}

// Butang utama ikut keadaan: belum siap / bayar tunai dahulu / serah
function primaryAction(order) {
    var ready = ['ready', 'partially_collected'].indexOf(order.status) !== -1;
    if (order.pickup_locked) {
        return disabledButton(T('handover.locked_short'));
    }
    if (order.payment_status !== 'paid' && order.payment_method === 'cash') {
        return actionButton(T('handover.receive_cash_total', { amount: formatRM(order.total) }), 'btn-warning', 'bi-cash-coin', order);
    }
    if (!ready) {
        return disabledButton(T('handover.not_ready_short'));
    }
    return actionButton(T('handover.hand_over'), 'btn-success', 'bi-hand-index', order);
}

function actionButton(text, className, icon, order) {
    var button = createButton(text, 'btn-lg js-primary ' + className, icon, function () {
        startHandover(order);
    });
    return button;
}

function disabledButton(text) {
    var button = createEl('button', 'btn btn-lg btn-outline-secondary', text);
    button.type = 'button';
    button.disabled = true;
    return button;
}

// ---------- Langkah: tunai → serah ----------

function startHandover(order) {
    handoverState.order = order;
    if (order.payment_status !== 'paid' && order.payment_method === 'cash') {
        openCash(order);
        return;
    }
    if (['ready', 'partially_collected'].indexOf(order.status) === -1 || order.pickup_locked) {
        showToast(order.pickup_locked ? T('handover.locked') : T('handover.not_ready'), 'warning');
        return;
    }
    openHandover(order);
}

function openCash(order) {
    var form = document.getElementById('cash-form');
    resetForm(form);
    document.getElementById('cash-due').textContent = formatRM(order.total);
    document.getElementById('cash-received').value = senToInput(order.total);
    updateChange();
    showModal('cash-modal');
}

function updateChange() {
    var received = parseRM(document.getElementById('cash-received').value);
    var change = received - handoverState.order.total;
    var box = document.getElementById('cash-change');
    box.textContent = change >= 0 ? T('handover.change', { amount: formatRM(change) }) : T('handover.cash_not_enough');
    box.className = 'fs-5 mt-2 ' + (change >= 0 ? 'text-success' : 'text-danger');
}

function openHandover(order) {
    var form = document.getElementById('handover-form');
    resetForm(form);
    form.classList.remove('d-none');
    document.getElementById('ho-success').classList.add('d-none');
    document.getElementById('ho-code').textContent = order.short_code;
    document.getElementById('ho-children').textContent = childrenText(order);
    document.getElementById('ho-parent').textContent = T('handover.parent_line', { name: order.customer_name });

    // Lalai ikut pilihan ibu bapa semasa checkout
    var type = order.pickup_by_type || 'student';
    document.getElementById('ho-type-' + type).checked = true;
    document.getElementById('ho-name').value = order.pickup_by_name || '';

    var codeSection = document.getElementById('ho-code-section');
    var pin = document.getElementById('ho-pin');
    codeSection.classList.toggle('d-none', !order.requires_code);
    pin.required = order.requires_code;

    renderHandoverItems(order);
    showModal('handover-modal');
}

// Setiap item: kotak tanda + kuantiti (ambil separa dibenarkan)
function renderHandoverItems(order) {
    var box = document.getElementById('ho-items');
    box.replaceChildren();
    order.groups.forEach(function (group) {
        var section = createEl('div', 'border rounded p-2 mb-2');
        section.appendChild(createEl('div', 'small fw-semibold mb-1', childLine(group)));
        group.items.forEach(function (item) {
            var left = remainingQty(item);
            if (left <= 0) {
                return;
            }
            var row = createEl('div', 'd-flex align-items-center gap-2 py-1');
            var check = createEl('input', 'form-check-input js-ho-check');
            check.type = 'checkbox';
            check.checked = true;
            check.id = 'ho-item-' + item.id;
            check.setAttribute('data-item-id', item.id);
            var label = createEl('label', 'form-check-label flex-grow-1', itemLabel(item));
            label.htmlFor = check.id;
            var qty = createEl('input', 'form-control form-control-sm app-int-input js-ho-qty');
            qty.type = 'number';
            qty.min = '0';
            qty.max = left;
            qty.value = left;
            qty.setAttribute('data-item-id', item.id);
            qty.setAttribute('aria-label', T('field.qty') + ': ' + itemLabel(item));
            check.addEventListener('change', function () {
                qty.disabled = !check.checked;
            });
            row.appendChild(check);
            row.appendChild(label);
            row.appendChild(qty);
            row.appendChild(createEl('span', 'small text-body-secondary text-nowrap', T('handover.of_qty', { n: left })));
            section.appendChild(row);
        });
        box.appendChild(section);
    });
}

function readHandoverItems() {
    return Array.prototype.filter.call(document.querySelectorAll('.js-ho-check'), function (check) {
        return check.checked;
    }).map(function (check) {
        var qty = document.querySelector('.js-ho-qty[data-item-id="' + check.getAttribute('data-item-id') + '"]');
        return { item_id: Number(check.getAttribute('data-item-id')), qty: parseInt(qty.value, 10) || 0 };
    });
}

// Pilih jenis pengambil → isi nama lalai (murid = nama anak, ibu bapa = nama ibu bapa, wakil = kosong)
function fillCollectorName(type) {
    var order = handoverState.order;
    var name = '';
    if (type === 'student') {
        name = order.groups.map(function (group) { return group.child_name; }).join(', ');
    } else if (type === 'parent') {
        name = order.customer_name;
    } else if (order.pickup_by_type === 'proxy') {
        name = order.pickup_by_name;
    }
    document.getElementById('ho-name').value = name;
}

function showSuccess(data, collectorName) {
    document.getElementById('handover-form').classList.add('d-none');
    document.getElementById('ho-success').classList.remove('d-none');
    document.getElementById('ho-success-title').textContent = T(data.status === 'collected' ? 'handover.done_full' : 'handover.done_partial');
    document.getElementById('ho-success-detail').textContent = T('handover.done_detail', { name: collectorName, code: data.order.short_code });
    document.getElementById('ho-next').focus();
}

// Dipanggil apabila modal ditutup selepas berjaya (butang "Seterusnya", Esc atau ×)
function backToSearch() {
    document.getElementById('ho-success').classList.add('d-none');
    handoverInput.value = '';
    runSearch(false);
    setTimeout(function () {
        handoverInput.focus();
    }, 300);
}

// ---------- Acara ----------

document.getElementById('handover-search').addEventListener('submit', function (event) {
    event.preventDefault();
    runSearch(true);
});
handoverInput.addEventListener('input', debounce(function () {
    runSearch(false);
}, 350));
['handover-grade', 'handover-class'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () {
        runSearch(false);
    });
});

document.getElementById('cash-received').addEventListener('input', updateChange);
document.getElementById('cash-modal').addEventListener('shown.bs.modal', function () {
    var input = document.getElementById('cash-received');
    input.focus();
    input.select();
});

document.getElementById('cash-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var form = event.target;
    var received = parseRM(document.getElementById('cash-received').value);
    if (!validateForm(form) || received < handoverState.order.total) {
        document.getElementById('cash-received').classList.add('is-invalid');
        return;
    }
    Api.post('vendor/handover/receive-cash', withCoop({ id: handoverState.order.id, amount_received: received })).then(function (res) {
        if (!res.ok) {
            showFormErrors(form, res.errors);
            showToast(res.error, 'danger');
            return;
        }
        showToast(T('handover.cash_received', { change: formatRM(res.data.change) }), 'success');
        handoverState.order = res.data.order;
        handoverState.afterCash = true;
        hideModal('cash-modal');
    });
});

// Selepas tunai diterima → terus ke langkah serah (jika pesanan sudah sedia)
document.getElementById('cash-modal').addEventListener('hidden.bs.modal', function () {
    if (!handoverState.afterCash) {
        return;
    }
    handoverState.afterCash = false;
    runSearch(false);
    startHandover(handoverState.order);
});

document.getElementById('handover-modal').addEventListener('shown.bs.modal', function () {
    var checked = document.querySelector('#handover-form [name="picked_by_type"]:checked');
    if (checked) {
        checked.focus();
    }
});

document.querySelectorAll('#handover-form [name="picked_by_type"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        fillCollectorName(radio.value);
    });
});

document.getElementById('handover-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var form = event.target;
    var items = readHandoverItems();
    if (items.length === 0 || items.every(function (item) { return item.qty === 0; })) {
        showToast(T('handover.no_items'), 'warning');
        return;
    }
    var collectorName = document.getElementById('ho-name').value.trim();
    submitForm(form, 'vendor/handover/collect', function (data) {
        showSuccess(data, collectorName);
    }, function (payload) {
        payload.id = handoverState.order.id;
        payload.items = items;
        return withCoop(payload);
    });
});

document.getElementById('ho-next').addEventListener('click', function () {
    hideModal('handover-modal');
});
document.getElementById('handover-modal').addEventListener('hidden.bs.modal', function () {
    if (!document.getElementById('ho-success').classList.contains('d-none')) {
        backToSearch();
    }
});

onCooperativeReady(function () {
    bindGradeClassFilters('handover-grade', 'handover-class').then(function () {
        var initial = queryParam('q');
        if (initial) {
            handoverInput.value = initial;
        }
        runSearch(Boolean(initial));
        handoverInput.focus();
    });
});
