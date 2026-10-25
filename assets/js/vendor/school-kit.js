// Borang kit sekolah

var kitForm = document.getElementById('kit-form');
var kitItemsBody = document.getElementById('kit-items');
var kitPage = { id: Number(queryParam('id')) || null, kit: null, options: [], loadedCoopId: null };

function optionKey(productId, variantId) {
    return productId + ':' + (variantId || '');
}

function findOption(key) {
    return kitPage.options.filter(function (option) { return optionKey(option.product_id, option.variant_id) === key; })[0] || null;
}

// Produk bervarian tanpa varian dipilih = ibu bapa pilih saiz/warna sendiri
function optionLabel(option) {
    if (option.variant_label) {
        return option.name + ' — ' + option.variant_label;
    }
    return option.has_variants ? option.name + ' — ' + T('kit.parent_chooses_variant') : option.name;
}

function addItemRow(item) {
    item = item || { product_id: '', variant_id: null, qty: 1, is_mandatory: true, note: '' };
    var row = createEl('tr');

    var moveCell = createEl('td', 'text-nowrap');
    moveCell.appendChild(createButton('', 'btn-outline-secondary px-1', 'bi-arrow-up', function () {
        if (row.previousElementSibling) {
            kitItemsBody.insertBefore(row, row.previousElementSibling);
            refreshKit();
        }
    }, T('kit.move_up')));
    moveCell.appendChild(createButton('', 'btn-outline-secondary px-1 ms-1', 'bi-arrow-down', function () {
        if (row.nextElementSibling) {
            kitItemsBody.insertBefore(row.nextElementSibling, row);
            refreshKit();
        }
    }, T('kit.move_down')));
    row.appendChild(moveCell);

    var productCell = createEl('td');
    var select = createEl('select', 'form-select form-select-sm app-po-product');
    select.required = true;
    select.setAttribute('data-field', 'product');
    select.setAttribute('aria-label', T('field.product'));
    var empty = createEl('option', '', T('common.choose'));
    empty.value = '';
    select.appendChild(empty);
    kitPage.options.forEach(function (option) {
        var element = createEl('option', '', optionLabel(option) + ' · ' + formatRM(option.price));
        element.value = optionKey(option.product_id, option.variant_id);
        select.appendChild(element);
    });
    select.value = item.product_id ? optionKey(item.product_id, item.variant_id) : '';
    productCell.appendChild(select);
    row.appendChild(productCell);

    var qtyCell = createEl('td');
    var qty = createEl('input', 'form-control form-control-sm app-int-input');
    qty.type = 'number';
    qty.min = '1';
    qty.required = true;
    qty.value = item.qty;
    qty.setAttribute('data-field', 'qty');
    qty.setAttribute('aria-label', T('field.qty'));
    qtyCell.appendChild(qty);
    row.appendChild(qtyCell);

    var mandatoryCell = createEl('td');
    var mandatoryWrap = createEl('div', 'form-check form-switch mb-0');
    var mandatory = createEl('input', 'form-check-input');
    mandatory.type = 'checkbox';
    mandatory.setAttribute('role', 'switch');
    mandatory.checked = item.is_mandatory;
    mandatory.setAttribute('data-field', 'is_mandatory');
    mandatory.setAttribute('aria-label', T('kit.mandatory'));
    mandatoryWrap.appendChild(mandatory);
    mandatoryCell.appendChild(mandatoryWrap);
    row.appendChild(mandatoryCell);

    var noteCell = createEl('td');
    var note = createEl('input', 'form-control form-control-sm');
    note.type = 'text';
    note.maxLength = 80;
    note.value = item.note || '';
    note.setAttribute('data-field', 'note');
    note.setAttribute('aria-label', T('field.note'));
    noteCell.appendChild(note);
    row.appendChild(noteCell);

    row.appendChild(createEl('td', 'text-end text-nowrap js-amount'));

    var removeCell = createEl('td', 'text-end');
    removeCell.appendChild(createButton('', 'btn-outline-danger', 'bi-x-lg', function () {
        row.remove();
        refreshKit();
    }, T('btn.delete')));
    row.appendChild(removeCell);

    [select, qty, mandatory, note].forEach(function (input) {
        input.addEventListener('input', refreshKit);
        input.addEventListener('change', refreshKit);
    });
    kitItemsBody.appendChild(row);
    refreshKit();
}

function readItems() {
    return Array.prototype.map.call(kitItemsBody.rows, function (row) {
        var option = findOption(row.querySelector('[data-field="product"]').value);
        return {
            product_id: option ? option.product_id : null,
            variant_id: option ? option.variant_id : null,
            option: option,
            qty: parseInt(row.querySelector('[data-field="qty"]').value, 10) || 0,
            is_mandatory: row.querySelector('[data-field="is_mandatory"]').checked,
            note: row.querySelector('[data-field="note"]').value.trim()
        };
    });
}

// Jumlah + pratonton seperti dilihat ibu bapa (semua siap ditanda, boleh nyahtanda)
function refreshKit() {
    var items = readItems();
    var total = 0;
    var mandatoryTotal = 0;
    var preview = document.getElementById('kit-preview');
    preview.replaceChildren();

    items.forEach(function (item, index) {
        var amount = item.option ? item.option.price * item.qty : 0;
        total += amount;
        if (item.is_mandatory) {
            mandatoryTotal += amount;
        }
        kitItemsBody.rows[index].querySelector('.js-amount').textContent = formatRM(amount);
        if (item.option) {
            preview.appendChild(previewItem(item, amount));
        }
    });
    if (preview.children.length === 0) {
        preview.appendChild(createEl('li', 'list-group-item text-body-secondary', T('kit.no_items')));
    }
    document.getElementById('kit-total').textContent = formatRM(total);
    document.getElementById('kit-mandatory').textContent = formatRM(mandatoryTotal);
}

