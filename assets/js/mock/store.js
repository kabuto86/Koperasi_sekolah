// Data mock store ibu bapa — BAKI peralihan B6 → B7/B8. Bentuk objek = kontrak API (PELAN 18.12).
// Sejak B6 sekolah, anak, katalog, produk, kit, troli, berita & jualan murah ialah laluan SEBENAR (api/v1/store/).
// Yang masih mock di sini: checkout (B7), pesanan saya (B7) dan simulasi bayaran (B8).
// Bergantung pada vendor.js / vendor-orders.js / vendor-finance.js / vendor-addons.js / vendor/pos-cart.js — pesanan mock
// terus muncul dalam panel vendor (tab yang sama).
//
// Jambatan peralihan (dibuang dalam B7):
// - Ibu bapa: sesi mock mengikut ibu bapa SEBENAR (dipadankan ikut e-mel) supaya pesanan mock ditapis customer_id sesi.
// - Troli: halaman checkout memuat troli SEBENAR (store/cart/get) sekali sebelum jawapan mock pertama; checkout mock
//   mengira promosi, cukai, slot & kaedah bayaran atas baris itu, kemudian mengosongkan troli sebenar (store/cart/clear).

var MockStore = {
    session: { customer_id: null },
    liveCart: null
};

// ---------- Pembantu: konteks sekolah & sesi ----------

// Slug → koperasi mock aktif (sekolah yang tiada dalam mock → "Sekolah tidak dijumpai" sehingga B7)
function storeCoop(params) {
    var slug = String((params && params.slug) || '');
    return MockVendor.cooperatives.filter(function (coop) {
        return coop.slug === slug && coop.status === 'active';
    })[0] || null;
}

function storeSchoolError() {
    return Mock.fail(T('store.school_not_found'));
}

function storeCustomer() {
    var id = MockStore.session.customer_id;
    if (!id) {
        return null;
    }
    var customer = Mock.findById(MockOrders.customers, id);
    return customer && !customer.deleted_at ? customer : null;
}

// Belum log masuk → halaman membawa ibu bapa ke log masuk (auth_required)
function storeAuthFail() {
    var res = Mock.fail(T('store.login_required'));
    res.auth_required = true;
    return res;
}

// Baris milik koperasi ini (vendor_id koperasi dari pelayan, bukan dari input)
function storeRows(rows, coop) {
    return rows.filter(function (row) {
        return row.vendor_id === coop.vendor_id && row.cooperative_id === coop.id;
    });
}

function storeStockLevel(coop, productId, variantId) {
    return storeRows(MockVendor.stock, coop).filter(function (row) {
        return row.product_id === Number(productId) && row.variant_id === Number(variantId || 0);
    })[0] || null;
}

// Konteks biasa untuk laluan yang perlu sekolah + log masuk
function storeContext(params) {
    var coop = storeCoop(params);
    if (!coop) {
        return { error: storeSchoolError() };
    }
    var customer = storeCustomer();
    if (!customer) {
        return { error: storeAuthFail() };
    }
    return { coop: coop, customer: customer };
}

// ---------- Jambatan troli sebenar (B6 → B7) ----------

// Baris sah dari CartQuote sebenar, dengan maklumat anak dari kumpulannya
function storeLiveLines() {
    var lines = [];
    ((MockStore.liveCart && MockStore.liveCart.groups) || []).forEach(function (group) {
        group.items.forEach(function (item) {
            if (!item.problem) {
                lines.push(Object.assign({}, item, { child: { id: group.child_id, full_name: group.child_name,
                    grade_level_code: group.grade_level_code, class_name: group.class_name } }));
            }
        });
    });
    return lines;
}

// Selepas pesanan mock dicipta: kosongkan troli sebenar (keepalive — halaman terus bertukar ke order-success)
function storeClearLiveCart() {
    var slug = typeof Store !== 'undefined' ? Store.slug : '';
    MockStore.liveCart = null;
    fetch(Api.url('store/cart/clear'), {
        method: 'POST', credentials: 'same-origin', keepalive: true,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': document.body.getAttribute('data-csrf') || '' },
        body: JSON.stringify({ slug: slug })
    }).catch(function () {
        // abaikan — troli boleh dikosongkan sendiri oleh ibu bapa
    });
}

// Gateway aktif koperasi (milik vendor koperasi itu)
function storeGateway(coop) {
    return storeRows(MockFinance.gateways, coop).filter(function (row) { return row.is_active; })[0] || null;
}

