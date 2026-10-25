// Kit sekolah: semua item siap ditanda; ibu bapa nyahtanda yang tidak perlu / ubah kuantiti / pilih saiz.
// Jumlah dikemas kini serta-merta di pelayar (paparan sahaja) — pelayan mengira semula semasa masuk troli.

var kitData = null;
var kitChildren = [];
var kitChildId = queryParam('child') ? Number(queryParam('child')) : null;
// Pilihan setiap item: { checked, qty, variant_id }
var kitPicks = {};

function kitItemPrice(item) {
    var pick = kitPicks[item.id];
    if (item.choose_variant && pick.variant_id) {
        var variant = item.variants.filter(function (v) { return v.id === pick.variant_id; })[0];
        return variant ? variant.price : item.unit_price;
    }
    return item.unit_price;
}

function kitItemAvailable(item) {
    var pick = kitPicks[item.id];
    if (item.choose_variant) {
        var variant = item.variants.filter(function (v) { return v.id === pick.variant_id; })[0];
        return variant ? variant.available : null;
    }
    return item.available;
}

function refreshKitTotal() {
    var count = 0;
    var total = 0;
    kitData.items.forEach(function (item) {
        var pick = kitPicks[item.id];
        if (pick.checked) {
            count++;
            total += kitItemPrice(item) * pick.qty;
        }
    });
    document.getElementById('kit-selected').textContent = T('store.kit.selected', { n: count, total: kitData.items.length });
    document.getElementById('kit-total').textContent = formatRM(total);
    document.getElementById('kit-add').disabled = count === 0;
}

// Satu baris kit: tanda, nama, harga, kuantiti, saiz, amaran wajib
function kitItemRow(item) {
    var pick = kitPicks[item.id];
    var row = createEl('li', 'list-group-item px-0 store-kit-item');
    var top = createEl('div', 'd-flex gap-2 align-items-start');

    var check = createEl('input', 'form-check-input store-kit-check flex-shrink-0');
    check.type = 'checkbox';
    check.id = 'kit-item-' + item.id;
    check.checked = pick.checked;
    top.appendChild(check);

    var label = createEl('label', 'flex-grow-1 min-w-0');
    label.htmlFor = check.id;
    label.appendChild(createEl('span', 'd-block fw-semibold', item.name + (item.variant_label ? ' — ' + item.variant_label : '')));
    var meta = createEl('span', 'd-block small text-body-secondary');
    meta.textContent = formatRM(item.choose_variant ? kitItemPrice(item) : item.unit_price) + ' / ' + item.unit;
    label.appendChild(meta);
    var badges = createEl('span', 'd-flex flex-wrap gap-1 mt-1');
    if (item.is_mandatory) {
        badges.appendChild(createEl('span', 'badge text-bg-warning', T('kit.mandatory_badge')));
    }
    var stock = stockBadge(item.available);
    if (stock) {
        badges.appendChild(stock);
    }
    label.appendChild(badges);
    if (item.note) {
        label.appendChild(createEl('span', 'd-block small fst-italic mt-1', item.note));
    }
    top.appendChild(label);
    top.appendChild(createEl('div', 'fw-bold text-nowrap store-kit-line-total'));
    row.appendChild(top);

    var controls = createEl('div', 'd-flex flex-wrap gap-2 mt-2 ms-4 ps-2');
    if (item.choose_variant) {
        var select = createEl('select', 'form-select form-select-sm store-kit-variant');
        select.setAttribute('aria-label', T('store.kit.choose_size', { name: item.name }));
        select.appendChild(createEl('option', '', T('store.kit.choose_size_short')));
        select.firstChild.value = '';
        item.variants.forEach(function (variant) {
            var option = createEl('option', '', variant.label + ' · ' + (variant.available > 0 ? formatRM(variant.price) : T('store.stock.out')));
            option.value = variant.id;
            option.disabled = variant.available <= 0;
            select.appendChild(option);
        });
        select.value = pick.variant_id || '';
        select.addEventListener('change', function () {
            pick.variant_id = select.value ? Number(select.value) : null;
            select.classList.remove('is-invalid');
            meta.textContent = formatRM(kitItemPrice(item)) + ' / ' + item.unit;
            updateRow();
        });
        controls.appendChild(select);
        row.variantSelect = select;
    }
    var stepper = qtyStepper(pick.qty, item.max_per_order || 99, function (n) {
        pick.qty = n;
        updateRow();
    }, T('store.kit.qty_for', { name: item.name }));
    controls.appendChild(stepper);
    row.appendChild(controls);

    var warning = createEl('div', 'alert alert-warning small py-1 px-2 mt-2 mb-0 ms-4 d-none', T('store.kit.mandatory_warning'));
    warning.setAttribute('role', 'status');
    row.appendChild(warning);

    function updateRow() {
        row.classList.toggle('store-kit-unchecked', !pick.checked);
        controls.classList.toggle('d-none', !pick.checked);
        warning.classList.toggle('d-none', pick.checked || !item.is_mandatory);
        top.lastChild.textContent = pick.checked ? formatRM(kitItemPrice(item) * pick.qty) : '';
        refreshKitTotal();
    }
    check.addEventListener('change', function () {
        pick.checked = check.checked;
        updateRow();
    });
    row.refresh = function () {
        check.checked = pick.checked;
        updateRow();
    };
    updateRow();
    return row;
}

