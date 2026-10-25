// POS kaunter (PELAN 5.2 modul 9). Aliran: PIN juruwang → buka syif → jual → resit → tutup syif.
// Medan imbas sentiasa difokus: pengimbas barcode USB = papan kekunci yang menaip kod + Enter.
// Logik troli dari pos-cart.js (KedaiPOS). Harga & diskaun di sini untuk paparan — pelayan mengira semula semasa checkout.

var pos = {
    session: null,      // vendor/pos/session: { cooperative, cashier, shift, online_available, gateway_name }
    settings: null,     // vendor/settings/get: resit & cukai
    promotions: [],     // promosi berjalan (kupon & automatik)
    catalog: { categories: [], products: [] },
    category: '',
    cart: [],           // [{ line_id, product_id, variant_id, name, variant_label, sku, unit_price, qty, stock, max_per_order }]
    coupon: null,
    lastLineId: null,
    variantProduct: null,
    pending: null,      // jualan online menunggu bayaran { receipt, payment }
    pollTimer: null,
    receipt: null,
    receiptAfterHide: null,
    shiftClosed: null
};

var scanInput = document.getElementById('pos-scan');

function byId(id) {
    return document.getElementById(id);
}

function isModalOpen() {
    return Boolean(document.querySelector('.modal.show, .offcanvas.show, .offcanvas.showing'));
}

function isTextField(node) {
    return Boolean(node) && ['INPUT', 'SELECT', 'TEXTAREA'].indexOf(node.tagName) !== -1;
}

function lineLabel(name, variantLabel) {
    return variantLabel ? name + ' — ' + variantLabel : name;
}

// ---------- Langkah: log masuk → syif → jualan ----------

function showStep(step) {
    ['loading', 'login', 'shift', 'sale'].forEach(function (name) {
        byId('pos-' + name).classList.toggle('d-none', name !== step);
    });
    var cashier = pos.session && pos.session.cashier;
    byId('pos-cashier-badge').classList.toggle('d-none', !cashier);
    byId('pos-cashier-name').textContent = cashier ? cashier.full_name : '';
    byId('pos-menu').classList.toggle('d-none', step !== 'sale');
}

function loadSession() {
    stopPolling();
    showStep('loading');
    Api.get('vendor/pos/session', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        pos.session = res.data;
        if (!res.data.cashier) {
            showLogin();
        } else if (!res.data.shift) {
            showShiftOpen();
        } else {
            startSale();
        }
    });
}

// ---------- Log masuk PIN ----------

// Kakitangan aktif dengan PIN & akses koperasi ini (U3c: vendor/staff/list). Pelayan menyemak semula semasa log masuk
function showLogin() {
    showStep('login');
    var form = byId('pin-form');
    resetForm(form);
    byId('pos-login-coop').textContent = pos.session.cooperative.school_name;
    Api.get('vendor/staff/list', { per_page: 100, status: 'active' }).then(function (res) {
        var coopId = currentCooperativeId();
        var cashiers = (res.ok ? res.data.items : []).filter(function (user) {
            return user.pin_set && (user.role === 'vendor_admin' || user.cooperative_ids.indexOf(coopId) !== -1);
        });
        renderCashiers(cashiers);
    });
}

function renderCashiers(cashiers) {
    var box = byId('pos-cashiers');
    box.replaceChildren();
    byId('pos-no-cashier').classList.toggle('d-none', cashiers.length > 0);
    cashiers.forEach(function (user, index) {
        var col = createEl('div', 'col-12 col-sm-6');
        var input = createEl('input', 'btn-check');
        input.type = 'radio';
        input.name = 'user_id';
        input.id = 'cashier-' + user.id;
        input.value = user.id;
        input.required = true;
        input.checked = cashiers.length === 1 || index === 0;
        var label = createEl('label', 'btn btn-outline-secondary w-100 text-start app-pos-choice d-flex align-items-center gap-2');
        label.htmlFor = input.id;
        label.appendChild(createIcon('bi-person-circle fs-4'));
        label.appendChild(createStack(user.full_name, T('role.' + user.role)));
        input.addEventListener('change', function () {
            byId('pos-pin').focus();
        });
        col.appendChild(input);
        col.appendChild(label);
        box.appendChild(col);
    });
    byId('pos-pin').focus();
}

function pressKey(key) {
    var pin = byId('pos-pin');
    if (key === 'clear') {
        pin.value = '';
    } else if (key === 'back') {
        pin.value = pin.value.slice(0, -1);
    } else if (pin.value.length < 6) {
        pin.value += key;
    }
    pin.classList.remove('is-invalid');
    pin.focus();
}

function submitPin(event) {
    event.preventDefault();
    var form = byId('pin-form');
    var chosen = form.querySelector('[name="user_id"]:checked');
    if (!chosen) {
        showToast(T('pos.choose_cashier'), 'warning');
        return;
    }
    if (!validateForm(form)) {
        return;
    }
    Api.post('vendor/pos/pin-login', withCoop({ user_id: Number(chosen.value), pin: byId('pos-pin').value })).then(function (res) {
        if (!res.ok) {
            byId('pos-pin').value = '';
            showFormErrors(form, res.errors);
            showToast(res.error, 'danger');
            return;
        }
        pos.session.cashier = res.data.cashier;
        pos.session.shift = res.data.shift;
        showToast(T('pos.welcome', { name: res.data.cashier.full_name }), 'success');
        if (res.data.shift) {
            startSale();
        } else {
            showShiftOpen();
        }
    });
}