function storeActivePromotions(coop) {
    return storeRows(MockFinance.promotions, coop).filter(function (promo) {
        return mockPromotionState(promo) === 'active';
    });
}

// Slot ambil untuk troli ini (darjah anak-anak dalam troli)
function storeSlotsFor(coop, lines) {
    var grades = [];
    lines.forEach(function (line) {
        if (grades.indexOf(line.child.grade_level_code) === -1) {
            grades.push(line.child.grade_level_code);
        }
    });
    return addonSlotsFor(coop, grades).map(addonSlotRow);
}

// Ringkasan checkout atas troli sebenar + promosi, cukai, slot & kaedah bayaran mock (satu promosi setiap pesanan,
// cukai selepas diskaun — sama seperti POS). null = troli sebenar belum dimuat
function storeQuote(customer, coop, couponCode) {
    if (!MockStore.liveCart) {
        return null;
    }
    var live = MockStore.liveCart;
    var lines = storeLiveLines();
    var subtotal = lines.reduce(function (sum, line) { return sum + line.unit_price * line.qty; }, 0);
    var active = storeActivePromotions(coop);
    var code = String(couponCode || '').trim().toUpperCase();
    var promotion = null;
    var couponError = null;
    if (code) {
        promotion = active.filter(function (promo) { return promo.code === code; })[0] || null;
        if (!promotion) {
            couponError = T('pos.coupon_invalid');
        } else if (subtotal < promotion.min_spend) {
            couponError = T('pos.coupon_min_spend', { amount: formatRM(promotion.min_spend) });
            promotion = null;
        }
    }
    if (!promotion && !code) {
        promotion = posBestAutomatic(active, subtotal);
    }
    var taxRate = MockFinance.settings[coop.id].tax_rate || 0;
    var totals = posTotals(lines, { promotion: promotion, taxRate: taxRate });
    var settings = MockOrders.pickupSettings[coop.id];
    var slots = storeSlotsFor(coop, lines);
    var blocking = addonBlockingEvent(coop, mockToday());
    var gateway = storeGateway(coop);
    var driver = gateway ? mockDriver(gateway.gateway_code) : null;
    var methods = [];
    if (coop.accept_cash) {
        methods.push('cash');
    }
    if (coop.accept_online && driver) {
        methods.push('online');
    }
    return {
        lines: lines, promotion: promotion, coupon_error: couponError, tax_rate: taxRate,
        data: {
            groups: live.groups, item_count: live.item_count, has_problem: live.has_problem,
            subtotal: totals.subtotal, discount: totals.discount, promotion_name: promotion ? promotion.name : null, promotion_code: promotion ? promotion.code : null,
            coupon_code: code, coupon_error: couponError, tax_rate: taxRate, tax: totals.tax, total: totals.total,
            payment_methods: methods, gateway_name: driver ? driver.name : null,
            cash_expiry_days: MockFinance.settings[coop.id].cash_order_expiry_days,
            requires_code: settings.methods.indexOf('E') !== -1 || (settings.code_required_above > 0 && totals.total > settings.code_required_above),
            code_required_above: settings.code_required_above,
            pickup_slots: slots,
            slot_required: slots.length > 0,
            orders_blocked: blocking ? { title: blocking.title, reopens_on: addonNextDay(blocking.ends_on) } : null
        }
    };
}

// Pilihan tapis anak di "Pesanan saya": anak yang ada dalam pesanan (salinan nama)
function storeOrderChildren(orders) {
    var children = [];
    orders.forEach(function (order) {
        order.items.forEach(function (item) {
            if (!children.some(function (child) { return child.id === item.child_id; })) {
                children.push({ id: item.child_id, full_name: item.child_name });
            }
        });
    });
    return children;
}

// ---------- Akaun: laluan sebenar sejak B2 — sesi mock mengikut ibu bapa sebenar (untuk pesanan mock) ----------

function storeAdoptCustomer(res) {
    if (!res.ok || !res.data.customer) {
        return;
    }
    var real = res.data.customer;
    var email = String(real.email || '').toLowerCase();
    var customer = MockOrders.customers.filter(function (row) {
        return !row.deleted_at && String(row.email).toLowerCase() === email;
    })[0];
    if (!customer) {
        customer = { id: Mock.nextId(MockOrders.customers), is_active: true, deleted_at: null, created_at: Mock.now() };
        MockOrders.customers.push(customer);
    }
    Object.assign(customer, { full_name: real.full_name, phone: real.phone, email: email, relationship: real.relationship,
        language: real.language, email_verified: real.email_verified });
    MockStore.session.customer_id = customer.id;
}

