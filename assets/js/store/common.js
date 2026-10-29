// Fungsi bantu store ibu bapa. Dipakai oleh SEMUA template (templates/*/ hanya paparan):
// template baharu mesti mengekalkan id & atribut data-* yang dibaca di sini dan dalam fail halaman.
// Bergantung pada: helpers.js, lang.js, api.js

var storeContextEl = document.getElementById('store-context');

var Store = {
    slug: storeContextEl.getAttribute('data-school-slug'),
    page: storeContextEl.getAttribute('data-page'),
    baseUrl: storeContextEl.getAttribute('data-store-url'),
    // URL cantik /{slug}/{halaman} bila config pretty_store_urls aktif (kosong = ?school=…&page=…)
    prettyUrl: storeContextEl.getAttribute('data-pretty-url'),
    school: null,
    customer: null,
    session: null,
    readyCallbacks: [],
    isReady: false
};

// Semua borang store dihantar melalui Api (bindForm). Halang hantar asal pelayar walaupun skrip halaman
// belum siap dimuat (elak kata laluan dalam URL). Borang carian biasa ditanda data-native
document.addEventListener('submit', function (event) {
    if (!event.target.hasAttribute('data-native')) {
        event.preventDefault();
    }
}, true);

// URL halaman store: storeUrl('product', { id: 5 }). slug lalai = sekolah semasa
function storeUrl(page, params, slug) {
    var query = new URLSearchParams();
    var school = slug === undefined ? Store.slug : slug;
    var path = Store.baseUrl;
    if (school && Store.prettyUrl) {
        path = Store.prettyUrl + encodeURIComponent(school) + '/' + (page && page !== 'home' ? encodeURIComponent(page) : '');
    } else if (school) {
        query.set('school', school);
    }
    if (page && page !== 'home' && !(school && Store.prettyUrl)) {
        query.set('page', page);
    }
    Object.keys(params || {}).forEach(function (key) {
        if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
            query.set(key, params[key]);
        }
    });
    var text = query.toString();
    return path + (text ? '?' + text : '');
}

function storeGo(page, params, slug) {
    window.location.href = storeUrl(page, params, slug);
}

// Log masuk, kemudian kembali ke halaman ini (hanya nama halaman + id — tiada URL luar)
function storeGoLogin() {
    storeGo('login', { next: Store.page, next_id: queryParam('id') || queryParam('order') || '' });
}

// Api dengan slug sekolah. Belum log masuk → terus ke halaman log masuk
function storeHandle(res) {
    if (!res.ok && res.auth_required) {
        storeGoLogin();
    }
    return res;
}

function storeGet(path, params) {
    return Api.get(path, Object.assign({ slug: Store.slug }, params || {})).then(storeHandle);
}

function storePost(path, body) {
    return Api.post(path, Object.assign({ slug: Store.slug }, body || {})).then(storeHandle);
}

// Halaman dipanggil selepas sekolah & sesi dimuat oleh layout.js
function onStoreReady(fn) {
    if (Store.isReady) {
        fn(Store);
    } else {
        Store.readyCallbacks.push(fn);
    }
}

// Halaman yang perlu log masuk. Pulangkan false (dan pergi ke log masuk) jika belum
function storeRequireLogin() {
    if (!Store.customer) {
        storeGoLogin();
        return false;
    }
    return true;
}

// isNotice = koperasi ditutup sementara (vendor digantung) → amaran kuning, bukan ralat
function storeShowError(message, isNotice) {
    var box = document.getElementById('store-error');
    box.textContent = message;
    box.classList.toggle('alert-danger', !isNotice);
    box.classList.toggle('alert-warning', Boolean(isNotice));
    box.classList.remove('d-none');
}

function setCartCount(count) {
    document.querySelectorAll('[data-cart-count]').forEach(function (el) {
        el.textContent = count;
        el.classList.toggle('d-none', !(count > 0));
    });
}

// ---------- Paparan kecil ----------

function gradeLabel(code) {
    return code ? T('grade.' + code) : '';
}

// Slot ambil (modul school_opening): "02/01/2027 · 08:00–10:00 · Darjah 1"
function slotText(slot) {
    return [formatDate(slot.slot_date), slot.time_start + '–' + slot.time_end, slot.grade_level_code ? gradeLabel(slot.grade_level_code) : T('store.slot.all_grades')].join(' · ');
}

// "Darjah 4 · Bestari"
function childClassLine(child) {
    return [gradeLabel(child.grade_level_code), child.class_name].filter(Boolean).join(' · ');
}

// Gambar produk atau ikon gantian
function productImage(url, className) {
    var box = createEl('div', 'store-thumb ' + (className || ''));
    if (url) {
        var img = createEl('img');
        img.src = url;
        img.alt = '';
        img.loading = 'lazy';
        box.appendChild(img);
    } else {
        box.appendChild(createIcon('bi-box-seam'));
    }
    return box;
}