function renderKit() {
    var list = document.getElementById('kit-items');
    list.replaceChildren();
    kitPicks = {};
    kitData.items.forEach(function (item) {
        // Item habis stok tidak ditanda; jika kit sudah dalam troli → ikut troli
        kitPicks[item.id] = kitData.in_cart
            ? { checked: Boolean(item.in_cart), qty: item.in_cart ? item.in_cart.qty : item.qty, variant_id: item.in_cart ? item.in_cart.variant_id : null }
            : { checked: item.available !== 0, qty: item.qty, variant_id: null };
    });
    kitData.items.forEach(function (item) {
        list.appendChild(kitItemRow(item));
    });
    document.getElementById('kit-title').textContent = kitData.name || T('kit.title_for', { grade: gradeLabel(kitData.grade_level_code), year: kitData.school_year });
    var note = document.getElementById('kit-note');
    note.textContent = kitData.note || '';
    note.classList.toggle('d-none', !kitData.note);
    document.getElementById('kit-in-cart').classList.toggle('d-none', !kitData.in_cart);
    document.getElementById('kit-add-text').textContent = T(kitData.in_cart ? 'store.kit.update_cart' : 'store.kit.add_to_cart');
    document.getElementById('kit-card').classList.remove('d-none');
    document.getElementById('kit-bar').classList.remove('d-none');
    document.body.classList.add('store-has-sticky-bar');
    refreshKitTotal();
}

function hideKit() {
    document.getElementById('kit-card').classList.add('d-none');
    document.getElementById('kit-bar').classList.add('d-none');
    document.body.classList.remove('store-has-sticky-bar');
}

function loadKit() {
    var empty = document.getElementById('kit-empty');
    hideKit();
    loadingState(empty);
    storeGet('store/kit', { child_id: kitChildId || '' }).then(function (res) {
        if (!res.ok) {
            empty.replaceChildren(createEl('div', 'alert alert-danger', res.error));
            return;
        }
        kitChildren = res.data.children;
        renderKitChildren();
        if (kitChildren.length === 0) {
            emptyState(empty, 'bi-person-plus', T('store.kit.no_children', { school: Store.school.school_name }), T('store.children.add'), storeUrl('children', { add: 1 }));
            return;
        }
        if (!res.data.child) {
            if (kitChildren.length === 1) {
                kitChildId = kitChildren[0].id;
                loadKit();
                return;
            }
            empty.replaceChildren();
            return;
        }
        if (!res.data.kit) {
            emptyState(empty, 'bi-backpack', T('store.kit.none', { grade: gradeLabel(res.data.child.grade_level_code), year: res.data.child.school_year }),
                T('store.kit.browse_catalog'), storeUrl('catalog', { grade: res.data.child.grade_level_code }));
            return;
        }
        empty.replaceChildren();
        kitData = res.data.kit;
        document.getElementById('kit-child-line').textContent = T('store.kit.for_child', { name: res.data.child.full_name, class: childClassLine(res.data.child) });
        renderKit();
    });
}

function renderKitChildren() {
    var box = document.getElementById('kit-children');
    document.getElementById('kit-child-section').classList.toggle('d-none', kitChildren.length === 0);
    childRadios(box, kitChildren, 'kit_child', kitChildId, function (child) {
        kitChildId = child.id;
        history.replaceState(null, '', storeUrl('kit', { child: child.id }));
        loadKit();
    });
}

function addKitToCart() {
    var items = [];
    var missingSize = null;
    kitData.items.forEach(function (item) {
        var pick = kitPicks[item.id];
        if (!pick.checked) {
            return;
        }
        if (item.choose_variant && !pick.variant_id && !missingSize) {
            missingSize = item;
        }
        items.push({ kit_item_id: item.id, qty: pick.qty, variant_id: pick.variant_id });
    });
    if (missingSize) {
        showToast(T('store.kit.size_required', { name: missingSize.name }), 'danger');
        var select = document.querySelectorAll('.store-kit-variant');
        select.forEach(function (el) {
            if (!el.value && !el.closest('.d-none')) {
                el.classList.add('is-invalid');
            }
        });
        var first = document.querySelector('.store-kit-variant.is-invalid');
        if (first) {
            first.focus();
        }
        return;
    }
    var button = document.getElementById('kit-add');
    button.disabled = true;
    storePost('store/cart/add-kit', { child_id: kitChildId, kit_id: kitData.id, items: items }).then(function (res) {
        button.disabled = false;
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        setCartCount(res.data.cart_count);
        showToast(T('store.kit.added', { n: res.data.added, name: res.data.child_name }));
        storeGo('cart');
    });
}

onStoreReady(function () {
    if (!Store.customer) {
        document.getElementById('kit-child-section').classList.add('d-none');
        emptyState(document.getElementById('kit-empty'), 'bi-backpack', T('store.kit.login_text'), T('btn.login'), storeUrl('login', { next: 'kit' }));
        return;
    }
    document.getElementById('kit-check-all').addEventListener('click', function () {
        kitData.items.forEach(function (item) {
            kitPicks[item.id].checked = item.available !== 0;
        });
        document.querySelectorAll('.store-kit-item').forEach(function (row) {
            row.refresh();
        });
    });
    document.getElementById('kit-add').addEventListener('click', addKitToCart);
    loadKit();
});