function lockCounter() {
    Api.post('vendor/pos/lock', withCoop()).then(function () {
        clearCart();
        loadSession();
    });
}

// ---------- Buka syif ----------

function showShiftOpen() {
    showStep('shift');
    resetForm(byId('shift-open-form'));
    byId('pos-shift-who').textContent = T('pos.shift_who', { name: pos.session.cashier.full_name, coop: pos.session.cooperative.school_name });
    byId('opening-cash').focus();
}

function openShift(data) {
    pos.session.shift = data;
    showToast(T('pos.shift_opened', { amount: formatRM(data.opening_cash) }), 'success');
    startSale();
}

// ---------- Jualan: katalog ----------

function startSale() {
    showStep('sale');
    byId('pos-online-off').classList.toggle('d-none', pos.session.online_available);
    Promise.all([
        Api.get('vendor/settings/get', withCoop()),
        Api.get('vendor/promotions/list', withCoop({ per_page: 100, state: 'active' })),
        loadCatalog()
    ]).then(function (results) {
        pos.settings = results[0].ok ? results[0].data : { tax_rate: 0, receipt_paper: '80', receipt_header: '', receipt_footer: '', receipt_show_logo: false };
        pos.promotions = results[1].ok ? results[1].data.items : [];
        renderCart();
        focusScan();
    });
}

function loadCatalog() {
    return Api.get('vendor/pos/catalog', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        pos.catalog = res.data;
        refreshCartStock();
        renderCategories();
        renderProducts();
    });
}

function renderCategories() {
    var box = byId('pos-categories');
    box.replaceChildren();
    [{ id: '', name: T('common.all') }].concat(pos.catalog.categories).forEach(function (category) {
        var active = String(pos.category) === String(category.id);
        var button = createButton(category.name, 'app-pos-category ' + (active ? 'btn-brand' : 'btn-outline-secondary'), null, function () {
            pos.category = category.id;
            renderCategories();
            renderProducts();
        });
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        box.appendChild(button);
    });
}

function searchText() {
    return scanInput.value.trim().replace(/^\d{1,3}\s*[*xX]\s*/, '').toLowerCase();
}

function productMatches(product, q) {
    if (!q) {
        return true;
    }
    return [product.name, product.display_name, product.sku, product.barcode].concat(product.variants.map(function (variant) {
        return variant.sku + ' ' + variant.barcode;
    })).some(function (text) {
        return String(text || '').toLowerCase().indexOf(q) !== -1;
    });
}

function visibleProducts() {
    var q = searchText();
    return pos.catalog.products.filter(function (product) {
        // Carian merentas semua kategori; tanpa carian ikut kategori dipilih
        return q ? productMatches(product, q) : (!pos.category || product.category_id === Number(pos.category));
    });
}

function minPrice(product) {
    if (!product.has_variants || product.variants.length === 0) {
        return product.price;
    }
    return Math.min.apply(null, product.variants.map(function (variant) {
        return posUnitPrice(product, variant);
    }));
}

function renderProducts() {
    var box = byId('pos-products');
    var rows = visibleProducts();
    box.replaceChildren();
    byId('pos-no-products').classList.toggle('d-none', rows.length > 0);
    rows.forEach(function (product) {
        var col = createEl('div', 'col-6 col-md-4 col-xl-3');
        var button = createEl('button', 'app-pos-product');
        button.type = 'button';
        button.disabled = product.stock_available <= 0;
        button.appendChild(createEl('span', 'app-pos-product-name', product.display_name));
        var meta = createEl('span', 'small text-body-secondary', product.sku);
        button.appendChild(meta);
        var bottom = createEl('span', 'd-flex justify-content-between align-items-end gap-1 w-100');
        bottom.appendChild(createEl('span', 'app-pos-product-price',
            product.has_variants ? T('pos.from_price', { amount: formatRM(minPrice(product)) }) : formatRM(product.price)));
        bottom.appendChild(stockBadge(product.stock_available, product.has_variants));
        button.appendChild(bottom);
        button.addEventListener('click', function () {
            addProduct(product, 1);
        });
        col.appendChild(button);
        box.appendChild(col);
    });
}

function stockBadge(available, hasVariants) {
    if (available <= 0) {
        return createEl('span', 'badge text-bg-secondary', T('pos.out_of_stock'));
    }
    var text = hasVariants ? T('pos.variant_count_stock', { n: available }) : T('pos.stock_left', { n: available });
    return createEl('span', 'badge ' + (available <= 5 ? 'text-bg-warning' : 'text-bg-light border'), text);
}

// ---------- Imbas / cari ----------

function findByCode(code) {
    var wanted = code.toLowerCase();
    var found = null;
    pos.catalog.products.some(function (product) {
        var variant = product.variants.filter(function (row) {
            return String(row.barcode).toLowerCase() === wanted || String(row.sku).toLowerCase() === wanted;
        })[0];
        if (variant) {
            found = { product: product, variant: variant };
            return true;
        }
        if (String(product.barcode).toLowerCase() === wanted || String(product.sku).toLowerCase() === wanted) {
            found = { product: product, variant: null };
            return true;
        }
        return false;
    });
    return found;
}

