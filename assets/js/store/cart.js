// Troli: kumpulan ikut anak, ubah kuantiti, buang, kosongkan

function cartLine(item) {
    var row = createEl('li', 'list-group-item px-0');
    var top = createEl('div', 'd-flex gap-2');
    top.appendChild(productImage(item.image_url, 'store-thumb-sm'));
    var text = createEl('div', 'flex-grow-1 min-w-0');
    text.appendChild(createEl('div', 'fw-semibold', item.name + (item.variant_label ? ' — ' + item.variant_label : '')));
    var meta = createEl('div', 'small text-body-secondary d-flex flex-wrap gap-1 align-items-center');
    meta.appendChild(sourceBadge(item.source));
    meta.appendChild(document.createTextNode(formatRM(item.unit_price)));
    if (item.regular_price > item.unit_price) {
        meta.appendChild(createEl('del', '', formatRM(item.regular_price)));
    }
    text.appendChild(meta);
    top.appendChild(text);
    top.appendChild(createEl('div', 'fw-bold text-nowrap', formatRM(item.line_total)));
    row.appendChild(top);

    if (item.problem) {
        var messages = {
            unavailable: T('store.cart.problem.unavailable'),
            stock: T('store.cart.problem.stock', { n: item.available }),
            max: T('store.cart.problem.max', { n: item.max })
        };
        row.appendChild(createEl('div', 'alert alert-warning small py-1 px-2 mt-2 mb-0', messages[item.problem]));
    }

    var controls = createEl('div', 'd-flex justify-content-end align-items-center gap-2 mt-2');
    if (item.problem !== 'unavailable') {
        controls.appendChild(qtyStepper(item.qty, item.max || 99, debounce(function (n) {
            storePost('store/cart/update', { id: item.id, qty: n }).then(renderCartResponse);
        }, 400), T('store.kit.qty_for', { name: item.name })));
    }
    controls.appendChild(createButton('', 'btn-outline-danger btn-sm', 'bi-trash', function () {
        storePost('store/cart/remove', { id: item.id }).then(renderCartResponse);
    }, T('store.cart.remove', { name: item.name })));
    row.appendChild(controls);
    return row;
}

function renderCart(data) {
    var box = document.getElementById('cart-groups');
    var empty = data.groups.length === 0;
    document.getElementById('cart-summary-col').classList.toggle('d-none', empty);
    document.getElementById('cart-clear').classList.toggle('d-none', empty);
    setCartCount(data.item_count);
    if (empty) {
        emptyState(box, 'bi-cart3', T('store.cart.empty'), T('store.nav.kit'), storeUrl('kit'));
        box.querySelector('.text-center').appendChild(createLink(T('store.nav.catalog'), storeUrl('catalog'), 'btn btn-outline-brand ms-2'));
        return;
    }
    box.replaceChildren();
    data.groups.forEach(function (group) {
        var card = createEl('section', 'card border-0 shadow-sm mb-3');
        var body = createEl('div', 'card-body');
        body.appendChild(childGroupHeader(group));
        var list = createEl('ul', 'list-group list-group-flush');
        group.items.forEach(function (item) {
            list.appendChild(cartLine(item));
        });
        body.appendChild(list);
        var foot = createEl('div', 'd-flex justify-content-between small border-top pt-2 mt-1');
        foot.appendChild(createEl('span', 'text-body-secondary', T('store.cart.child_subtotal', { name: group.child_name })));
        foot.appendChild(createEl('span', 'fw-semibold', formatRM(group.subtotal)));
        body.appendChild(foot);
        card.appendChild(body);
        box.appendChild(card);
    });
    renderTotals(document.getElementById('cart-totals'), data);
    document.getElementById('cart-problem').classList.toggle('d-none', !data.has_problem);
    document.getElementById('cart-checkout').classList.toggle('disabled', data.has_problem);
}

function renderCartResponse(res) {
    if (!res.ok) {
        showToast(res.error, 'danger');
        return;
    }
    renderCart(res.data);
}

function clearCart() {
    confirmDialog(T('store.cart.clear_confirm'), { okClass: 'btn-danger', okText: T('store.cart.clear') }).then(function (yes) {
        if (yes) {
            storePost('store/cart/clear').then(renderCartResponse);
        }
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    loadingState(document.getElementById('cart-groups'));
    document.getElementById('cart-clear').addEventListener('click', clearCart);
    storeGet('store/cart/get').then(renderCartResponse);
});
