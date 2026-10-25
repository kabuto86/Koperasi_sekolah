// Data mock POS kaunter (U4): log masuk PIN juruwang, syif & tunai laci, katalog POS, checkout tunai / online QR, resit.
// Bergantung pada mock/vendor.js, vendor-orders.js, vendor-finance.js dan assets/js/vendor/pos-cart.js (posTotals).
// Jualan POS disimpan sebagai pesanan channel 'pos' dalam MockOrders.orders (customer_id null, terus 'collected'),
// jadi ia muncul dalam rekod bayaran, laporan dan pemulangan & refund tanpa kod tambahan.

var MockPos = {
    // PIN demo untuk semua juruwang yang pin_set (Peringkat 2: pin_hash Argon2id dalam vendor_users)
    demoPin: '1234',
    maxPinAttempts: 5,
    // Juruwang yang log masuk di kaunter setiap koperasi (Peringkat 2: $_SESSION['pos_user_id'][cooperative_id])
    sessions: {},
    pinAttempts: {},
    // pos_shifts
    shifts: [
        { id: 1, vendor_id: 1, cooperative_id: 1, vendor_user_id: 12, opened_at: '2026-10-24T07:20:00+08:00', opening_cash: 10000,
          closed_at: '2026-10-24T13:10:00+08:00', closing_cash: 48550, expected_cash: 48600, variance: -50, note: 'Kurang 50 sen — syiling', status: 'closed' },
        { id: 2, vendor_id: 2, cooperative_id: 3, vendor_user_id: 21, opened_at: '2026-10-25T07:15:00+08:00', opening_cash: 5000,
          closed_at: null, closing_cash: null, expected_cash: null, variance: null, note: '', status: 'open' }
    ],
    // Simulasi pembeli mengimbas QR: dibayar pada semakan status ke-2
    paymentPolls: {}
};

// ---------- Pembantu ----------

// Kakitangan yang boleh guna POS koperasi ini: aktif, PIN ditetapkan, ada akses koperasi
function mockPosCanUse(user, cooperativeId) {
    return Boolean(user) && user.vendor_id === MockVendor.sessionVendorId && user.is_active && user.pin_set
        && (user.role === 'vendor_admin' || user.cooperative_ids.indexOf(Number(cooperativeId)) !== -1);
}

function mockPosCashier(cooperativeId) {
    var user = Mock.findById(MockFinance.staff, MockPos.sessions[cooperativeId]);
    return mockPosCanUse(user, cooperativeId) ? user : null;
}

function mockPosCashierPublic(user) {
    return user ? { id: user.id, full_name: user.full_name, role: user.role } : null;
}

function mockPosOpenShift(cooperativeId, userId) {
    return mockCoopRows(MockPos.shifts, cooperativeId).filter(function (shift) {
        return shift.status === 'open' && shift.vendor_user_id === Number(userId);
    })[0] || null;
}

function mockPosFindShift(id, cooperativeId) {
    return mockCoopRows(MockPos.shifts, cooperativeId).filter(function (shift) {
        return shift.id === Number(id);
    })[0] || null;
}

function mockPosShiftOrders(shift) {
    return mockCoopRows(MockOrders.orders, shift.cooperative_id).filter(function (order) {
        return order.channel === 'pos' && order.shift_id === shift.id;
    });
}