// "5*9552000001" atau "5x pemadam" = tambah 5 unit
function handleScan(event) {
    event.preventDefault();
    var raw = scanInput.value.trim();
    if (!raw) {
        return;
    }
    var qty = 1;
    var match = raw.match(/^(\d{1,3})\s*[*xX]\s*(.+)$/);
    if (match) {
        qty = Math.max(1, parseInt(match[1], 10));
        raw = match[2].trim();
    }
    var exact = findByCode(raw);
    if (exact) {
        clearScan();
        if (exact.variant) {
            addToCart(exact.product, exact.variant, qty);
        } else {
            addProduct(exact.product, qty);
        }
        return;
    }
    var results = visibleProducts();
    if (results.length === 1) {
        clearScan();
        addProduct(results[0], qty);
        return;
    }
    showToast(results.length ? T('pos.many_found', { n: results.length }) : T('pos.not_found', { code: raw }), 'warning');
    scanInput.select();
}

function clearScan() {
    scanInput.value = '';
    renderProducts();
}

function focusScan() {
    if (!byId('pos-sale').classList.contains('d-none') && !isModalOpen()) {
        scanInput.focus({ preventScroll: true });
    }
}

// ---------- Varian ----------

function addProduct(product, qty) {
    if (product.has_variants) {
        openVariant(product, qty);
        return;
    }
    addToCart(product, null, qty);
}

function openVariant(product, qty) {
    pos.variantProduct = product;
    byId('variant-title').textContent = product.display_name;
    byId('variant-qty').value = qty || 1;
    var box = byId('variant-options');
    box.replaceChildren();
    var first = null;
    product.variants.forEach(function (variant) {
        var col = createEl('div', 'col-6 col-sm-4');
        var input = createEl('input', 'btn-check');
        input.type = 'radio';
        input.name = 'variant_id';
        input.id = 'variant-' + variant.id;
        input.value = variant.id;
        input.disabled = variant.stock_available <= 0;
        if (!input.disabled && !first) {
            first = input;
            input.checked = true;
        }
        var label = createEl('label', 'btn btn-outline-secondary w-100 app-pos-choice');
        label.htmlFor = input.id;
        label.appendChild(createEl('div', 'fw-semibold', variant.label));
        label.appendChild(createEl('div', 'small', formatRM(posUnitPrice(product, variant))));
        label.appendChild(createEl('div', 'small text-body-secondary',
            variant.stock_available > 0 ? T('pos.stock_left', { n: variant.stock_available }) : T('pos.out_of_stock')));
        input.addEventListener('change', updateVariantButton);
        col.appendChild(input);
        col.appendChild(label);
        box.appendChild(col);
    });
    updateVariantButton();
    showModal('variant-modal');
}

function selectedVariant() {
    var checked = document.querySelector('#variant-options [name="variant_id"]:checked');
    if (!checked || !pos.variantProduct) {
        return null;
    }
    return pos.variantProduct.variants.filter(function (variant) {
        return variant.id === Number(checked.value);
    })[0] || null;
}

function updateVariantButton() {
    var variant = selectedVariant();
    var qty = Math.max(1, parseInt(byId('variant-qty').value, 10) || 1);
    var button = byId('variant-add');
    button.disabled = !variant;
    button.textContent = variant ? T('pos.add_amount', { amount: formatRM(posUnitPrice(pos.variantProduct, variant) * qty) }) : T('pos.choose_variant');
}

function changeVariantQty(delta) {
    var input = byId('variant-qty');
    input.value = Math.max(1, (parseInt(input.value, 10) || 1) + delta);
    updateVariantButton();
}

// ---------- Troli (KedaiPOS addToCart / changeQty / removeFromCart) ----------

function cartLine(lineId) {
    return pos.cart.filter(function (line) {
        return line.line_id === lineId;
    })[0] || null;
}

// Had beli dikira setiap produk (semua varian)
function productQtyInCart(productId, exceptLineId) {
    return pos.cart.filter(function (line) {
        return line.product_id === productId && line.line_id !== exceptLineId;
    }).reduce(function (sum, line) {
        return sum + line.qty;
    }, 0);
}

function canSetQty(line, qty) {
    if (qty > line.stock) {
        showToast(T('pos.error.stock', { name: lineLabel(line.name, line.variant_label), n: line.stock }), 'warning');
        return false;
    }
    if (line.max_per_order && productQtyInCart(line.product_id, line.line_id) + qty > line.max_per_order) {
        showToast(T('pos.error.max_per_order', { name: line.name, n: line.max_per_order }), 'warning');
        return false;
    }
    return true;
}

function addToCart(product, variant, qty) {
    var lineId = posLineId(product.id, variant ? variant.id : 0);
    var line = cartLine(lineId) || {
        line_id: lineId, product_id: product.id, variant_id: variant ? variant.id : 0, name: product.display_name,
        variant_label: variant ? variant.label : null, sku: variant ? variant.sku : product.sku,
        unit_price: posUnitPrice(product, variant), qty: 0,
        stock: variant ? variant.stock_available : product.stock_available, max_per_order: product.max_per_order
    };
    if (!canSetQty(line, line.qty + qty)) {
        return;
    }
    if (line.qty === 0) {
        pos.cart.push(line);
    }
    line.qty += qty;
    pos.lastLineId = lineId;
    renderCart();
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        showToast(T('pos.added', { name: lineLabel(line.name, line.variant_label), n: line.qty }), 'success');
    }
}

function setLineQty(lineId, qty) {
    var line = cartLine(lineId);
    if (!line) {
        return;
    }
    if (qty <= 0) {
        removeLine(lineId);
        return;
    }
    if (canSetQty(line, qty)) {
        line.qty = qty;
    }
    pos.lastLineId = lineId;
    renderCart();
}

function removeLine(lineId) {
    pos.cart = pos.cart.filter(function (line) {
        return line.line_id !== lineId;
    });
    renderCart();
}