function storeForgetCustomer(res) {
    if (res.ok) {
        MockStore.session.customer_id = null;
    }
}

// Jawapan store/session sebenar: sesi mock ikut ibu bapa sebenar (troli & sekolah anak sudah sebenar sejak B6)
function storeAdoptSession(res) {
    if (!res.ok) {
        return;
    }
    if (res.data.customer) {
        storeAdoptCustomer(res);
    } else {
        MockStore.session.customer_id = null;
    }
}

Mock.onLive('store/session', storeAdoptSession);
Mock.onLive('store/auth/login', storeAdoptCustomer);
Mock.onLive('store/auth/register', storeAdoptCustomer);
Mock.onLive('store/auth/logout', storeForgetCustomer);
Mock.onLive('store/profile/delete', storeForgetCustomer);
// Setiap halaman: sesi sebenar ialah sumber kebenaran; halaman checkout juga memuat troli sebenar
Mock.beforeFirst(function () {
    var slug = typeof Store !== 'undefined' ? Store.slug : '';
    var requests = [Api.fetchJson('GET', 'store/session', { slug: slug })];
    if (slug && Store.page === 'checkout') {
        requests.push(Api.fetchJson('GET', 'store/cart/get', { slug: slug }));
    }
    return Promise.all(requests).then(function (results) {
        storeAdoptSession(results[0]);
        MockStore.liveCart = results[1] && results[1].ok ? results[1].data : null;
        Mock.saveStores();
    });
});
Mock.onLive('store/profile/save', function (res) {
    storeAdoptCustomer({ ok: res.ok, data: { customer: res.data } });
});
Mock.onLive('store/auth/verify-email', function (res) {
    var customer = storeCustomer();
    if (res.ok && customer) {
        customer.email_verified = true;
    }
});

// ---------- Checkout ----------

Mock.register('store/checkout/quote', function (params) {
    var ctx = storeContext(params);
    if (ctx.error) {
        return ctx.error;
    }
    var quote = storeQuote(ctx.customer, ctx.coop, params.coupon_code);
    if (!quote) {
        return Mock.fail(T('error.network'));
    }
    quote.data.customer_name = ctx.customer.full_name;
    quote.data.email_verified = Boolean(ctx.customer.email_verified);
    return Mock.ok(quote.data);
});

// No. pesanan TM-2610-0046: singkatan sekolah (perkataan selepas jenis) + YYMM + id
function storeOrderNumbers(coop, orderId) {
    var words = coop.school_name.split(/\s+/).slice(1);
    var prefix = words.map(function (word) { return word.charAt(0).toUpperCase(); }).join('').slice(0, 3) || 'KP';
    var day = mockToday();
    var used = storeRows(MockOrders.orders, coop).map(function (order) { return order.short_code; });
    var code = null;
    for (var n = 0; !code || used.indexOf(code) !== -1; n++) {
        code = String.fromCharCode(65 + ((orderId + n) % 8)) + String((orderId * 37 + n * 11) % 1000).padStart(3, '0');
    }
    return { order_no: prefix + '-' + day.slice(2, 4) + day.slice(5, 7) + '-' + String(orderId).padStart(4, '0'), short_code: code };
}

function storeExpiry(days) {
    return mockDaysFromToday(days) + 'T23:59:59+08:00';
}

function storeReserve(coop, order, type) {
    order.items.forEach(function (item) {
        var level = storeStockLevel(coop, item.product_id, item.variant_id);
        if (!level) {
            return;
        }
        level.qty_reserved = type === 'reserve' ? level.qty_reserved + item.qty : Math.max(0, level.qty_reserved - item.qty);
        mockMoveStock(level, type, 0, 'order', order.id, order.order_no);
        MockVendor.movements[0].user_name = order.customer_name;
    });
}