// Ringkasan syif: jangkaan tunai laci = tunai permulaan + jualan tunai
function mockPosShiftSummary(shift) {
    var paid = mockPosShiftOrders(shift).filter(function (order) { return order.payment_status === 'paid'; });
    var cash = paid.filter(function (order) { return order.payment_method === 'cash'; });
    var online = paid.filter(function (order) { return order.payment_method === 'online'; });
    var sum = function (rows, field) {
        return rows.reduce(function (total, row) { return total + row[field]; }, 0);
    };
    var expected = shift.opening_cash + sum(cash, 'total');
    return {
        id: shift.id, cooperative_id: shift.cooperative_id, user_id: shift.vendor_user_id, user_name: mockStaffName(shift.vendor_user_id),
        status: shift.status, opened_at: shift.opened_at, closed_at: shift.closed_at, opening_cash: shift.opening_cash,
        sale_count: paid.length, item_count: paid.reduce(function (total, order) {
            return total + order.items.reduce(function (n, item) { return n + item.qty; }, 0);
        }, 0),
        cash_count: cash.length, cash_sales: sum(cash, 'total'), online_count: online.length, online_sales: sum(online, 'total'),
        discount_total: sum(paid, 'discount'), tax_total: sum(paid, 'tax'),
        pending_count: mockPosShiftOrders(shift).filter(function (order) { return order.status === 'pending_payment'; }).length,
        expected_cash: shift.status === 'closed' ? shift.expected_cash : expected,
        closing_cash: shift.closing_cash, variance: shift.variance, note: shift.note
    };
}

// Log aktiviti atas nama juruwang yang log masuk dengan PIN (bukan akaun panel)
function mockPosActivity(user, action, cooperativeId, entity, entityId, detail) {
    mockActivity(action, cooperativeId, entity, entityId, detail);
    MockFinance.activity[0].user_id = user.id;
}

function mockPosRequireCashier(params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return { error: mockCoopError() };
    }
    var user = mockPosCashier(coop.id);
    if (!user) {
        return { error: Mock.fail(T('pos.error.login_required')) };
    }
    return { coop: coop, user: user };
}

function mockPosGatewayName(cooperativeId) {
    var gateway = mockCoopGateway(cooperativeId);
    var driver = gateway ? mockDriver(gateway.gateway_code) : null;
    return driver ? driver.name : null;
}

function mockPosAvailable(level) {
    return level ? level.qty_on_hand - level.qty_reserved : 0;
}

// Promosi berjalan koperasi ini (state dikira pelayan, sama seperti vendor/promotions/list)
function mockPosActivePromotions(cooperativeId) {
    return mockCoopRows(MockFinance.promotions, cooperativeId).filter(function (promo) {
        return mockPromotionState(promo) === 'active';
    });
}

// No. resit POS: POS-YYMMDD-001 (berturut setiap koperasi setiap hari) & kod pendek P001
function mockPosNumbers(cooperativeId) {
    var day = mockToday();
    var posOrders = mockCoopRows(MockOrders.orders, cooperativeId).filter(function (order) { return order.channel === 'pos'; });
    var today = posOrders.filter(function (order) { return order.created_at.slice(0, 10) === day; }).length;
    return {
        order_no: 'POS-' + day.slice(2, 4) + day.slice(5, 7) + day.slice(8, 10) + '-' + String(today + 1).padStart(3, '0'),
        short_code: 'P' + String((posOrders.length % 999) + 1).padStart(3, '0')
    };
}