function clearCart() {
    pos.cart = [];
    pos.coupon = null;
    pos.lastLineId = null;
    byId('pos-coupon').value = '';
    byId('pos-manual-discount').value = '';
    byId('pos-customer').value = '';
    byId('pos-coupon').classList.remove('is-invalid');
    renderCart();
}

// Selepas katalog dimuat semula: kemas kini baki stok setiap baris troli
function refreshCartStock() {
    pos.cart.forEach(function (line) {
        var product = pos.catalog.products.filter(function (row) { return row.id === line.product_id; })[0];
        var variant = product && line.variant_id ? product.variants.filter(function (row) { return row.id === line.variant_id; })[0] : null;
        line.stock = product ? (variant ? variant.stock_available : product.stock_available) : 0;
    });
}

// getTotals: promosi kupon (jika diguna) atau diskaun automatik terbaik + diskaun manual + cukai koperasi
function currentTotals() {
    var subtotal = pos.cart.reduce(function (sum, line) {
        return sum + line.unit_price * line.qty;
    }, 0);
    var promotion = pos.coupon || posBestAutomatic(pos.promotions, subtotal);
    var totals = posTotals(pos.cart, {
        promotion: promotion,
        manualDiscount: parseRM(byId('pos-manual-discount').value),
        taxRate: pos.settings ? pos.settings.tax_rate : 0
    });
    totals.promotion = promotion;
    return totals;
}

function renderCart() {
    var box = byId('pos-lines');
    box.querySelectorAll('.app-pos-line').forEach(function (row) {
        row.remove();
    });
    byId('pos-cart-empty').classList.toggle('d-none', pos.cart.length > 0);
    pos.cart.forEach(function (line) {
        box.appendChild(cartRow(line));
    });
    var fresh = box.querySelector('.is-new');
    if (fresh) {
        fresh.scrollIntoView({ block: 'nearest' });
    }

    var totals = currentTotals();
    var count = pos.cart.reduce(function (sum, line) { return sum + line.qty; }, 0);
    byId('pos-cart-count').textContent = count;
    byId('pos-cartbar-count').textContent = count;
    byId('pos-cartbar-total').textContent = formatRM(totals.total);
    byId('pos-subtotal').textContent = formatRM(totals.subtotal);
    byId('pos-discount').textContent = totals.discount ? '-' + formatRM(totals.discount) : formatRM(0);
    var taxRate = pos.settings ? pos.settings.tax_rate : 0;
    byId('pos-tax-label').textContent = T('pos.tax_rate', { rate: taxRate });
    byId('pos-tax').textContent = formatRM(totals.tax);
    byId('pos-tax-label').classList.toggle('d-none', !taxRate);
    byId('pos-tax').classList.toggle('d-none', !taxRate);
    byId('pos-total').textContent = formatRM(totals.total);
    renderPromoLine(totals);

    var empty = pos.cart.length === 0;
    byId('btn-pay-cash').disabled = empty;
    byId('btn-pay-online').disabled = empty || !(pos.session && pos.session.online_available);
    byId('btn-clear-cart').disabled = empty;
    byId('btn-coupon').textContent = pos.coupon ? T('btn.delete') : T('pos.apply');
}

function cartRow(line) {
    var row = createEl('div', 'app-pos-line d-flex align-items-center gap-2' + (line.line_id === pos.lastLineId ? ' is-new' : ''));
    var text = createEl('div', 'flex-grow-1 min-w-0');
    text.appendChild(createEl('div', 'fw-semibold small text-truncate', line.name));
    if (line.variant_label) {
        text.appendChild(createEl('div', 'small text-body-secondary', line.variant_label));
    }
    text.appendChild(createEl('div', 'small text-body-secondary', formatRM(line.unit_price) + ' × ' + line.qty + ' = ' + formatRM(line.unit_price * line.qty)));
    row.appendChild(text);

    var group = createEl('div', 'input-group input-group-sm flex-nowrap w-auto');
    group.appendChild(createButton('', 'btn-outline-secondary', 'bi-dash-lg', function () {
        setLineQty(line.line_id, line.qty - 1);
    }, T('pos.qty_minus') + ': ' + line.name));
    var qty = createEl('input', 'form-control app-pos-qty');
    qty.type = 'number';
    qty.min = '0';
    qty.value = line.qty;
    qty.setAttribute('aria-label', T('field.qty') + ': ' + lineLabel(line.name, line.variant_label));
    qty.addEventListener('change', function () {
        setLineQty(line.line_id, parseInt(qty.value, 10) || 0);
    });
    group.appendChild(qty);
    group.appendChild(createButton('', 'btn-outline-secondary', 'bi-plus-lg', function () {
        setLineQty(line.line_id, line.qty + 1);
    }, T('pos.qty_plus') + ': ' + line.name));
    row.appendChild(group);
    row.appendChild(createButton('', 'btn-link text-danger px-1', 'bi-x-lg', function () {
        removeLine(line.line_id);
    }, T('btn.delete') + ': ' + line.name));
    return row;
}

function renderPromoLine(totals) {
    var box = byId('pos-promo-line');
    var promo = totals.promotion;
    var text = '';
    var className = 'small mb-2 text-success';
    if (promo && totals.promotion_discount > 0) {
        text = T('pos.promo_applied', { name: promo.code || promo.name, amount: formatRM(totals.promotion_discount) });
    } else if (pos.coupon) {
        text = T('pos.coupon_min_spend', { amount: formatRM(pos.coupon.min_spend) });
        className = 'small mb-2 text-warning-emphasis';
    }
    box.className = className + (text ? '' : ' d-none');
    box.textContent = text;
}