// Harga: harga jualan murah dengan harga asal dipotong; "dari RM" untuk varian
function priceEl(price, regular, from, className) {
    var wrap = createEl('div', className || 'fw-bold');
    if (from) {
        wrap.appendChild(createEl('span', 'small fw-normal text-body-secondary me-1', T('store.price_from')));
    }
    wrap.appendChild(createEl('span', regular && regular > price ? 'text-danger' : '', formatRM(price)));
    if (regular && regular > price) {
        wrap.appendChild(createEl('del', 'small fw-normal text-body-secondary ms-1', formatRM(regular)));
    }
    return wrap;
}

// Baki stok untuk ibu bapa: Habis / Stok terhad / (tiada teks)
function stockBadge(available) {
    if (available === null || available === undefined) {
        return null;
    }
    if (available <= 0) {
        return createEl('span', 'badge text-bg-secondary', T('store.stock.out'));
    }
    if (available <= 5) {
        return createEl('span', 'badge text-bg-warning', T('store.stock.low', { n: available }));
    }
    return null;
}

// Kotak kosong dengan ikon, teks dan pautan pilihan
function emptyState(container, iconClass, text, linkText, href) {
    var box = createEl('div', 'text-center text-body-secondary py-5');
    var icon = createIcon(iconClass + ' display-6 d-block mb-2');
    box.appendChild(icon);
    box.appendChild(createEl('p', 'mb-3', text));
    if (linkText) {
        box.appendChild(createLink(linkText, href, 'btn btn-brand'));
    }
    container.replaceChildren(box);
}

function loadingState(container) {
    var box = createEl('div', 'text-center text-body-secondary py-5');
    box.appendChild(createEl('div', 'spinner-border spinner-border-sm me-2'));
    box.appendChild(document.createTextNode(T('common.loading')));
    container.replaceChildren(box);
}

// QR sebagai gambar (qrcode-generator, dijana dalam pelayar)
function qrImage(text, alt, className) {
    var qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    var img = createEl('img', className || '');
    img.src = qr.createDataURL(6, 2);
    img.alt = alt;
    return img;
}

// Pilihan kuantiti − [n] + (input nombor dengan butang)
function qtyStepper(value, max, onChange, label) {
    var group = createEl('div', 'input-group input-group-sm store-qty');
    var minus = createButton('', 'btn-outline-secondary', 'bi-dash', function () { set(Number(input.value) - 1); }, T('store.qty_less'));
    var input = createEl('input', 'form-control text-center');
    var plus = createButton('', 'btn-outline-secondary', 'bi-plus', function () { set(Number(input.value) + 1); }, T('store.qty_more'));
    input.type = 'number';
    input.min = '1';
    input.max = String(max || 99);
    input.inputMode = 'numeric';
    input.value = value;
    input.setAttribute('aria-label', label || T('field.qty'));

    // Had dibaca dari input.max (boleh ditukar halaman, cth. bila varian bertukar)
    function set(n) {
        var next = Math.max(1, Math.min(Number(input.max) || 99, Math.round(n) || 1));
        input.value = next;
        onChange(next);
    }
    input.addEventListener('change', function () { set(Number(input.value)); });
    group.appendChild(minus);
    group.appendChild(input);
    group.appendChild(plus);
    group.qtyInput = input;
    return group;
}

// Senarai "Anak saya" sebagai butang radio (untuk pilih anak bagi item / kit)
function childRadios(container, children, name, selectedId, onChange) {
    container.replaceChildren();
    children.forEach(function (child) {
        var id = name + '-' + child.id;
        var input = createEl('input', 'btn-check');
        input.type = 'radio';
        input.name = name;
        input.id = id;
        input.value = child.id;
        input.checked = Number(selectedId) === child.id;
        input.required = true;
        input.addEventListener('change', function () { onChange(child); });
        var label = createEl('label', 'btn btn-outline-brand text-start store-child-option');
        label.htmlFor = id;
        label.appendChild(createEl('span', 'd-block fw-semibold', child.full_name));
        label.appendChild(createEl('span', 'd-block small', childClassLine(child) + ' · ' + child.school_year));
        container.appendChild(input);
        container.appendChild(label);
    });
}

// Bayaran online (B8): papar amaun & rujukan, kemudian ibu bapa dibawa ke halaman gateway vendor (redirect_url).
// Status PAID hanya diputuskan oleh pelayan (panggilan balik gateway + semakan semula) — bukan oleh halaman kembali.
// payment = null (gateway gagal mencipta bil) → halaman pesanan, ibu bapa boleh tekan "Bayar" semula.
function storeStartPayment(orderId, payment) {
    if (!payment || !payment.redirect_url) {
        storeGo('order-success', { order: orderId });
        return;
    }
    document.getElementById('gateway-text').textContent = T('store.payment.text', { gateway: payment.gateway_name });
    document.getElementById('gateway-amount').textContent = formatRM(payment.amount);
    document.getElementById('gateway-ref').textContent = T('store.payment.reference', { ref: payment.reference });
    document.getElementById('gateway-sandbox').classList.toggle('d-none', payment.mode !== 'sandbox');
    document.getElementById('gateway-pay').href = payment.redirect_url;
    document.getElementById('gateway-cancel').onclick = function () {
        storeGo('order-success', { order: orderId });
    };
    showModal('gateway-modal');
}