// Kira semula troli di "pelayan": harga, stok, had beli, promosi, cukai. Nilai dari pelayar diabaikan
function mockPosPrice(coop, body) {
    var lines = [];
    var needed = {};
    var error = null;
    (body.items || []).forEach(function (row) {
        if (error) {
            return;
        }
        var product = mockFindProduct(row.product_id, coop.id);
        var qty = parseInt(row.qty, 10);
        if (!product || !product.is_active || !(qty >= 1)) {
            error = T('pos.error.invalid_item');
            return;
        }
        var variant = row.variant_id ? mockFindVariant(product, row.variant_id) : null;
        if (product.has_variants && (!variant || !variant.is_active)) {
            error = T('pos.error.choose_variant', { name: product.name });
            return;
        }
        var level = mockStockLevel(coop.id, product.id, variant ? variant.id : 0);
        var key = product.id + '#' + (variant ? variant.id : 0);
        needed[key] = (needed[key] || 0) + qty;
        if (needed[key] > mockPosAvailable(level)) {
            error = T('pos.error.stock', { name: product.name + (variant ? ' — ' + variant.label : ''), n: mockPosAvailable(level) });
            return;
        }
        if (product.max_per_order && needed[key] > product.max_per_order) {
            error = T('pos.error.max_per_order', { name: product.name, n: product.max_per_order });
            return;
        }
        var price = posUnitPrice(product, variant);
        lines.push({ product: product, variant: variant, level: level, qty: qty, unit_price: price, line_total: price * qty });
    });
    if (!error && lines.length === 0) {
        error = T('pos.cart_empty');
    }
    if (error) {
        return { error: error };
    }

    var subtotal = lines.reduce(function (sum, line) { return sum + line.line_total; }, 0);
    var active = mockPosActivePromotions(coop.id);
    var promotion = null;
    var code = String(body.coupon_code || '').trim().toUpperCase();
    if (code) {
        promotion = active.filter(function (promo) { return promo.code === code; })[0] || null;
        if (!promotion) {
            return { error: T('pos.coupon_invalid'), field: 'coupon_code' };
        }
        if (subtotal < promotion.min_spend) {
            return { error: T('pos.coupon_min_spend', { amount: formatRM(promotion.min_spend) }), field: 'coupon_code' };
        }
    } else {
        promotion = posBestAutomatic(active, subtotal);
    }
    var manual = parseInt(body.manual_discount, 10) || 0;
    if (manual < 0 || manual > subtotal) {
        return { error: T('pos.error.manual_discount'), field: 'manual_discount' };
    }
    var taxRate = MockFinance.settings[coop.id].tax_rate || 0;
    var totals = posTotals(lines, { promotion: promotion, manualDiscount: manual, taxRate: taxRate });
    return { lines: lines, promotion: promotion, tax_rate: taxRate, totals: totals };
}

function mockPosReceipt(order) {
    var coop = mockOwnCooperative(order.cooperative_id);
    var promotion = order.promotion_id ? Mock.findById(MockFinance.promotions, order.promotion_id) : null;
    return {
        order_id: order.id, order_no: order.order_no, short_code: order.short_code, status: order.status, payment_status: order.payment_status,
        created_at: order.created_at, paid_at: order.paid_at, cashier_name: mockStaffName(order.cashier_id), customer_name: order.customer_name,
        shift_id: order.shift_id,
        items: order.items.map(function (item) {
            return { product_name: item.product_name, variant_label: item.variant_label, sku: item.sku, qty: item.qty, unit_price: item.unit_price,
                line_total: item.line_total, returned_qty: item.returned_qty || 0 };
        }),
        subtotal: order.subtotal, promotion_name: promotion ? promotion.name : null, promotion_code: promotion ? promotion.code : null,
        promotion_discount: order.promotion_discount, manual_discount: order.manual_discount, discount: order.discount,
        tax_rate: order.tax_rate, tax: order.tax, total: order.total, refunded_amount: order.refunded_amount || 0,
        payment_method: order.payment_method, gateway_name: order.payment_method === 'online' ? mockPosGatewayName(order.cooperative_id) : null,
        gateway_ref: order.gateway_ref, amount_received: order.amount_received, change: order.change,
        school_name: coop.school_name, address: coop.address, phone: coop.phone, logo_url: coop.logo_url
    };
}

function mockPosFindSale(id, cooperativeId) {
    var order = mockFindOrder(id, cooperativeId);
    return order && order.channel === 'pos' ? order : null;
}

// Pergerakan stok atas nama juruwang POS
function mockPosMove(level, type, qtyChange, order, user) {
    mockMoveStock(level, type, qtyChange, 'order', order.id, order.order_no);
    MockVendor.movements[0].user_name = user.full_name;
}

