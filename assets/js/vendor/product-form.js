// Borang produk

var productForm = document.getElementById('product-form');
var productId = Number(queryParam('id')) || null;
var productState = { imageUrl: null, loadedCoopId: null };
var variantsBody = document.getElementById('variants-body');

function loadCategoryOptions() {
    return Api.get('vendor/categories/list', withCoop()).then(function (res) {
        var active = (res.ok ? res.data : []).filter(function (category) { return category.is_active; });
        fillSelect('product-category', active, 'id', function (category) { return category.name; }, true);
    });
}

// Darjah sasaran — checkbox; tiada ditanda = semua darjah
function loadGradeChecks() {
    return Api.get('vendor/grades/list', withCoop()).then(function (res) {
        var box = document.getElementById('grade-checks');
        box.replaceChildren();
        (res.ok ? res.data : []).forEach(function (code) {
            var col = createEl('div', 'col');
            var check = createEl('div', 'form-check');
            var input = createEl('input', 'form-check-input');
            input.type = 'checkbox';
            input.name = 'grade_levels';
            input.value = code;
            input.id = 'grade-' + code;
            input.setAttribute('data-multi', '');
            var label = createEl('label', 'form-check-label', T('grade.' + code));
            label.htmlFor = input.id;
            check.appendChild(input);
            check.appendChild(label);
            col.appendChild(check);
            box.appendChild(col);
        });
    });
}

// ---------- Varian (baris dinamik, dibaca sendiri — tiada atribut name) ----------

function variantInput(field, value, type, label) {
    var input = createEl('input', 'form-control form-control-sm' + (field === 'label' ? '' : ' font-monospace'));
    input.type = type || 'text';
    input.value = value === null || value === undefined ? '' : value;
    input.setAttribute('data-field', field);
    input.setAttribute('aria-label', label);
    return input;
}

function addVariantRow(variant) {
    variant = variant || { label: '', sku: '', barcode: '', price_delta: 0, is_active: true };
    var row = createEl('tr');
    row.setAttribute('data-id', variant.id || '');

    var labelInput = variantInput('label', variant.label, 'text', T('product.variant_label'));
    labelInput.required = true;
    labelInput.placeholder = T('product.variant_placeholder');
    [labelInput, variantInput('sku', variant.sku, 'text', T('product.sku')), variantInput('barcode', variant.barcode, 'text', T('product.barcode'))]
        .forEach(function (input) {
            var cell = createEl('td');
            cell.appendChild(input);
            row.appendChild(cell);
        });

    var deltaCell = createEl('td');
    var delta = variantInput('price_delta', senToInput(variant.price_delta), 'number', T('product.price_delta'));
    delta.step = '0.01';
    delta.classList.add('app-money-input');
    delta.addEventListener('input', updateVariantPrices);
    deltaCell.appendChild(delta);
    row.appendChild(deltaCell);

    row.appendChild(createEl('td', 'text-end text-nowrap js-final-price'));

    var activeCell = createEl('td');
    var active = createEl('input', 'form-check-input');
    active.type = 'checkbox';
    active.checked = variant.is_active !== false;
    active.setAttribute('data-field', 'is_active');
    active.setAttribute('aria-label', T('common.active'));
    activeCell.appendChild(active);
    row.appendChild(activeCell);

    var removeCell = createEl('td', 'text-end');
    removeCell.appendChild(createButton('', 'btn-outline-danger', 'bi-x-lg', function () {
        row.remove();
    }, T('btn.delete')));
    row.appendChild(removeCell);

    variantsBody.appendChild(row);
    updateVariantPrices();
}

// Harga akhir varian = harga produk + beza harga
function updateVariantPrices() {
    var base = parseRM(document.getElementById('product-price').value);
    Array.prototype.forEach.call(variantsBody.rows, function (row) {
        var delta = parseRM(row.querySelector('[data-field="price_delta"]').value);
        row.querySelector('.js-final-price').textContent = formatRM(base + delta);
    });
}

function readVariants() {
    return Array.prototype.map.call(variantsBody.rows, function (row) {
        return {
            id: Number(row.getAttribute('data-id')) || null,
            label: row.querySelector('[data-field="label"]').value.trim(),
            sku: row.querySelector('[data-field="sku"]').value.trim(),
            barcode: row.querySelector('[data-field="barcode"]').value.trim(),
            price_delta: parseRM(row.querySelector('[data-field="price_delta"]').value),
            is_active: row.querySelector('[data-field="is_active"]').checked
        };
    });
}