Mock.register('store/checkout/place', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    if (!ctx.customer.email_verified) {
        return Mock.fail(T('store.checkout.verify_first'));
    }
    var quote = storeQuote(ctx.customer, ctx.coop, body.coupon_code);
    if (!quote) {
        return Mock.fail(T('error.network'));
    }
    var data = quote.data;
    var errors = {};
    if (data.item_count === 0) {
        return Mock.fail(T('store.cart.empty'));
    }
    if (data.has_problem) {
        return Mock.fail(T('store.checkout.fix_cart'));
    }
    // Modul calendar: tempahan ditutup pada hari koperasi tutup
    if (data.orders_blocked) {
        return Mock.fail(T('store.closed.blocked', { title: data.orders_blocked.title, date: formatDate(data.orders_blocked.reopens_on) }));
    }
    if (quote.coupon_error) {
        return Mock.fail(quote.coupon_error, { coupon_code: quote.coupon_error });
    }
    if (['student', 'parent', 'proxy'].indexOf(body.pickup_by_type) === -1) {
        errors.pickup_by_type = T('error.required');
    }
    var pickupName = String(body.pickup_by_name || '').trim();
    if (body.pickup_by_type === 'proxy' && !pickupName) {
        errors.pickup_by_name = T('error.required');
    }
    if (data.payment_methods.indexOf(body.payment_method) === -1) {
        errors.payment_method = T('store.checkout.method_unavailable');
    }
    if (data.requires_code && !/^[0-9]{4}$/.test(String(body.pickup_code || ''))) {
        errors.pickup_code = T('handover.code_format');
    }
    // Modul school_opening: slot mesti dari senarai yang sesuai dan masih ada tempat (dikira semula di sini)
    var slot = null;
    if (data.slot_required) {
        var chosen = data.pickup_slots.filter(function (row) { return row.id === Number(body.pickup_slot_id); })[0];
        if (!chosen) {
            errors.pickup_slot_id = T('store.slot.choose');
        } else if (chosen.remaining < 1) {
            errors.pickup_slot_id = T('store.slot.full');
        } else {
            slot = Mock.findById(MockAddons.slots, chosen.id);
        }
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    // Harga berubah sejak ibu bapa menyemak (cth. stok/promosi) → minta semak semula
    if (body.expected_total !== undefined && Number(body.expected_total) !== data.total) {
        return Mock.fail(T('store.checkout.total_changed', { total: formatRM(data.total) }));
    }

    var orderId = Mock.nextId(MockOrders.orders);
    var numbers = storeOrderNumbers(ctx.coop, orderId);
    var now = Mock.now();
    var isCash = body.payment_method === 'cash';
    var status = isCash ? 'unpaid_cash' : 'pending_payment';
    var lines = quote.lines;
    var order = {
        id: orderId, vendor_id: ctx.coop.vendor_id, cooperative_id: ctx.coop.id, customer_id: ctx.customer.id,
        order_no: numbers.order_no, short_code: numbers.short_code, channel: 'online', payment_method: body.payment_method,
        status: status, payment_status: isCash ? 'unpaid' : 'pending',
        subtotal: data.subtotal, promotion_id: quote.promotion ? quote.promotion.id : null, promotion_discount: data.discount, manual_discount: 0,
        discount: data.discount, tax_rate: data.tax_rate, tax: data.tax, total: data.total,
        pickup_by_type: body.pickup_by_type,
        pickup_slot_id: slot ? slot.id : null,
        pickup_slot: slot ? { slot_date: slot.slot_date, time_start: slot.time_start, time_end: slot.time_end, grade_level_code: slot.grade_level_code } : null,
        pickup_by_name: body.pickup_by_type === 'parent' ? ctx.customer.full_name : (body.pickup_by_type === 'proxy' ? pickupName : ''),
        // Peringkat 2: hash Argon2id; ibu bapa yang menetapkan kod (diberi kepada anak). Tidak wajib → kod rawak, tidak digunakan
        pickup_code: data.requires_code ? String(body.pickup_code) : String(1000 + (orderId * 739) % 9000),
        pickup_attempts: 0, pickup_locked: false,
        pickup_qr_token: 'KPK-' + (orderId * 7919).toString(36).toUpperCase().padStart(6, '0'),
        customer_name: ctx.customer.full_name, customer_phone: ctx.customer.phone, customer_email: ctx.customer.email,
        expires_at: isCash ? storeExpiry(data.cash_expiry_days) : null, paid_at: null, collected_at: null, created_at: now,
        gateway_ref: null,
        // Baris dari troli SEBENAR (B6) — product_id ialah id DB; stok mock tidak ditahan (live_cart). B7: CheckoutJob sebenar
        live_cart: true,
        items: lines.map(function (line, index) {
            return { id: orderId * 100 + index + 1, product_id: line.product_id, variant_id: line.variant_id,
                product_name: line.name, variant_label: line.variant_label, sku: null,
                unit_price: line.unit_price, qty: line.qty, line_total: line.unit_price * line.qty, source: line.source,
                school_kit_id: line.school_kit_id, clearance_item_id: null,
                child_id: line.child.id, child_name: line.child.full_name, grade_level_code: line.child.grade_level_code, class_name: line.child.class_name,
                collected_qty: 0, cancelled_qty: 0, returned_qty: 0 };
        }),
        logs: [{ from_status: null, to_status: status, actor: 'customer', actor_name: ctx.customer.full_name, note: '', created_at: now }]
    };
    // Murid sendiri: nama anak (seorang) atau senarai nama anak
    if (body.pickup_by_type === 'student') {
        order.pickup_by_name = data.groups.map(function (group) { return group.child_name; }).join(', ');
    }
    MockOrders.orders.push(order);
    if (slot) {
        slot.booked++;
    }
    if (quote.promotion) {
        quote.promotion.used_count++;
    }
    storeClearLiveCart();
    // Peringkat 2: e-mel amaran kepada vendor + e-mel kepada ibu bapa (no. pendek, QR, slip) melalui email_queue

    var payment = null;
    if (!isCash) {
        order.gateway_ref = 'ST' + String(700000 + order.id * 43);
        payment = storePaymentInfo(ctx.coop, order);
    }
    return Mock.ok({ order_id: order.id, order_no: order.order_no, short_code: order.short_code, payment: payment });
});