// Bayaran diterima: tolak stok (tahanan dilepas untuk online), kira guna promosi
function mockPosMarkPaid(order, user) {
    order.items.forEach(function (item) {
        var level = mockStockLevel(order.cooperative_id, item.product_id, item.variant_id);
        if (order.payment_method === 'online') {
            level.qty_reserved = Math.max(0, level.qty_reserved - item.qty);
        }
        mockPosMove(level, 'pos_sale', -item.qty, order, user);
        item.collected_qty = item.qty;
    });
    if (order.promotion_id) {
        Mock.findById(MockFinance.promotions, order.promotion_id).used_count++;
    }
    var from = order.status;
    order.status = 'collected';
    order.payment_status = 'paid';
    order.paid_at = Mock.now();
    order.collected_at = order.paid_at;
    order.logs.push({ from_status: from, to_status: 'collected', actor: 'vendor_user', actor_name: user.full_name, note: T('pos.log_sale'), created_at: order.paid_at });
    mockPosActivity(user, 'pos_sale', order.cooperative_id, 'order', order.id, order.order_no + ' · ' + formatRM(order.total));
}

// ---------- Sesi & log masuk PIN ----------

// Kalendar (modul calendar): hari "koperasi tutup" — amaran sahaja, jualan kaunter tidak disekat
function mockPosClosedToday(coop) {
    var event = addonBlockingEvent(coop, mockToday());
    return event ? { title: event.title, reopens_on: addonNextDay(event.ends_on) } : null;
}

Mock.register('vendor/pos/session', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var user = mockPosCashier(coop.id);
    var shift = user ? mockPosOpenShift(coop.id, user.id) : null;
    return Mock.ok({
        cooperative: { id: coop.id, school_name: coop.school_name, address: coop.address, phone: coop.phone, logo_url: coop.logo_url },
        cashier: mockPosCashierPublic(user), shift: shift ? mockPosShiftSummary(shift) : null,
        online_available: Boolean(mockCoopGateway(coop.id)), gateway_name: mockPosGatewayName(coop.id),
        closed_today: mockPosClosedToday(coop)
    });
});

Mock.register('vendor/pos/pin-login', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var user = mockOwnRows(MockFinance.staff).filter(function (row) { return row.id === Number(body.user_id); })[0] || null;
    if (!mockPosCanUse(user, coop.id)) {
        return Mock.fail(T('pos.error.no_access'));
    }
    var attempts = MockPos.pinAttempts[user.id] || 0;
    if (attempts >= MockPos.maxPinAttempts) {
        return Mock.fail(T('pos.pin_locked'));
    }
    if (String(body.pin || '') !== MockPos.demoPin) {
        MockPos.pinAttempts[user.id] = attempts + 1;
        var left = MockPos.maxPinAttempts - attempts - 1;
        var message = left > 0 ? T('pos.pin_wrong', { left: left }) : T('pos.pin_locked');
        return Mock.fail(message, { pin: message });
    }
    MockPos.pinAttempts[user.id] = 0;
    MockPos.sessions[coop.id] = user.id;
    user.last_login_at = Mock.now();
    mockPosActivity(user, 'pos_login', coop.id, null, null, '');
    var shift = mockPosOpenShift(coop.id, user.id);
    return Mock.ok({ cashier: mockPosCashierPublic(user), shift: shift ? mockPosShiftSummary(shift) : null });
});

// Kunci kaunter / tukar juruwang — syif kekal dibuka
Mock.register('vendor/pos/lock', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    delete MockPos.sessions[coop.id];
    return Mock.ok({});
});

// ---------- Syif & tunai laci ----------