// ---------- Kupon ----------

function applyCoupon() {
    var input = byId('pos-coupon');
    if (pos.coupon) {
        pos.coupon = null;
        input.value = '';
        renderCart();
        return;
    }
    var code = input.value.trim().toUpperCase();
    if (!code) {
        input.focus();
        return;
    }
    var promo = pos.promotions.filter(function (row) {
        return row.code === code;
    })[0];
    input.classList.toggle('is-invalid', !promo);
    if (!promo) {
        byId('pos-coupon-error').textContent = T('pos.coupon_invalid');
        input.select();
        return;
    }
    input.value = code;
    pos.coupon = promo;
    renderCart();
    focusScan();
}

// ---------- Bayar tunai ----------

// Kupon yang belanja minimumnya belum dicapai akan ditolak pelayan — beritahu sebelum bayar
function couponBlocks() {
    if (pos.coupon && currentTotals().promotion_discount === 0) {
        showToast(T('pos.coupon_min_spend', { amount: formatRM(pos.coupon.min_spend) }), 'warning');
        return true;
    }
    return false;
}

function openCash() {
    if (pos.cart.length === 0 || couponBlocks()) {
        return;
    }
    var total = currentTotals().total;
    var form = byId('cash-form');
    resetForm(form);
    byId('cash-due').textContent = formatRM(total);
    var quick = byId('cash-quick');
    quick.replaceChildren();
    posSuggestCash(total).forEach(function (amount) {
        quick.appendChild(createButton(formatRM(amount), 'btn-lg btn-outline-secondary flex-grow-1', null, function () {
            byId('cash-received').value = senToInput(amount);
            updateChange();
            byId('cash-confirm').focus();
        }));
    });
    updateChange();
    showModal('cash-modal');
}

function updateChange() {
    var input = byId('cash-received');
    var box = byId('cash-change');
    var total = currentTotals().total;
    if (input.value === '') {
        box.textContent = '';
        return;
    }
    var change = parseRM(input.value) - total;
    input.classList.remove('is-invalid');
    box.textContent = change >= 0 ? T('handover.change', { amount: formatRM(change) }) : T('handover.cash_not_enough');
    box.className = 'fs-4 fw-semibold mt-3 text-center ' + (change >= 0 ? 'text-success' : 'text-danger');
}

function submitCash(event) {
    event.preventDefault();
    var input = byId('cash-received');
    var received = input.value === '' ? currentTotals().total : parseRM(input.value);
    if (received < currentTotals().total) {
        input.classList.add('is-invalid');
        input.select();
        return;
    }
    checkout('cash', received).then(function (data) {
        if (data) {
            showReceiptAfter('cash-modal', data.receipt, true);
        }
    });
}

// ---------- Bayar online (QR gateway) ----------

function openOnline() {
    if (pos.cart.length === 0 || !pos.session.online_available || couponBlocks()) {
        return;
    }
    checkout('online', 0).then(function (data) {
        if (!data) {
            return;
        }
        pos.pending = data;
        byId('online-due').textContent = formatRM(data.receipt.total);
        byId('online-gateway').textContent = data.payment.gateway_name || '';
        byId('online-ref').textContent = T('pos.reference', { ref: data.payment.reference });
        byId('online-qr').src = qrDataUrl(data.payment.qr_payload);
        showModal('online-modal');
        startPolling(data.receipt.order_id);
    });
}

function qrDataUrl(text) {
    var qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr.createDataURL(6, 2);
}

// Semak status setiap 2.5 saat (Peringkat 2: gateway → webhook → payments.status = paid)
function startPolling(orderId) {
    stopPolling();
    pos.pollTimer = setInterval(function () {
        Api.get('vendor/pos/payment-status', withCoop({ order_id: orderId })).then(function (res) {
            if (!res.ok || res.data.status === 'pending') {
                return;
            }
            stopPolling();
            if (res.data.status === 'paid') {
                showToast(T('pos.payment_received'), 'success');
                saleCompleted();
                showReceiptAfter('online-modal', res.data.receipt, true);
            }
        });
    }, 2500);
}

function stopPolling() {
    if (pos.pollTimer) {
        clearInterval(pos.pollTimer);
        pos.pollTimer = null;
    }
}

function cancelOnline() {
    if (!pos.pending) {
        hideModal('online-modal');
        return;
    }
    stopPolling();
    Api.post('vendor/pos/cancel-payment', withCoop({ order_id: pos.pending.receipt.order_id })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            startPolling(pos.pending.receipt.order_id);
            return;
        }
        pos.pending = null;
        hideModal('online-modal');
        showToast(T('pos.payment_cancelled'), 'warning');
        loadCatalog();
    });
}

// ---------- Checkout ----------

function checkout(method, received) {
    var payload = withCoop({
        shift_id: pos.session.shift.id,
        items: pos.cart.map(function (line) {
            return { product_id: line.product_id, variant_id: line.variant_id, qty: line.qty };
        }),
        coupon_code: pos.coupon ? pos.coupon.code : '',
        manual_discount: parseRM(byId('pos-manual-discount').value),
        customer_name: byId('pos-customer').value.trim(),
        payment_method: method,
        amount_received: received
    });
    var buttons = [byId('btn-pay-cash'), byId('btn-pay-online'), byId('cash-confirm')];
    buttons.forEach(function (button) { button.disabled = true; });
    return Api.post('vendor/pos/checkout', payload).then(function (res) {
        buttons.forEach(function (button) { button.disabled = false; });
        renderCart();
        if (!res.ok) {
            showToast(res.error, 'danger');
            if (res.errors.coupon_code || res.errors.manual_discount) {
                bootstrap.Collapse.getOrCreateInstance(byId('pos-extras'), { toggle: false }).show();
            }
            if (res.errors.coupon_code) {
                byId('pos-coupon').classList.add('is-invalid');
                byId('pos-coupon-error').textContent = res.errors.coupon_code;
            }
            if (res.errors.amount_received) {
                byId('cash-received').classList.add('is-invalid');
            }
            loadCatalog();
            return null;
        }
        if (method === 'cash') {
            saleCompleted();
        }
        return res.data;
    });
}