// Ringkasan jumlah: jumlah kecil, diskaun, cukai, jumlah (dl.row)
function renderTotals(dl, data) {
    dl.replaceChildren();
    function line(label, value, strong) {
        dl.appendChild(createEl('dt', 'col-7 fw-normal' + (strong ? ' fs-6 fw-bold' : ''), label));
        dl.appendChild(createEl('dd', 'col-5 text-end mb-1' + (strong ? ' fs-6 fw-bold' : ''), value));
    }
    line(T('store.cart.items', { n: data.item_count }), formatRM(data.subtotal));
    if (data.discount > 0) {
        line(data.promotion_name ? T('store.cart.discount_named', { name: data.promotion_name }) : T('order.discount'), '-' + formatRM(data.discount));
    }
    if (data.tax > 0) {
        line(T('field.tax') + ' (' + data.tax_rate + '%)', formatRM(data.tax));
    }
    line(T('field.total'), formatRM(data.total), true);
}

// Tajuk kumpulan anak dalam troli / pesanan
function childGroupHeader(group) {
    var head = createEl('div', 'd-flex align-items-center gap-2 mb-2');
    head.appendChild(createIcon('bi-person-badge fs-5 text-brand'));
    head.appendChild(createStack(group.child_name, childClassLine(group)));
    return head;
}

function sourceBadge(source) {
    return createEl('span', 'badge ' + (source === 'kit' ? 'text-bg-brand' : 'text-bg-light border'), T('store.source.' + source));
}

// Slip beg (satu setiap anak): nama anak, kelas, no. pendek, QR — ibu bapa cetak & masukkan dalam beg (cara D)
function renderSlips(container, order) {
    container.replaceChildren();
    order.groups.forEach(function (group) {
        var slip = createEl('div', 'store-slip');
        slip.appendChild(qrImage(order.qr_token, T('order.qr_alt', { code: order.short_code }), 'store-slip-qr'));
        var text = createEl('div', 'min-w-0');
        text.appendChild(createEl('div', 'store-slip-school', order.school.school_name));
        text.appendChild(createEl('div', 'store-slip-child', group.child_name));
        text.appendChild(createEl('div', '', childClassLine(group)));
        text.appendChild(createEl('div', 'store-slip-code', order.short_code));
        text.appendChild(createEl('div', 'small', order.order_no));
        if (order.payment_status !== 'paid') {
            text.appendChild(createEl('div', 'small fw-bold', T('store.slip.pay_cash', { amount: formatRM(order.total) })));
        }
        slip.appendChild(text);
        container.appendChild(slip);
    });
}

// Cetak slip atau resit sahaja (print-slip.css membaca kelas pada <body>)
function storePrint(mode) {
    document.body.classList.toggle('store-print-receipt', mode === 'receipt');
    window.print();
}

// Resit pesanan (dicetak dari halaman butiran)
function renderReceipt(container, order) {
    container.replaceChildren();
    container.appendChild(createEl('div', 'fw-bold fs-5', order.school.school_name));
    container.appendChild(createEl('div', 'small', [order.school.address, order.school.phone].filter(Boolean).join(' · ')));
    container.appendChild(createEl('hr'));
    container.appendChild(createEl('div', '', T('store.receipt.order_no', { no: order.order_no, code: order.short_code })));
    container.appendChild(createEl('div', 'small', formatDate(order.created_at, true) + ' · ' + order.customer_name));
    var table = createEl('table', 'table table-sm mt-2');
    var body = createEl('tbody');
    order.groups.forEach(function (group) {
        var head = createEl('tr');
        var cell = createEl('th', 'pt-2', group.child_name + ' — ' + childClassLine(group));
        cell.colSpan = 3;
        head.appendChild(cell);
        body.appendChild(head);
        group.items.forEach(function (item) {
            var row = createEl('tr');
            row.appendChild(createEl('td', '', item.product_name + (item.variant_label ? ' — ' + item.variant_label : '')));
            row.appendChild(createEl('td', 'text-end text-nowrap', item.qty + ' × ' + formatRM(item.unit_price)));
            row.appendChild(createEl('td', 'text-end text-nowrap', formatRM(item.line_total)));
            body.appendChild(row);
        });
    });
    table.appendChild(body);
    container.appendChild(table);
    var totals = createEl('dl', 'row mb-0');
    renderTotals(totals, order);
    container.appendChild(totals);
    container.appendChild(createEl('div', 'small mt-2', T('store.receipt.payment', {
        method: T('payment.method.' + order.payment_method), status: T('payment.status.' + order.payment_status) })
        + (order.paid_at ? ' · ' + formatDate(order.paid_at, true) : '')));
    if (order.refunded_amount > 0) {
        container.appendChild(createEl('div', 'small', T('store.receipt.refunded', { amount: formatRM(order.refunded_amount) })));
    }
}