Mock.register('vendor/pos/shift-open', function (body) {
    var ctx = mockPosRequireCashier(body);
    if (ctx.error) {
        return ctx.error;
    }
    if (mockPosOpenShift(ctx.coop.id, ctx.user.id)) {
        return Mock.fail(T('pos.shift_already_open'));
    }
    if (body.opening_cash === null || body.opening_cash === undefined || !(body.opening_cash >= 0)) {
        return Mock.fail(T('error.check_form'), { opening_cash: T('error.required') });
    }
    var shift = { id: Mock.nextId(MockPos.shifts), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id, vendor_user_id: ctx.user.id,
        opened_at: Mock.now(), opening_cash: Math.round(body.opening_cash), closed_at: null, closing_cash: null, expected_cash: null, variance: null,
        note: '', status: 'open' };
    MockPos.shifts.push(shift);
    mockPosActivity(ctx.user, 'pos_shift_open', ctx.coop.id, 'pos_shift', shift.id, formatRM(shift.opening_cash));
    return Mock.ok(mockPosShiftSummary(shift));
});

Mock.register('vendor/pos/shift-summary', function (params) {
    var ctx = mockPosRequireCashier(params);
    if (ctx.error) {
        return ctx.error;
    }
    var shift = mockPosFindShift(params.shift_id, ctx.coop.id);
    if (!shift) {
        return mockCoopError();
    }
    return Mock.ok(mockPosShiftSummary(shift));
});

Mock.register('vendor/pos/shift-close', function (body) {
    var ctx = mockPosRequireCashier(body);
    if (ctx.error) {
        return ctx.error;
    }
    var shift = mockPosFindShift(body.shift_id, ctx.coop.id);
    if (!shift || shift.vendor_user_id !== ctx.user.id || shift.status !== 'open') {
        return mockCoopError();
    }
    if (body.closing_cash === null || body.closing_cash === undefined || !(body.closing_cash >= 0)) {
        return Mock.fail(T('error.check_form'), { closing_cash: T('error.required') });
    }
    var summary = mockPosShiftSummary(shift);
    if (summary.pending_count > 0) {
        return Mock.fail(T('pos.shift_pending_payments', { n: summary.pending_count }));
    }
    shift.status = 'closed';
    shift.closed_at = Mock.now();
    shift.closing_cash = Math.round(body.closing_cash);
    shift.expected_cash = summary.expected_cash;
    shift.variance = shift.closing_cash - shift.expected_cash;
    shift.note = String(body.note || '').trim();
    mockPosActivity(ctx.user, 'pos_shift_close', ctx.coop.id, 'pos_shift', shift.id,
        formatRM(shift.closing_cash) + ' · ' + T('pos.variance') + ' ' + formatRM(shift.variance));
    delete MockPos.sessions[ctx.coop.id];
    return Mock.ok(mockPosShiftSummary(shift));
});

// ---------- Katalog POS ----------

// Produk aktif sahaja, dengan barcode & stok boleh dijual (on_hand − reserved) setiap varian
Mock.register('vendor/pos/catalog', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var products = mockSortBy(mockCoopRows(MockVendor.products, coop.id).filter(function (product) { return product.is_active; }), 'sort_order');
    var used = {};
    var items = products.map(function (product) {
        used[product.category_id] = true;
        var variants = product.variants.filter(function (variant) { return variant.is_active; }).map(function (variant) {
            return { id: variant.id, label: variant.label, sku: variant.sku, barcode: variant.barcode, price_delta: variant.price_delta,
                stock_available: mockPosAvailable(mockStockLevel(coop.id, product.id, variant.id)) };
        });
        var available = product.has_variants ? variants.reduce(function (sum, variant) { return sum + Math.max(0, variant.stock_available); }, 0)
            : mockPosAvailable(mockStockLevel(coop.id, product.id, 0));
        var translation = product.translations && product.translations[currentLang()] ? product.translations[currentLang()].name : '';
        return { id: product.id, category_id: product.category_id, name: product.name, display_name: translation || product.name, sku: product.sku,
            barcode: product.barcode, price: product.price, unit: product.unit, image_url: product.image_url, max_per_order: product.max_per_order,
            has_variants: product.has_variants, stock_available: available, variants: variants };
    });
    var categories = mockSortBy(mockCoopRowsByCoop(MockVendor.categories, coop.id).filter(function (category) {
        return category.is_active && used[category.id];
    }), 'sort_order').map(function (category) {
        return { id: category.id, name: category.name };
    });
    return Mock.ok({ categories: categories, products: items });
});