// Jualan selesai: kosongkan troli, muat semula stok
function saleCompleted() {
    pos.pending = null;
    clearCart();
    loadCatalog();
}

// ---------- Resit ----------

// Tunggu modal semasa tertutup sepenuhnya sebelum buka resit (elak backdrop bertindih — KedaiPOS)
function showReceiptAfter(modalId, receipt, isNew) {
    pos.receiptAfterHide = { receipt: receipt, isNew: isNew };
    hideModal(modalId);
}

function showReceipt(receipt, isNew) {
    pos.receipt = receipt;
    byId('receipt-heading').textContent = isNew ? T('pos.sale_done') : T('pos.receipt_copy');
    var change = byId('receipt-change');
    change.textContent = isNew && receipt.payment_method === 'cash' ? T('handover.change', { amount: formatRM(receipt.change) }) : '';
    change.classList.toggle('d-none', !change.textContent);
    renderReceipt(byId('receipt-paper'), receipt);
    renderReceipt(byId('receipt-print'), receipt);
    showModal('receipt-modal');
}

function paperClass() {
    return 'app-receipt app-receipt-' + (pos.settings && pos.settings.receipt_paper === '58' ? '58' : '80');
}

function receiptRow(paper, left, right, className) {
    var row = createEl('div', 'app-receipt-row' + (className ? ' ' + className : ''));
    row.appendChild(createEl('span', '', left));
    row.appendChild(createEl('span', '', right));
    paper.appendChild(row);
}

function receiptRule(paper) {
    paper.appendChild(createEl('div', 'app-receipt-rule'));
}

function receiptHeader(paper, info) {
    var settings = pos.settings || {};
    if (settings.receipt_show_logo && info.logo_url) {
        var logo = createEl('img', 'app-receipt-logo');
        logo.src = info.logo_url;
        logo.alt = '';
        paper.appendChild(logo);
    }
    var head = createEl('div', 'app-receipt-center');
    head.appendChild(createEl('div', 'app-receipt-title', settings.receipt_header || info.school_name));
    if (settings.receipt_header && settings.receipt_header !== info.school_name) {
        head.appendChild(createEl('div', '', info.school_name));
    }
    if (info.address) {
        head.appendChild(createEl('div', 'app-receipt-muted', info.address));
    }
    if (info.phone) {
        head.appendChild(createEl('div', 'app-receipt-muted', T('pos.receipt_phone', { phone: info.phone })));
    }
    paper.appendChild(head);
    receiptRule(paper);
}

function renderReceipt(container, receipt) {
    var paper = createEl('div', paperClass());
    receiptHeader(paper, receipt);

    receiptRow(paper, T('pos.receipt_no'), receipt.order_no);
    receiptRow(paper, T('pos.date'), formatDate(receipt.paid_at || receipt.created_at, true));
    receiptRow(paper, T('pos.cashier'), receipt.cashier_name);
    if (receipt.customer_name) {
        receiptRow(paper, T('pos.customer'), receipt.customer_name);
    }
    receiptRule(paper);

    receipt.items.forEach(function (item) {
        var block = createEl('div', 'app-receipt-item');
        block.appendChild(createEl('div', '', lineLabel(item.product_name, item.variant_label)));
        receiptRow(block, item.qty + ' × ' + formatRM(item.unit_price), formatRM(item.line_total), 'app-receipt-sub');
        paper.appendChild(block);
    });
    receiptRule(paper);

    receiptRow(paper, T('field.subtotal'), formatRM(receipt.subtotal));
    if (receipt.promotion_discount) {
        receiptRow(paper, T('pos.receipt_promo', { name: receipt.promotion_code || receipt.promotion_name }), '-' + formatRM(receipt.promotion_discount));
    }
    if (receipt.manual_discount) {
        receiptRow(paper, T('pos.manual_discount'), '-' + formatRM(receipt.manual_discount));
    }
    if (receipt.tax_rate) {
        receiptRow(paper, T('pos.tax_rate', { rate: receipt.tax_rate }), formatRM(receipt.tax));
    }
    receiptRow(paper, T('field.total').toUpperCase(), formatRM(receipt.total), 'app-receipt-total');
    receiptRule(paper);

    if (receipt.payment_method === 'cash') {
        receiptRow(paper, T('payment.method.cash'), formatRM(receipt.amount_received));
        receiptRow(paper, T('pos.change'), formatRM(receipt.change));
    } else {
        receiptRow(paper, T('payment.method.online'), receipt.gateway_name || '');
        receiptRow(paper, T('pos.reference_short'), receipt.gateway_ref || '');
    }
    if (receipt.payment_status !== 'paid') {
        paper.appendChild(createEl('div', 'app-receipt-center app-receipt-title mt-1',
            receipt.status === 'cancelled' ? T('order.status.cancelled') : T('pos.receipt_unpaid')));
    }
    if (receipt.refunded_amount) {
        receiptRow(paper, T('pos.receipt_refunded'), '-' + formatRM(receipt.refunded_amount));
    }

    var settings = pos.settings || {};
    if (settings.receipt_footer) {
        receiptRule(paper);
        paper.appendChild(createEl('div', 'app-receipt-center', settings.receipt_footer));
    }
    container.replaceChildren(paper);
}