function storePaymentInfo(coop, order) {
    var gateway = storeGateway(coop);
    var driver = gateway ? mockDriver(gateway.gateway_code) : null;
    // Peringkat 2: redirect_url ke halaman bayaran gateway vendor; status PAID hanya selepas webhook disahkan pelayan
    return { reference: order.gateway_ref, gateway_name: driver ? driver.name : '', amount: order.total, mode: gateway ? gateway.mode : 'sandbox', redirect_url: null };
}

// Peringkat 1 sahaja: simulasi halaman gateway (bayar / batal). Peringkat 2: webhook gateway → B8
Mock.register('store/payment/simulate', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var order = storeFindOrder(ctx, body.order_id);
    if (!order) {
        return Mock.fail(T('error.not_found'));
    }
    if (order.status !== 'pending_payment') {
        return Mock.ok({ status: order.status });
    }
    if (body.result === 'paid') {
        order.payment_status = 'paid';
        order.paid_at = Mock.now();
        order.logs.push({ from_status: order.status, to_status: 'paid', actor: 'system', actor_name: storePaymentInfo(ctx.coop, order).gateway_name, note: order.gateway_ref, created_at: order.paid_at });
        order.status = 'paid';
    }
    return Mock.ok({ status: order.status });
});

// ---------- Pesanan saya ----------

function storeFindOrder(ctx, id) {
    return storeRows(MockOrders.orders, ctx.coop).filter(function (order) {
        return order.id === Number(id) && order.customer_id === ctx.customer.id && order.channel === 'online';
    })[0] || null;
}

function storeOrderSummary(order) {
    var groups = mockChildrenOf(order);
    return { id: order.id, order_no: order.order_no, short_code: order.short_code, status: order.status, payment_method: order.payment_method,
        payment_status: order.payment_status, total: order.total, created_at: order.created_at, expires_at: order.expires_at,
        children: groups.map(function (group) { return { child_id: group.child_id, child_name: group.child_name, grade_level_code: group.grade_level_code, class_name: group.class_name }; }),
        item_count: order.items.reduce(function (sum, item) { return sum + item.qty; }, 0) };
}