function toggleVariants() {
    var on = document.getElementById('product-has-variants').checked;
    document.getElementById('variants-section').classList.toggle('d-none', !on);
    document.getElementById('variants-help').classList.toggle('d-none', on);
    variantsBody.querySelectorAll('[data-field="label"]').forEach(function (input) {
        input.required = on;
    });
    if (on && variantsBody.rows.length === 0) {
        addVariantRow(null);
    }
}

function updateMarginHint() {
    var price = parseRM(document.getElementById('product-price').value);
    var cost = parseRM(document.getElementById('product-cost').value);
    var hint = (price > 0 && cost > 0) ? T('product.margin', { amount: formatRM(price - cost), percent: Math.round((price - cost) / price * 100) }) : '';
    document.getElementById('margin-hint').textContent = hint;
    updateVariantPrices();
}

// ---------- Muat & simpan ----------

function loadProduct() {
    variantsBody.replaceChildren();
    if (!productId) {
        fillForm(productForm, { is_active: true, unit: 'buah', grade_levels: [], has_variants: false });
        toggleVariants();
        return;
    }
    Api.get('vendor/products/get', withCoop({ id: productId })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            productForm.classList.add('d-none');
            return;
        }
        fillForm(productForm, res.data);
        res.data.variants.forEach(addVariantRow);
        productState.imageUrl = res.data.image_url;
        showImagePreview('image-preview', productState.imageUrl);
        document.getElementById('page-title').textContent = T('product.edit') + ': ' + res.data.name;
        toggleVariants();
        updateMarginHint();
        var hasTranslation = Object.keys(res.data.translations || {}).some(function (code) {
            return Boolean(res.data.translations[code].name);
        });
        if (hasTranslation) {
            bootstrap.Collapse.getOrCreateInstance(document.getElementById('translations-section'), { toggle: false }).show();
        }
    });
}

productForm.addEventListener('submit', function (event) {
    event.preventDefault();
    var hasVariants = document.getElementById('product-has-variants').checked;
    var noVariants = hasVariants && variantsBody.rows.length === 0;
    document.getElementById('variants-error').classList.toggle('d-none', !noVariants);
    if (noVariants) {
        return;
    }
    submitForm(productForm, 'vendor/products/save', function () {
        showToast(T('common.saved'), 'success');
        window.location.href = pageUrl('vendor/products.php');
    }, function (payload) {
        payload.id = productId;
        payload.category_id = Number(payload.category_id);
        payload.image_url = productState.imageUrl;
        payload.variants = hasVariants ? readVariants() : [];
        payload.cost_price = payload.cost_price || 0;
        // Terjemahan pilihan: bahasa yang kosong tidak dihantar (jatuh balik ke nama asal)
        Object.keys(payload.translations || {}).forEach(function (code) {
            var item = payload.translations[code];
            if (!item.name && !item.description) {
                delete payload.translations[code];
            }
        });
        return withCoop(payload);
    });
});

document.getElementById('product-has-variants').addEventListener('change', toggleVariants);
document.getElementById('btn-add-variant').addEventListener('click', function () {
    addVariantRow(null);
});
document.getElementById('product-price').addEventListener('input', updateMarginHint);
document.getElementById('product-cost').addEventListener('input', updateMarginHint);

bindImageInput('image-file', 'image-preview', function (dataUrl) {
    productState.imageUrl = dataUrl;
});
document.getElementById('image-remove').addEventListener('click', function () {
    productState.imageUrl = null;
    document.getElementById('image-file').value = '';
    showImagePreview('image-preview', null);
});

// Produk milik satu koperasi: tukar koperasi semasa menyunting → kembali ke senarai
onCooperativeReady(function (coop) {
    if (productState.loadedCoopId !== null && productState.loadedCoopId !== coop.id) {
        window.location.href = pageUrl('vendor/products.php');
        return;
    }
    productState.loadedCoopId = coop.id;
    Promise.all([loadCategoryOptions(), loadGradeChecks()]).then(loadProduct);
});