function printReceipt() {
    window.print();
    byId('receipt-new').focus();
}

// ---------- Jualan syif ini ----------

function summaryRows(box, rows) {
    box.replaceChildren();
    rows.forEach(function (row) {
        box.appendChild(createEl('dt', 'col-7 fw-normal' + (row[2] ? ' fw-semibold' : ''), row[0]));
        box.appendChild(createEl('dd', 'col-5 text-end mb-1' + (row[2] ? ' fw-semibold' : ''), row[1]));
    });
}

function shiftRows(shift) {
    return [
        [T('pos.opened_at'), formatDate(shift.opened_at, true)],
        [T('pos.opening_cash'), formatRM(shift.opening_cash)],
        [T('pos.cash_sales', { n: shift.cash_count }), formatRM(shift.cash_sales)],
        [T('pos.online_sales', { n: shift.online_count }), formatRM(shift.online_sales)],
        [T('pos.discount_total'), formatRM(shift.discount_total)],
        [T('pos.expected_cash'), formatRM(shift.expected_cash), true]
    ];
}

function openSalesPanel() {
    var list = byId('sales-list');
    list.replaceChildren(createEl('div', 'text-center text-body-secondary py-4', T('common.loading')));
    bootstrap.Offcanvas.getOrCreateInstance(byId('sales-panel')).show();
    var shiftId = pos.session.shift.id;
    Promise.all([
        Api.get('vendor/pos/shift-summary', withCoop({ shift_id: shiftId })),
        Api.get('vendor/pos/sales', withCoop({ shift_id: shiftId, per_page: 100 }))
    ]).then(function (results) {
        if (results[0].ok) {
            summaryRows(byId('sales-summary'), shiftRows(results[0].data));
        }
        list.replaceChildren();
        var items = results[1].ok ? results[1].data.items : [];
        if (items.length === 0) {
            list.appendChild(createEl('div', 'text-center text-body-secondary py-4', T('pos.no_sales')));
        }
        items.forEach(function (sale) {
            list.appendChild(saleItem(sale));
        });
    });
}

function saleItem(sale) {
    var button = createEl('button', 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2');
    button.type = 'button';
    var left = createStack(sale.order_no, formatDate(sale.created_at, true) + ' · ' + T('payment.method.' + sale.payment_method)
        + ' · ' + T('pos.items_count', { n: sale.item_count }));
    if (sale.status !== 'collected') {
        left.appendChild(statusBadge(sale.status));
    }
    button.appendChild(left);
    button.appendChild(createEl('span', 'fw-semibold text-nowrap', formatRM(sale.total)));
    button.addEventListener('click', function () {
        Api.get('vendor/pos/receipt', withCoop({ order_id: sale.order_id })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            bootstrap.Offcanvas.getOrCreateInstance(byId('sales-panel')).hide();
            showReceipt(res.data, false);
        });
    });
    return button;
}

// ---------- Tutup syif ----------

function openCloseShift() {
    pos.shiftClosed = null;
    var form = byId('shift-close-form');
    resetForm(form);
    form.classList.remove('d-none');
    byId('shift-closed-box').classList.add('d-none');
    byId('closing-variance').textContent = '';
    Api.get('vendor/pos/shift-summary', withCoop({ shift_id: pos.session.shift.id })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        pos.session.shift = res.data;
        summaryRows(byId('shift-summary'), shiftRows(res.data));
        showModal('close-shift-modal');
    });
}

function updateVariance() {
    var input = byId('closing-cash');
    var box = byId('closing-variance');
    if (input.value === '') {
        box.textContent = '';
        return;
    }
    var variance = parseRM(input.value) - pos.session.shift.expected_cash;
    box.textContent = T('pos.variance_line', { amount: formatRM(variance) });
    box.className = 'fw-semibold mb-3 ' + (variance === 0 ? 'text-success' : 'text-danger');
}

function shiftClosed(data) {
    pos.shiftClosed = data;
    byId('shift-close-form').classList.add('d-none');
    byId('shift-closed-box').classList.remove('d-none');
    summaryRows(byId('shift-summary'), shiftRows(data).concat([
        [T('pos.closing_cash'), formatRM(data.closing_cash), true],
        [T('pos.variance'), formatRM(data.variance), true]
    ]));
    renderShiftReport(byId('receipt-print'), data);
    byId('shift-print').focus();
}

// Laporan syif (Z) untuk dicetak pada kertas resit
function renderShiftReport(container, shift) {
    var paper = createEl('div', paperClass());
    receiptHeader(paper, pos.session.cooperative);
    paper.appendChild(createEl('div', 'app-receipt-center app-receipt-title', T('pos.shift_report')));
    receiptRule(paper);
    receiptRow(paper, T('pos.cashier'), shift.user_name);
    receiptRow(paper, T('pos.opened_at'), formatDate(shift.opened_at, true));
    receiptRow(paper, T('pos.closed_at'), formatDate(shift.closed_at, true));
    receiptRule(paper);
    receiptRow(paper, T('pos.sale_count'), String(shift.sale_count));
    receiptRow(paper, T('pos.cash_sales', { n: shift.cash_count }), formatRM(shift.cash_sales));
    receiptRow(paper, T('pos.online_sales', { n: shift.online_count }), formatRM(shift.online_sales));
    receiptRow(paper, T('pos.discount_total'), formatRM(shift.discount_total));
    receiptRule(paper);
    receiptRow(paper, T('pos.opening_cash'), formatRM(shift.opening_cash));
    receiptRow(paper, T('pos.expected_cash'), formatRM(shift.expected_cash));
    receiptRow(paper, T('pos.closing_cash'), formatRM(shift.closing_cash));
    receiptRow(paper, T('pos.variance'), formatRM(shift.variance), 'app-receipt-total');
    if (shift.note) {
        receiptRule(paper);
        paper.appendChild(createEl('div', '', shift.note));
    }
    container.replaceChildren(paper);
}