function previewItem(item, amount) {
    var li = createEl('li', 'list-group-item d-flex gap-2');
    var check = createEl('input', 'form-check-input mt-1');
    check.type = 'checkbox';
    check.checked = true;
    check.disabled = true;
    check.setAttribute('aria-hidden', 'true');
    li.appendChild(check);
    var text = createEl('div', 'flex-grow-1');
    text.appendChild(createEl('div', '', item.qty + ' × ' + optionLabel(item.option)));
    if (item.is_mandatory) {
        text.appendChild(createEl('span', 'badge text-bg-warning me-1', T('kit.mandatory_badge')));
    }
    if (item.note) {
        text.appendChild(createEl('span', 'text-body-secondary', item.note));
    }
    li.appendChild(text);
    li.appendChild(createEl('div', 'text-nowrap', formatRM(amount)));
    return li;
}

function setStatus(isPublished) {
    var badge = document.getElementById('kit-status');
    badge.textContent = T(isPublished ? 'kit.published' : 'kit.draft');
    badge.className = 'badge ' + (isPublished ? 'text-bg-success' : 'text-bg-light border');
    document.getElementById('btn-publish').classList.toggle('d-none', isPublished);
    document.getElementById('btn-unpublish').classList.toggle('d-none', !isPublished);
    document.getElementById('btn-delete-kit').classList.toggle('d-none', !kitPage.id || isPublished);
}

function saveKit(publish) {
    var empty = kitItemsBody.rows.length === 0;
    document.getElementById('kit-items-error').classList.toggle('d-none', !empty);
    if (empty) {
        return;
    }
    submitForm(kitForm, 'vendor/kits/save', function (data) {
        showToast(publish ? T('kit.published_toast') : T('common.saved'), 'success');
        if (!kitPage.id) {
            window.location.href = pageUrl('vendor/school-kit.php?id=' + data.id);
            return;
        }
        setStatus(data.is_published);
    }, function (payload) {
        payload.id = kitPage.id;
        payload.items = readItems().map(function (item) {
            return { product_id: item.product_id, variant_id: item.variant_id, qty: item.qty, is_mandatory: item.is_mandatory, note: item.note };
        });
        payload.publish = publish;
        return withCoop(payload);
    });
}

function loadKitPage() {
    var grades = Api.get('vendor/grades/list', withCoop());
    var options = Api.get('vendor/kits/product-options', withCoop());
    Promise.all([grades, options]).then(function (results) {
        fillSelect('kit-grade', (results[0].ok ? results[0].data : []).map(function (code) { return { code: code }; }), 'code', function (item) {
            return T('grade.' + item.code);
        }, false);
        kitPage.options = results[1].ok ? results[1].data : [];
        kitItemsBody.replaceChildren();

        if (!kitPage.id) {
            fillForm(kitForm, { grade_level_code: queryParam('grade') || '', school_year: Number(queryParam('year')) || new Date().getFullYear() + 1 });
            addItemRow(null);
            setStatus(false);
            return;
        }
        Api.get('vendor/kits/get', withCoop({ id: kitPage.id })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                kitForm.classList.add('d-none');
                return;
            }
            kitPage.kit = res.data;
            fillForm(kitForm, res.data);
            res.data.items.forEach(addItemRow);
            setStatus(res.data.is_published);
            document.getElementById('page-title').textContent = T('kit.title_for', { grade: T('grade.' + res.data.grade_level_code), year: res.data.school_year });
        });
    });
}

document.getElementById('btn-add-item').addEventListener('click', function () {
    addItemRow(null);
});
document.getElementById('btn-save-draft').addEventListener('click', function () {
    saveKit(false);
});
document.getElementById('btn-publish').addEventListener('click', function () {
    confirmDialog(T('kit.publish_confirm'), { okText: T('kit.publish') }).then(function (confirmed) {
        if (confirmed) {
            saveKit(true);
        }
    });
});
document.getElementById('btn-unpublish').addEventListener('click', function () {
    Api.post('vendor/kits/set-published', withCoop({ id: kitPage.id, is_published: false })).then(function (res) {
        showToast(res.ok ? T('kit.unpublished_toast') : res.error, res.ok ? 'success' : 'danger');
        if (res.ok) {
            setStatus(false);
        }
    });
});
document.getElementById('btn-delete-kit').addEventListener('click', function () {
    confirmDialog(T('kit.delete_confirm'), { okText: T('btn.delete'), okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/kits/delete', withCoop({ id: kitPage.id })).then(function (res) {
            showToast(res.ok ? T('common.deleted') : res.error, res.ok ? 'success' : 'danger');
            if (res.ok) {
                window.location.href = pageUrl('vendor/school-kits.php');
            }
        });
    });
});
kitForm.addEventListener('submit', function (event) {
    event.preventDefault();
});

onCooperativeReady(function (coop) {
    if (kitPage.loadedCoopId !== null && kitPage.loadedCoopId !== coop.id) {
        window.location.href = pageUrl('vendor/school-kits.php');
        return;
    }
    kitPage.loadedCoopId = coop.id;
    loadKitPage();
});