// ---------- Checkout ----------

Mock.register('vendor/pos/checkout', function (body) {
    var ctx = mockPosRequireCashier(body);
    if (ctx.error) {
        return ctx.error;
    }
    var shift = mockPosOpenShift(ctx.coop.id, ctx.user.id);
    if (!shift || shift.id !== Number(body.shift_id)) {
        return Mock.fail(T('pos.error.no_shift'));
    }
    if (['cash', 'online'].indexOf(body.payment_method) === -1) {
        return Mock.fail(T('error.invalid'));
    }
    var priced = mockPosPrice(ctx.coop, body);
    if (priced.error) {
        var errors = {};
        if (priced.field) {
            errors[priced.field] = priced.error;
        }
        return Mock.fail(priced.error, errors);
    }
    var totals = priced.totals;
    var received = parseInt(body.amount_received, 10) || 0;
    if (body.payment_method === 'cash' && received < totals.total) {
        return Mock.fail(T('handover.cash_not_enough'), { amount_received: T('handover.cash_not_enough') });
    }
    if (body.payment_method === 'online' && !mockCoopGateway(ctx.coop.id)) {
        return Mock.fail(T('pos.online_unavailable'));
    }

    var numbers = mockPosNumbers(ctx.coop.id);
    var orderId = Mock.nextId(MockOrders.orders);
    var now = Mock.now();
    var order = {
        id: orderId, vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id, customer_id: null,
        order_no: numbers.order_no, short_code: numbers.short_code, channel: 'pos', payment_method: body.payment_method,
        status: 'pending_payment', payment_status: 'pending',
        subtotal: totals.subtotal, promotion_id: priced.promotion ? priced.promotion.id : null, promotion_discount: totals.promotion_discount,
        manual_discount: totals.manual_discount, discount: totals.discount, tax_rate: priced.tax_rate, tax: totals.tax, total: totals.total,
        pickup_by_type: null, pickup_by_name: '', pickup_code: null, pickup_attempts: 0, pickup_locked: false, pickup_qr_token: null,
        customer_name: String(body.customer_name || '').trim().slice(0, 100), customer_phone: '', customer_email: '',
        expires_at: null, paid_at: null, collected_at: null, created_at: now,
        shift_id: shift.id, cashier_id: ctx.user.id,
        amount_received: body.payment_method === 'cash' ? received : null, change: body.payment_method === 'cash' ? received - totals.total : null,
        gateway_ref: null,
        items: priced.lines.map(function (line, index) {
            return { id: orderId * 100 + index + 1, product_id: line.product.id, variant_id: line.variant ? line.variant.id : 0,
                product_name: line.product.name, variant_label: line.variant ? line.variant.label : null, sku: line.variant ? line.variant.sku : line.product.sku,
                unit_price: line.unit_price, qty: line.qty, line_total: line.line_total, source: 'pos',
                child_id: null, child_name: null, grade_level_code: null, class_name: null, collected_qty: 0 };
        }),
        logs: [{ from_status: null, to_status: 'pending_payment', actor: 'vendor_user', actor_name: ctx.user.full_name, note: T('pos.log_counter'), created_at: now }]
    };
    MockOrders.orders.push(order);

    if (body.payment_method === 'cash') {
        mockPosMarkPaid(order, ctx.user);
        return Mock.ok({ receipt: mockPosReceipt(order), payment: null });
    }

    // Online: tahan stok sehingga gateway mengesahkan bayaran (Peringkat 2: webhook gateway → PAID)
    priced.lines.forEach(function (line) {
        line.level.qty_reserved += line.qty;
        mockPosMove(line.level, 'reserve', 0, order, ctx.user);
    });
    order.gateway_ref = 'POS' + String(500000 + order.id * 31);
    MockPos.paymentPolls[order.id] = 0;
    return Mock.ok({
        receipt: mockPosReceipt(order),
        payment: { reference: order.gateway_ref, gateway_name: mockPosGatewayName(ctx.coop.id),
            qr_payload: 'https://pay.example.my/' + order.gateway_ref, expires_in: 300 }
    });
});