// ---------- Acara ----------

byId('pin-form').addEventListener('submit', submitPin);
document.querySelectorAll('.app-pos-keypad [data-key]').forEach(function (button) {
    button.addEventListener('click', function () {
        pressKey(button.getAttribute('data-key'));
    });
});
byId('btn-switch-cashier').addEventListener('click', lockCounter);
byId('btn-lock').addEventListener('click', lockCounter);
bindForm('shift-open-form', 'vendor/pos/shift-open', openShift, withCoop);

byId('pos-scan-form').addEventListener('submit', handleScan);
scanInput.addEventListener('input', debounce(renderProducts, 150));

byId('variant-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var variant = selectedVariant();
    if (!variant) {
        return;
    }
    var product = pos.variantProduct;
    var qty = Math.max(1, parseInt(byId('variant-qty').value, 10) || 1);
    hideModal('variant-modal');
    addToCart(product, variant, qty);
});
byId('variant-qty').addEventListener('input', updateVariantButton);
byId('variant-minus').addEventListener('click', function () { changeVariantQty(-1); });
byId('variant-plus').addEventListener('click', function () { changeVariantQty(1); });
byId('variant-modal').addEventListener('shown.bs.modal', function () {
    var checked = document.querySelector('#variant-options [name="variant_id"]:checked');
    (checked || byId('variant-add')).focus();
});

byId('btn-clear-cart').addEventListener('click', function () {
    confirmDialog(T('pos.clear_confirm'), { okText: T('pos.clear_cart'), okClass: 'btn-danger' }).then(function (ok) {
        if (ok) {
            clearCart();
        }
        focusScan();
    });
});
byId('btn-coupon').addEventListener('click', applyCoupon);
byId('pos-coupon').addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        applyCoupon();
    }
});
byId('pos-manual-discount').addEventListener('input', debounce(renderCart, 200));

byId('btn-pay-cash').addEventListener('click', openCash);
byId('btn-pay-online').addEventListener('click', openOnline);
byId('cash-received').addEventListener('input', updateChange);
byId('cash-form').addEventListener('submit', submitCash);
byId('cash-modal').addEventListener('shown.bs.modal', function () {
    byId('cash-received').focus();
});
byId('online-cancel').addEventListener('click', cancelOnline);

byId('receipt-print-btn').addEventListener('click', printReceipt);
byId('receipt-modal').addEventListener('shown.bs.modal', function () {
    byId('receipt-new').focus();
});
// Enter semasa resit dipapar (kecuali pada butang, cth. selepas cetak fokus hilang) = jualan baharu
document.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && byId('receipt-modal').classList.contains('show') && event.target.tagName !== 'BUTTON') {
        event.preventDefault();
        hideModal('receipt-modal');
    }
});

// Modal ditutup → buka resit yang menunggu, atau kembali ke medan imbas
document.addEventListener('hidden.bs.modal', function () {
    if (pos.receiptAfterHide) {
        var next = pos.receiptAfterHide;
        pos.receiptAfterHide = null;
        showReceipt(next.receipt, next.isNew);
        return;
    }
    focusScan();
});
document.addEventListener('hidden.bs.offcanvas', focusScan);

byId('btn-shift-sales').addEventListener('click', openSalesPanel);
byId('btn-close-shift').addEventListener('click', openCloseShift);
byId('closing-cash').addEventListener('input', updateVariance);
bindForm('shift-close-form', 'vendor/pos/shift-close', shiftClosed, function (payload) {
    payload.shift_id = pos.session.shift.id;
    return withCoop(payload);
});
byId('shift-print').addEventListener('click', function () {
    window.print();
    byId('shift-print').focus();
});
byId('close-shift-modal').addEventListener('hidden.bs.modal', function () {
    if (pos.shiftClosed) {
        pos.shiftClosed = null;
        clearCart();
        loadSession();
    }
});

byId('btn-fullscreen').addEventListener('click', function () {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen();
    }
});

// Pintasan: F2 tunai, F4 QR. Aksara yang ditaip di luar medan → terus ke medan imbas (pengimbas USB)
document.addEventListener('keydown', function (event) {
    if (byId('pos-sale').classList.contains('d-none') || isModalOpen()) {
        return;
    }
    if (event.key === 'F2') {
        event.preventDefault();
        openCash();
    } else if (event.key === 'F4') {
        event.preventDefault();
        openOnline();
    } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey && !isTextField(document.activeElement)) {
        scanInput.focus();
    }
});

// Selepas sentuh / klik butang (produk, kategori, +/−), fokus kembali ke medan imbas
document.addEventListener('pointerup', function () {
    setTimeout(function () {
        if (!isTextField(document.activeElement)) {
            focusScan();
        }
    }, 0);
});

onCooperativeReady(function () {
    pos.category = '';
    clearCart();
    loadSession();
});