function storeOrderDetail(ctx, order) {
    var data = storeOrderSummary(order);
    var promotion = order.promotion_id ? Mock.findById(MockFinance.promotions, order.promotion_id) : null;
    data.subtotal = order.subtotal;
    data.discount = order.discount || 0;
    data.promotion_name = promotion ? promotion.name : null;
    data.tax = order.tax || 0;
    data.tax_rate = order.tax_rate || 0;
    data.refunded_amount = order.refunded_amount || 0;
    data.paid_at = order.paid_at;
    data.collected_at = order.collected_at;
    data.pickup_by_type = order.pickup_by_type;
    data.pickup_by_name = order.pickup_by_name;
    data.pickup_slot = addonOrderSlot(order);
    data.requires_code = mockRequiresCode(order);
    data.qr_token = order.pickup_qr_token;
    data.gateway_ref = order.payment_method === 'online' ? order.gateway_ref : null;
    data.can_pay = order.status === 'pending_payment';
    data.can_cancel = ['pending_payment', 'unpaid_cash'].indexOf(order.status) !== -1;
    data.customer_name = order.customer_name;
    data.school = { school_name: ctx.coop.school_name, address: ctx.coop.address, phone: ctx.coop.phone };
    data.groups = mockChildrenOf(order).map(function (group) {
        group.items = group.items.map(function (item) {
            return { id: item.id, product_name: item.product_name, variant_label: item.variant_label, unit_price: item.unit_price, qty: item.qty,
                line_total: item.line_total, collected_qty: item.collected_qty, cancelled_qty: item.cancelled_qty || 0, returned_qty: item.returned_qty || 0, source: item.source };
        });
        return group;
    });
    // Kepada ibu bapa: status & masa sahaja (tiada nama kakitangan)
    data.logs = order.logs.map(function (log) { return { to_status: log.to_status, created_at: log.created_at }; });
    data.pickups = MockOrders.pickups.filter(function (pickup) { return pickup.order_id === order.id; }).map(function (pickup) {
        return { picked_by_type: pickup.picked_by_type, picked_by_name: pickup.picked_by_name, created_at: pickup.created_at,
            items: pickup.items.map(function (row) {
                var item = order.items.filter(function (i) { return i.id === row.order_item_id; })[0];
                return { product_name: item.product_name, variant_label: item.variant_label, child_name: item.child_name, qty: row.qty };
            }) };
    });
    return data;
}

Mock.register('store/orders/list', function (params) {
    var ctx = storeContext(params);
    if (ctx.error) {
        return ctx.error;
    }
    var mine = storeRows(MockOrders.orders, ctx.coop).filter(function (order) {
        return order.customer_id === ctx.customer.id && order.channel === 'online';
    });
    var years = [];
    mine.forEach(function (order) {
        var year = Number(order.created_at.slice(0, 4));
        if (years.indexOf(year) === -1) {
            years.push(year);
        }
    });
    var rows = mine.filter(function (order) {
        return (!params.child_id || order.items.some(function (item) { return item.child_id === Number(params.child_id); }))
            && (!params.year || order.created_at.slice(0, 4) === String(params.year));
    }).sort(function (a, b) { return a.created_at < b.created_at ? 1 : -1; }).map(storeOrderSummary);
    var data = Mock.paginate(rows, params, []);
    data.years = years.sort().reverse();
    data.children = storeOrderChildren(mine);
    data.ready_count = mine.filter(function (order) { return ['ready', 'partially_collected'].indexOf(order.status) !== -1; }).length;
    return Mock.ok(data);
});

Mock.register('store/orders/get', function (params) {
    var ctx = storeContext(params);
    if (ctx.error) {
        return ctx.error;
    }
    var order = storeFindOrder(ctx, params.id);
    // Pesanan ibu bapa lain / koperasi lain → tidak dijumpai (Peringkat 2: 404)
    return order ? Mock.ok(storeOrderDetail(ctx, order)) : Mock.fail(T('error.not_found'));
});

// Bayar semula pesanan online yang belum dibayar
Mock.register('store/orders/pay', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var order = storeFindOrder(ctx, body.id);
    if (!order) {
        return Mock.fail(T('error.not_found'));
    }
    if (order.status !== 'pending_payment') {
        return Mock.fail(T('store.order.not_payable'));
    }
    order.gateway_ref = order.gateway_ref || 'ST' + String(700000 + order.id * 43);
    return Mock.ok({ order_id: order.id, payment: storePaymentInfo(ctx.coop, order) });
});

Mock.register('store/orders/cancel', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var order = storeFindOrder(ctx, body.id);
    if (!order) {
        return Mock.fail(T('error.not_found'));
    }
    if (['pending_payment', 'unpaid_cash'].indexOf(order.status) === -1) {
        return Mock.fail(T('store.order.cannot_cancel'));
    }
    if (!order.live_cart) {
        storeReserve(ctx.coop, order, 'release');
    }
    addonReleaseSlot(order);
    order.logs.push({ from_status: order.status, to_status: 'cancelled', actor: 'customer', actor_name: ctx.customer.full_name, note: T('store.order.cancelled_by_parent'), created_at: Mock.now() });
    order.status = 'cancelled';
    order.payment_status = 'none';
    order.cancelled_at = Mock.now();
    return Mock.ok(storeOrderDetail(ctx, order));
});

// Kekalkan sesi mock antara halaman (sessionStorage) — B7: sesi PHP sahaja
Mock.persist('store', MockStore, ['session']);