// Semak status bayaran QR (Peringkat 2: pelayan membaca jadual payments yang dikemas kini oleh webhook)
Mock.register('vendor/pos/payment-status', function (params) {
    var ctx = mockPosRequireCashier(params);
    if (ctx.error) {
        return ctx.error;
    }
    var order = mockPosFindSale(params.order_id, ctx.coop.id);
    if (!order) {
        return mockCoopError();
    }
    if (order.status === 'pending_payment') {
        MockPos.paymentPolls[order.id] = (MockPos.paymentPolls[order.id] || 0) + 1;
        if (MockPos.paymentPolls[order.id] >= 2) {
            mockPosMarkPaid(order, ctx.user);
            Mock.saveStores();
        }
    }
    return Mock.ok({ status: order.payment_status === 'paid' ? 'paid' : (order.status === 'cancelled' ? 'cancelled' : 'pending'),
        receipt: mockPosReceipt(order) });
});

Mock.register('vendor/pos/cancel-payment', function (body) {
    var ctx = mockPosRequireCashier(body);
    if (ctx.error) {
        return ctx.error;
    }
    var order = mockPosFindSale(body.order_id, ctx.coop.id);
    if (!order) {
        return mockCoopError();
    }
    if (order.status !== 'pending_payment') {
        return Mock.fail(T('pos.payment_not_pending'));
    }
    order.items.forEach(function (item) {
        var level = mockStockLevel(order.cooperative_id, item.product_id, item.variant_id);
        level.qty_reserved = Math.max(0, level.qty_reserved - item.qty);
        mockPosMove(level, 'release', 0, order, ctx.user);
    });
    order.logs.push({ from_status: order.status, to_status: 'cancelled', actor: 'vendor_user', actor_name: ctx.user.full_name, note: T('pos.payment_cancelled'), created_at: Mock.now() });
    order.status = 'cancelled';
    order.payment_status = 'none';
    order.cancelled_at = Mock.now();
    return Mock.ok({ order_id: order.id });
});

// ---------- Jualan syif & resit ----------

Mock.register('vendor/pos/sales', function (params) {
    var ctx = mockPosRequireCashier(params);
    if (ctx.error) {
        return ctx.error;
    }
    var shift = mockPosFindShift(params.shift_id, ctx.coop.id);
    if (!shift) {
        return mockCoopError();
    }
    var rows = mockPosShiftOrders(shift).sort(function (a, b) {
        return a.created_at < b.created_at ? 1 : -1;
    }).map(function (order) {
        return { order_id: order.id, order_no: order.order_no, created_at: order.created_at, total: order.total, payment_method: order.payment_method,
            status: order.status, payment_status: order.payment_status, customer_name: order.customer_name,
            item_count: order.items.reduce(function (sum, item) { return sum + item.qty; }, 0) };
    });
    return Mock.ok(Mock.paginate(rows, params, ['order_no', 'customer_name']));
});

Mock.register('vendor/pos/receipt', function (params) {
    var ctx = mockPosRequireCashier(params);
    if (ctx.error) {
        return ctx.error;
    }
    var order = mockPosFindSale(params.order_id, ctx.coop.id);
    if (!order) {
        return mockCoopError();
    }
    return Mock.ok(mockPosReceipt(order));
});

Mock.persist('vendor-pos', MockPos, ['sessions', 'pinAttempts', 'shifts', 'paymentPolls']);
