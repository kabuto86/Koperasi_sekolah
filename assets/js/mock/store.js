// Data mock store ibu bapa (U5). Bentuk objek = kontrak API (PELAN 18.12).
// Bergantung pada mock/vendor.js (katalog & stok), vendor-orders.js (ibu bapa, anak, kit, pesanan),
// vendor-finance.js (gateway, promosi, tetapan), vendor-addons.js (modul add-on) dan vendor/pos-cart.js (posTotals) — "pangkalan data" yang sama,
// jadi pesanan dari store terus muncul dalam panel vendor (tab yang sama).
//
// Keselamatan (sama seperti Peringkat 2):
// - Koperasi diselesaikan dari `slug` sahaja; tiada vendor_id / cooperative_id dari input.
// - customer_id datang dari "sesi" (MockStore.session) — tidak pernah dari parameter.
// - Anak, troli & pesanan ditapis customer_id sesi + koperasi slug. Milik orang lain → "tidak dijumpai".
// - Harga, diskaun, stok dikira semula di sini; nilai harga dari pelayar diabaikan.

var MockStore = {
    session: { customer_id: null },
    carts: [],
    cartSeq: 0
};

// Modul add-on yang mempengaruhi store. Data modul dalam mock/vendor-addons.js (MockAddons) — ditulis oleh panel vendor (U6)
var STORE_MODULES = ['news', 'clearance', 'calendar', 'school_opening'];

// ---------- Pembantu: konteks sekolah & sesi ----------

function storeLang() {
    return document.documentElement.lang || 'ms';
}

// Slug → koperasi aktif (Peringkat 2: diselesaikan dari URL /{slug}/ di pelayan)
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

function storeModules(coop) {
    return (MockVendor.activeModules[coop.id] || []).filter(function (code) {
        return STORE_MODULES.indexOf(code) !== -1;
    });
}

function storeChildren(customer, coop) {
    return MockOrders.children.filter(function (child) {
        return child.customer_id === customer.id && child.is_active !== false && (!coop || child.cooperative_id === coop.id);
    });
}

function storeFindChild(customer, coop, childId) {
    return storeChildren(customer, coop).filter(function (child) {
        return child.id === Number(childId);
    })[0] || null;
}

function storeChildPublic(child) {
    var coop = Mock.findById(MockVendor.cooperatives, child.cooperative_id);
    var next = storeNextGrade(child, coop);
    return { id: child.id, full_name: child.full_name, school_slug: coop.slug, school_name: coop.school_name, grade_level_code: child.grade_level_code,
        class_name: child.class_name, school_year: child.school_year, promote_to: next };
}

// Cadangan naik darjah untuk musim pre-order (Okt–Dis) atau tahun lepas
function storeNextGrade(child, coop) {
    var today = mockToday();
    var year = Number(today.slice(0, 4));
    var month = Number(today.slice(5, 7));
    var due = child.school_year < year || (child.school_year === year && month >= 10);
    var grades = mockGradesFor(coop.school_type);
    var index = grades.indexOf(child.grade_level_code);
    if (!due || index === -1 || index === grades.length - 1) {
        return null;
    }
    return { grade_level_code: grades[index + 1], school_year: child.school_year + 1 };
}


// ---------- Pembantu: produk, harga & stok ----------

function storeProduct(coop, id) {
    return storeRows(MockVendor.products, coop).filter(function (product) {
        return product.id === Number(id) && product.is_active;
    })[0] || null;
}

function storeDisplayName(product) {
    var translation = product.translations && product.translations[storeLang()];
    return translation && translation.name ? translation.name : product.name;
}

function storeAvailable(coop, productId, variantId) {
    var level = storeRows(MockVendor.stock, coop).filter(function (row) {
        return row.product_id === Number(productId) && row.variant_id === Number(variantId || 0);
    })[0];
    return level ? Math.max(0, level.qty_on_hand - level.qty_reserved) : 0;
}

function storeStockLevel(coop, productId, variantId) {
    return storeRows(MockVendor.stock, coop).filter(function (row) {
        return row.product_id === Number(productId) && row.variant_id === Number(variantId || 0);
    })[0] || null;
}

function storeActiveVariants(product) {
    return product.variants.filter(function (variant) { return variant.is_active; });
}

// Jualan murah aktif (modul clearance) untuk produk + varian ini
function storeClearanceItem(coop, product, variantId) {
    if (storeModules(coop).indexOf('clearance') === -1) {
        return null;
    }
    var now = Mock.now();
    var sales = storeRows(MockAddons.clearanceSales, coop).filter(function (sale) {
        return sale.is_active && sale.starts_at <= now && sale.ends_at >= now;
    }).map(function (sale) { return sale.id; });
    return MockAddons.clearanceItems.filter(function (item) {
        return item.vendor_id === coop.vendor_id && sales.indexOf(item.clearance_sale_id) !== -1 && item.product_id === product.id
            && (item.variant_id === null || item.variant_id === Number(variantId || 0));
    })[0] || null;
}

// Harga seunit di pelayan: harga jualan murah jika ada, jika tidak harga + beza varian
function storeUnitPrice(coop, product, variant) {
    var regular = posUnitPrice(product, variant);
    var clearance = storeClearanceItem(coop, product, variant ? variant.id : 0);
    return { price: clearance ? Math.min(regular, clearance.sale_price) : regular, regular: regular,
        clearance_item_id: clearance ? clearance.id : null, qty_limit: clearance ? clearance.qty_limit : null };
}

// Had beli setiap pesanan (produk atau jualan murah)
function storeMaxQty(product, pricing) {
    var limits = [product.max_per_order, pricing.qty_limit].filter(function (n) { return n; });
    return limits.length ? Math.min.apply(null, limits) : null;
}

function storeProductCard(coop, product) {
    var variants = storeActiveVariants(product);
    var prices = (product.has_variants ? variants : [null]).map(function (variant) {
        return storeUnitPrice(coop, product, variant);
    });
    var lowest = prices.reduce(function (min, row) { return !min || row.price < min.price ? row : min; }, null);
    var available = product.has_variants
        ? variants.reduce(function (sum, variant) { return sum + storeAvailable(coop, product.id, variant.id); }, 0)
        : storeAvailable(coop, product.id, 0);
    return { id: product.id, name: storeDisplayName(product), category_id: product.category_id, category_name: mockCategoryName(product.category_id),
        price: lowest.price, regular_price: lowest.regular, price_from: product.has_variants && prices.some(function (row) { return row.price !== lowest.price; }),
        on_sale: prices.some(function (row) { return row.clearance_item_id; }), unit: product.unit, image_url: product.image_url,
        has_variants: product.has_variants, available: available, grade_levels: product.grade_levels };
}

function storeProductDetail(coop, product) {
    var data = storeProductCard(coop, product);
    var basePricing = storeUnitPrice(coop, product, null);
    data.description = (product.translations[storeLang()] || {}).description || product.description;
    data.sku = product.sku;
    data.max_per_order = storeMaxQty(product, basePricing);
    data.variants = storeActiveVariants(product).map(function (variant) {
        var pricing = storeUnitPrice(coop, product, variant);
        return { id: variant.id, label: variant.label, price: pricing.price, regular_price: pricing.regular,
            available: storeAvailable(coop, product.id, variant.id), max_per_order: storeMaxQty(product, pricing) };
    });
    return data;
}

// ---------- Troli ----------

function storeCart(customer, coop, create) {
    var cart = MockStore.carts.filter(function (row) {
        return row.customer_id === customer.id && row.cooperative_id === coop.id;
    })[0] || null;
    if (!cart && create) {
        cart = { id: Mock.nextId(MockStore.carts), customer_id: customer.id, cooperative_id: coop.id, vendor_id: coop.vendor_id, items: [], updated_at: Mock.now() };
        MockStore.carts.push(cart);
    }
    return cart;
}

function storeCartCount(customer, coop) {
    var cart = customer && coop ? storeCart(customer, coop, false) : null;
    return cart ? cart.items.reduce(function (sum, item) { return sum + item.qty; }, 0) : 0;
}

// Kira semula setiap baris troli: harga semasa, stok, had beli. Baris bermasalah ditanda `problem`
function storePriceCart(customer, coop, cart) {
    var needed = {};
    var lines = (cart ? cart.items : []).map(function (item) {
        var product = storeProduct(coop, item.product_id);
        var child = storeFindChild(customer, coop, item.child_id);
        var variant = product && item.variant_id ? mockFindVariant(product, item.variant_id) : null;
        var line = { id: item.id, product_id: item.product_id, variant_id: item.variant_id || 0, child_id: item.child_id, qty: item.qty,
            source: item.source, school_kit_id: item.school_kit_id || null, product: product, variant: variant, child: child, problem: null };
        if (!product || !child || (product.has_variants && (!variant || !variant.is_active))) {
            line.problem = 'unavailable';
            line.unit_price = 0;
            return line;
        }
        var pricing = storeUnitPrice(coop, product, variant);
        var key = product.id + '#' + (variant ? variant.id : 0);
        needed[key] = (needed[key] || 0) + item.qty;
        line.key = key;
        line.unit_price = pricing.price;
        line.regular_price = pricing.regular;
        line.clearance_item_id = pricing.clearance_item_id;
        line.max = storeMaxQty(product, pricing);
        line.available = storeAvailable(coop, product.id, variant ? variant.id : 0);
        return line;
    });
    lines.forEach(function (line) {
        if (line.problem) {
            return;
        }
        if (needed[line.key] > line.available) {
            line.problem = 'stock';
        } else if (line.max && needed[line.key] > line.max) {
            line.problem = 'max';
        }
    });
    return lines;
}

function storeLinePublic(line) {
    return { id: line.id, product_id: line.product_id, variant_id: line.variant_id, name: line.product ? storeDisplayName(line.product) : T('store.cart.removed_product'),
        variant_label: line.variant ? line.variant.label : null, image_url: line.product ? line.product.image_url : null,
        unit_price: line.unit_price, regular_price: line.regular_price || line.unit_price, qty: line.qty, line_total: line.unit_price * line.qty,
        source: line.source, school_kit_id: line.school_kit_id, available: line.available || 0, max: line.max || null, problem: line.problem };
}

// Kumpul baris ikut anak (PELAN 5.4: setiap item ditanda untuk anak mana)
function storeGroupLines(lines) {
    var groups = [];
    lines.forEach(function (line) {
        var group = groups.filter(function (row) { return row.child_id === line.child_id; })[0];
        if (!group) {
            group = { child_id: line.child_id, child_name: line.child ? line.child.full_name : '', grade_level_code: line.child ? line.child.grade_level_code : '',
                class_name: line.child ? line.child.class_name : '', items: [], subtotal: 0 };
            groups.push(group);
        }
        var row = storeLinePublic(line);
        group.items.push(row);
        group.subtotal += row.line_total;
    });
    return groups;
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

// Ringkasan troli + diskaun + cukai (sama seperti POS: satu promosi setiap pesanan, cukai selepas diskaun)
function storeQuote(customer, coop, couponCode) {
    var cart = storeCart(customer, coop, false);
    var lines = storePriceCart(customer, coop, cart);
    var valid = lines.filter(function (line) { return !line.problem; });
    var subtotal = valid.reduce(function (sum, line) { return sum + line.unit_price * line.qty; }, 0);
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
    var totals = posTotals(valid, { promotion: promotion, taxRate: taxRate });
    var settings = MockOrders.pickupSettings[coop.id];
    var slots = storeSlotsFor(coop, valid);
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
            groups: storeGroupLines(lines), item_count: valid.reduce(function (sum, line) { return sum + line.qty; }, 0),
            has_problem: lines.some(function (line) { return line.problem; }),
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

// ---------- Sekolah ----------

function storeSchoolPublic(coop) {
    return { slug: coop.slug, school_name: coop.school_name, school_type: coop.school_type, state: coop.state, district: coop.district };
}

Mock.register('store/schools/list', function (params) {
    var rows = MockVendor.cooperatives.filter(function (coop) {
        return coop.status === 'active' && (!params.state || coop.state === params.state);
    }).map(storeSchoolPublic).sort(function (a, b) { return a.school_name < b.school_name ? -1 : 1; });
    return Mock.ok(Mock.paginate(rows, params, ['school_name', 'district', 'state']));
});

Mock.register('store/school', function (params) {
    var coop = storeCoop(params);
    if (!coop) {
        return storeSchoolError();
    }
    var data = storeSchoolPublic(coop);
    data.address = coop.address;
    data.phone = coop.phone;
    data.logo_url = coop.logo_url;
    data.default_language = coop.default_language;
    data.opening_hours = coop.opening_hours;
    data.grades = mockGradesFor(coop.school_type);
    data.modules = storeModules(coop);
    data.payment_methods = (coop.accept_cash ? ['cash'] : []).concat(coop.accept_online ? ['online'] : []);
    data.notices = storeNotices(coop);
    return Mock.ok(data);
});

// Banner store: koperasi tutup (modul calendar) & musim pre-order (modul school_opening)
function storeNotices(coop) {
    var closure = addonUpcomingClosure(coop);
    var season = addonOpenSeason(coop);
    var today = mockToday();
    return {
        closure: closure ? { title: closure.title, starts_on: closure.starts_on, ends_on: closure.ends_on, is_today: closure.starts_on <= today,
            reopens_on: addonNextDay(closure.ends_on) } : null,
        preorder: season ? { school_year: season.school_year, closes_at: season.closes_at, pickup_starts_on: season.pickup_starts_on, note: season.note,
            has_slots: addonSeasonSlots(season).length > 0 } : null
    };
}

// Slot ambil untuk troli ini (darjah anak-anak dalam troli)
function storeSlotsFor(coop, lines) {
    var grades = [];
    lines.forEach(function (line) {
        if (line.child && grades.indexOf(line.child.grade_level_code) === -1) {
            grades.push(line.child.grade_level_code);
        }
    });
    return addonSlotsFor(coop, grades).map(addonSlotRow);
}

Mock.register('store/classes', function (params) {
    var coop = storeCoop(params);
    if (!coop) {
        return storeSchoolError();
    }
    var rows = storeRows(MockVendor.classes, coop).filter(function (row) {
        return !params.grade || row.grade_level_code === params.grade;
    }).sort(function (a, b) { return a.sort_order - b.sort_order; });
    return Mock.ok(rows.map(function (row) { return { grade_level_code: row.grade_level_code, class_name: row.class_name }; }));
});

// store/session ialah laluan sebenar sejak B2 (ibu bapa dari sesi pelayan). Sementara troli & anak masih mock
// (sehingga B6), bilangan troli & sekolah anak-anak dalam jawapan diganti dengan nilai mock — lihat storeAdoptSession
function storeMockSessionExtras(params) {
    var customer = storeCustomer();
    var coop = storeCoop(params);
    if (!customer) {
        return { cart_count: 0, schools: [] };
    }
    var schools = [];
    storeChildren(customer, null).forEach(function (child) {
        var school = Mock.findById(MockVendor.cooperatives, child.cooperative_id);
        if (school.status === 'active' && !schools.some(function (row) { return row.slug === school.slug; })) {
            schools.push(storeSchoolPublic(school));
        }
    });
    return { cart_count: storeCartCount(customer, coop), schools: schools };
}

// Serasi dengan U1
Mock.register('store/cart/summary', function (params) {
    var customer = storeCustomer();
    var coop = storeCoop(params);
    return Mock.ok({ item_count: storeCartCount(customer, coop) });
});

// ---------- Akaun & profil: laluan sebenar sejak B2 (api/v1/store/auth|profile) ----------

// Peralihan B2–B6: anak, troli & pesanan masih mock. Selepas log masuk / daftar SEBENAR, sesi mock mengikut
// ibu bapa yang sama (dipadankan ikut e-mel; dicipta dalam mock jika belum ada). Dibuang bersama mock dalam B6.
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

// Jawapan store/session sebenar: sesi mock ikut ibu bapa sebenar; troli & sekolah anak dari mock (sehingga B6)
function storeAdoptSession(res) {
    if (!res.ok) {
        return;
    }
    if (res.data.customer) {
        storeAdoptCustomer(res);
    } else {
        MockStore.session.customer_id = null;
    }
    var slug = new URLSearchParams(window.location.search).get('school') || '';
    Object.assign(res.data, storeMockSessionExtras({ slug: slug }));
}

Mock.onLive('store/session', storeAdoptSession);
Mock.onLive('store/auth/login', storeAdoptCustomer);
Mock.onLive('store/auth/register', storeAdoptCustomer);
Mock.onLive('store/auth/logout', storeForgetCustomer);
Mock.onLive('store/profile/delete', storeForgetCustomer);
// Setiap halaman: sesi sebenar ialah sumber kebenaran (tab baharu / sesi tamat / log keluar di tab lain)
Mock.beforeFirst(function () {
    return Api.fetchJson('GET', 'store/session', {}).then(function (res) {
        storeAdoptSession(res);
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

// ---------- Anak ----------

Mock.register('store/children/list', function () {
    var customer = storeCustomer();
    if (!customer) {
        return storeAuthFail();
    }
    return Mock.ok(storeChildren(customer, null).map(storeChildPublic));
});

Mock.register('store/children/save', function (body) {
    var customer = storeCustomer();
    if (!customer) {
        return storeAuthFail();
    }
    var errors = Mock.requireFields(body, ['full_name', 'school_slug', 'grade_level_code', 'class_name', 'school_year']);
    var coop = storeCoop({ slug: body.school_slug });
    if (body.school_slug && !coop) {
        errors.school_slug = T('store.school_not_found');
    }
    if (coop && mockGradesFor(coop.school_type).indexOf(body.grade_level_code) === -1) {
        errors.grade_level_code = T('error.invalid');
    }
    var year = Number(mockToday().slice(0, 4));
    if (body.school_year && [year, year + 1].indexOf(Number(body.school_year)) === -1) {
        errors.school_year = T('error.invalid');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var child = null;
    if (body.id) {
        child = storeFindChild(customer, null, body.id);
        if (!child) {
            return Mock.fail(T('error.not_found'));
        }
    } else {
        child = { id: Mock.nextId(MockOrders.children), customer_id: customer.id, is_active: true };
        MockOrders.children.push(child);
    }
    var classRow = storeRows(MockVendor.classes, coop).filter(function (row) {
        return row.grade_level_code === body.grade_level_code && row.class_name.toLowerCase() === String(body.class_name).trim().toLowerCase();
    })[0];
    child.cooperative_id = coop.id;
    child.vendor_id = coop.vendor_id;
    child.full_name = String(body.full_name).trim();
    child.grade_level_code = body.grade_level_code;
    child.school_class_id = classRow ? classRow.id : null;
    child.class_name = classRow ? classRow.class_name : String(body.class_name).trim();
    child.school_year = Number(body.school_year);
    return Mock.ok(storeChildPublic(child));
});

Mock.register('store/children/remove', function (body) {
    var customer = storeCustomer();
    if (!customer) {
        return storeAuthFail();
    }
    var child = storeFindChild(customer, null, body.id);
    if (!child) {
        return Mock.fail(T('error.not_found'));
    }
    child.is_active = false;
    MockStore.carts.forEach(function (cart) {
        if (cart.customer_id === customer.id) {
            cart.items = cart.items.filter(function (item) { return item.child_id !== child.id; });
        }
    });
    return Mock.ok({ id: child.id });
});

Mock.register('store/children/promote', function (body) {
    var customer = storeCustomer();
    if (!customer) {
        return storeAuthFail();
    }
    var child = storeFindChild(customer, null, body.id);
    if (!child) {
        return Mock.fail(T('error.not_found'));
    }
    var next = storeNextGrade(child, Mock.findById(MockVendor.cooperatives, child.cooperative_id));
    if (!next) {
        return Mock.fail(T('store.children.promote_unavailable'));
    }
    child.grade_level_code = next.grade_level_code;
    child.school_year = next.school_year;
    child.school_class_id = null;
    child.class_name = String(body.class_name || '').trim() || child.class_name;
    return Mock.ok(storeChildPublic(child));
});

// ---------- Katalog & produk ----------

Mock.register('store/catalog', function (params) {
    var coop = storeCoop(params);
    if (!coop) {
        return storeSchoolError();
    }
    var q = String(params.q || '').trim().toLowerCase();
    var rows = storeRows(MockVendor.products, coop).filter(function (product) {
        var category = Mock.findById(MockVendor.categories, product.category_id);
        return product.is_active && category && category.is_active
            && (!params.category_id || product.category_id === Number(params.category_id))
            && (!params.grade || product.grade_levels.length === 0 || product.grade_levels.indexOf(params.grade) !== -1)
            && (!q || [product.name, storeDisplayName(product), product.sku].some(function (text) { return text.toLowerCase().indexOf(q) !== -1; }));
    }).sort(function (a, b) { return a.sort_order - b.sort_order; }).map(function (product) {
        return storeProductCard(coop, product);
    });
    var data = Mock.paginate(rows, { page: params.page, per_page: params.per_page || 24 }, []);
    data.categories = storeRows(MockVendor.categories, coop).filter(function (row) { return row.is_active; })
        .sort(function (a, b) { return a.sort_order - b.sort_order; }).map(function (row) { return { id: row.id, name: row.name }; });
    return Mock.ok(data);
});

Mock.register('store/product', function (params) {
    var coop = storeCoop(params);
    if (!coop) {
        return storeSchoolError();
    }
    var product = storeProduct(coop, params.id);
    if (!product) {
        return Mock.fail(T('error.not_found'));
    }
    return Mock.ok(storeProductDetail(coop, product));
});

// ---------- Kit sekolah ----------

function storePublishedKit(coop, child) {
    return storeRows(MockOrders.kits, coop).filter(function (kit) {
        return kit.is_published && kit.grade_level_code === child.grade_level_code && kit.school_year === child.school_year;
    })[0] || null;
}

Mock.register('store/kit', function (params) {
    var ctx = storeContext(params);
    if (ctx.error) {
        return ctx.error;
    }
    var children = storeChildren(ctx.customer, ctx.coop).map(storeChildPublic);
    var child = params.child_id ? storeFindChild(ctx.customer, ctx.coop, params.child_id) : null;
    if (params.child_id && !child) {
        return Mock.fail(T('error.not_found'));
    }
    if (!child) {
        return Mock.ok({ children: children, child: null, kit: null });
    }
    var kit = storePublishedKit(ctx.coop, child);
    if (!kit) {
        return Mock.ok({ children: children, child: storeChildPublic(child), kit: null });
    }
    var cart = storeCart(ctx.customer, ctx.coop, false);
    var cartLines = cart ? cart.items.filter(function (item) { return item.school_kit_id === kit.id && item.child_id === child.id; }) : [];
    var items = kit.items.slice().sort(function (a, b) { return a.sort_order - b.sort_order; }).map(function (item) {
        var product = storeProduct(ctx.coop, item.product_id);
        if (!product) {
            return null;
        }
        var fixed = item.variant_id ? mockFindVariant(product, item.variant_id) : null;
        var pricing = storeUnitPrice(ctx.coop, product, fixed);
        var inCart = cartLines.filter(function (line) { return line.school_kit_item_id === item.id; })[0] || null;
        return {
            id: item.id, product_id: product.id, name: storeDisplayName(product), image_url: product.image_url, unit: product.unit,
            variant_id: fixed ? fixed.id : null, variant_label: fixed ? fixed.label : null,
            choose_variant: product.has_variants && !fixed,
            variants: product.has_variants && !fixed ? storeActiveVariants(product).map(function (variant) {
                return { id: variant.id, label: variant.label, price: storeUnitPrice(ctx.coop, product, variant).price, available: storeAvailable(ctx.coop, product.id, variant.id) };
            }) : [],
            unit_price: pricing.price, qty: item.qty, is_mandatory: item.is_mandatory, note: item.note,
            available: product.has_variants && !fixed ? null : storeAvailable(ctx.coop, product.id, fixed ? fixed.id : 0),
            max_per_order: storeMaxQty(product, pricing),
            in_cart: inCart ? { qty: inCart.qty, variant_id: inCart.variant_id || null } : null
        };
    }).filter(Boolean);
    return Mock.ok({
        children: children, child: storeChildPublic(child),
        kit: { id: kit.id, name: kit.name, note: kit.note, grade_level_code: kit.grade_level_code, school_year: kit.school_year, items: items, in_cart: cartLines.length > 0 }
    });
});

// ---------- Troli ----------

Mock.register('store/cart/get', function (params) {
    var ctx = storeContext(params);
    if (ctx.error) {
        return ctx.error;
    }
    return Mock.ok(storeQuote(ctx.customer, ctx.coop, '').data);
});

function storeValidQty(qty) {
    var n = parseInt(qty, 10);
    return n >= 1 && n <= 99 ? n : null;
}

Mock.register('store/cart/add', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var product = storeProduct(ctx.coop, body.product_id);
    if (!product) {
        return Mock.fail(T('error.not_found'));
    }
    var child = storeFindChild(ctx.customer, ctx.coop, body.child_id);
    if (!child) {
        return Mock.fail(T('store.cart.choose_child'), { child_id: T('store.cart.choose_child') });
    }
    var variant = body.variant_id ? mockFindVariant(product, body.variant_id) : null;
    if (product.has_variants && (!variant || !variant.is_active)) {
        return Mock.fail(T('pos.error.choose_variant', { name: storeDisplayName(product) }), { variant_id: T('error.required') });
    }
    var qty = storeValidQty(body.qty);
    if (!qty) {
        return Mock.fail(T('error.range', { min: 1, max: 99 }), { qty: T('error.range', { min: 1, max: 99 }) });
    }
    var cart = storeCart(ctx.customer, ctx.coop, true);
    var variantId = variant ? variant.id : 0;
    var existing = cart.items.filter(function (item) {
        return item.product_id === product.id && (item.variant_id || 0) === variantId && item.child_id === child.id && item.source === 'ala_carte';
    })[0];
    var already = cart.items.filter(function (item) { return item.product_id === product.id && (item.variant_id || 0) === variantId; })
        .reduce(function (sum, item) { return sum + item.qty; }, 0);
    var available = storeAvailable(ctx.coop, product.id, variantId);
    if (already + qty > available) {
        return Mock.fail(T('pos.error.stock', { name: storeDisplayName(product) + (variant ? ' — ' + variant.label : ''), n: Math.max(0, available - already) }));
    }
    var max = storeMaxQty(product, storeUnitPrice(ctx.coop, product, variant));
    if (max && already + qty > max) {
        return Mock.fail(T('store.cart.max_per_order', { name: storeDisplayName(product), n: max }));
    }
    if (existing) {
        existing.qty += qty;
    } else {
        cart.items.push({ id: ++MockStore.cartSeq, product_id: product.id, variant_id: variantId,
            child_id: child.id, qty: qty, source: 'ala_carte', school_kit_id: null, school_kit_item_id: null });
    }
    cart.updated_at = Mock.now();
    return Mock.ok({ cart_count: storeCartCount(ctx.customer, ctx.coop), child_name: child.full_name });
});

// Masukkan kit (item yang ditanda sahaja). Kit yang sama untuk anak yang sama → diganti
Mock.register('store/cart/add-kit', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var child = storeFindChild(ctx.customer, ctx.coop, body.child_id);
    if (!child) {
        return Mock.fail(T('error.not_found'));
    }
    var kit = storePublishedKit(ctx.coop, child);
    if (!kit || kit.id !== Number(body.kit_id)) {
        return Mock.fail(T('store.kit.not_available'));
    }
    var picked = body.items || [];
    if (picked.length === 0) {
        return Mock.fail(T('store.kit.nothing_selected'));
    }
    var cart = storeCart(ctx.customer, ctx.coop, true);
    var others = cart.items.filter(function (item) { return !(item.school_kit_id === kit.id && item.child_id === child.id); });
    var lines = [];
    var error = null;
    var errors = {};
    picked.forEach(function (row) {
        if (error) {
            return;
        }
        var kitItem = kit.items.filter(function (item) { return item.id === Number(row.kit_item_id); })[0];
        var product = kitItem ? storeProduct(ctx.coop, kitItem.product_id) : null;
        var qty = storeValidQty(row.qty);
        if (!kitItem || !product || !qty) {
            error = T('error.invalid');
            return;
        }
        var variant = kitItem.variant_id ? mockFindVariant(product, kitItem.variant_id) : (row.variant_id ? mockFindVariant(product, row.variant_id) : null);
        if (product.has_variants && (!variant || !variant.is_active)) {
            error = T('pos.error.choose_variant', { name: storeDisplayName(product) });
            errors['variant_' + kitItem.id] = error;
            return;
        }
        var variantId = variant ? variant.id : 0;
        var already = others.concat(lines).filter(function (item) { return item.product_id === product.id && (item.variant_id || 0) === variantId; })
            .reduce(function (sum, item) { return sum + item.qty; }, 0);
        var available = storeAvailable(ctx.coop, product.id, variantId);
        if (already + qty > available) {
            error = T('pos.error.stock', { name: storeDisplayName(product) + (variant ? ' — ' + variant.label : ''), n: Math.max(0, available - already) });
            errors['qty_' + kitItem.id] = error;
            return;
        }
        lines.push({ product_id: product.id, variant_id: variantId, child_id: child.id, qty: qty, source: 'kit', school_kit_id: kit.id, school_kit_item_id: kitItem.id });
    });
    if (error) {
        return Mock.fail(error, errors);
    }
    lines.forEach(function (line) {
        line.id = ++MockStore.cartSeq;
    });
    cart.items = others.concat(lines);
    cart.updated_at = Mock.now();
    return Mock.ok({ cart_count: storeCartCount(ctx.customer, ctx.coop), added: lines.length, child_name: child.full_name });
});

function storeCartItem(ctx, id) {
    var cart = storeCart(ctx.customer, ctx.coop, false);
    var item = cart ? cart.items.filter(function (row) { return row.id === Number(id); })[0] : null;
    return item ? { cart: cart, item: item } : null;
}

Mock.register('store/cart/update', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var found = storeCartItem(ctx, body.id);
    var qty = storeValidQty(body.qty);
    if (!found) {
        return Mock.fail(T('error.not_found'));
    }
    if (!qty) {
        return Mock.fail(T('error.range', { min: 1, max: 99 }));
    }
    found.item.qty = qty;
    found.cart.updated_at = Mock.now();
    return Mock.ok(storeQuote(ctx.customer, ctx.coop, '').data);
});

Mock.register('store/cart/remove', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var found = storeCartItem(ctx, body.id);
    if (!found) {
        return Mock.fail(T('error.not_found'));
    }
    found.cart.items = found.cart.items.filter(function (row) { return row.id !== found.item.id; });
    return Mock.ok(storeQuote(ctx.customer, ctx.coop, '').data);
});

Mock.register('store/cart/clear', function (body) {
    var ctx = storeContext(body);
    if (ctx.error) {
        return ctx.error;
    }
    var cart = storeCart(ctx.customer, ctx.coop, false);
    if (cart) {
        cart.items = [];
    }
    return Mock.ok(storeQuote(ctx.customer, ctx.coop, '').data);
});

// ---------- Checkout ----------

Mock.register('store/checkout/quote', function (params) {
    var ctx = storeContext(params);
    if (ctx.error) {
        return ctx.error;
    }
    var quote = storeQuote(ctx.customer, ctx.coop, params.coupon_code);
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
        items: lines.map(function (line, index) {
            return { id: orderId * 100 + index + 1, product_id: line.product.id, variant_id: line.variant ? line.variant.id : 0,
                product_name: line.product.name, variant_label: line.variant ? line.variant.label : null, sku: line.variant ? line.variant.sku : line.product.sku,
                unit_price: line.unit_price, qty: line.qty, line_total: line.unit_price * line.qty, source: line.source,
                school_kit_id: line.school_kit_id, clearance_item_id: line.clearance_item_id || null,
                child_id: line.child.id, child_name: line.child.full_name, grade_level_code: line.child.grade_level_code, class_name: line.child.class_name,
                collected_qty: 0, cancelled_qty: 0, returned_qty: 0 };
        }),
        logs: [{ from_status: null, to_status: status, actor: 'customer', actor_name: ctx.customer.full_name, note: '', created_at: now }]
    };
    // Murid sendiri: nama anak (seorang) atau senarai nama anak
    if (body.pickup_by_type === 'student') {
        order.pickup_by_name = storeGroupLines(lines).map(function (group) { return group.child_name; }).join(', ');
    }
    MockOrders.orders.push(order);
    storeReserve(ctx.coop, order, 'reserve');
    if (slot) {
        slot.booked++;
    }
    if (quote.promotion) {
        quote.promotion.used_count++;
    }
    storeCart(ctx.customer, ctx.coop, false).items = [];
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
    data.children = storeChildren(ctx.customer, ctx.coop).map(function (child) { return { id: child.id, full_name: child.full_name }; });
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
    storeReserve(ctx.coop, order, 'release');
    addonReleaseSlot(order);
    order.logs.push({ from_status: order.status, to_status: 'cancelled', actor: 'customer', actor_name: ctx.customer.full_name, note: T('store.order.cancelled_by_parent'), created_at: Mock.now() });
    order.status = 'cancelled';
    order.payment_status = 'none';
    order.cancelled_at = Mock.now();
    return Mock.ok(storeOrderDetail(ctx, order));
});

// ---------- Modul add-on: berita & jualan murah ----------

Mock.register('store/news/list', function (params) {
    var coop = storeCoop(params);
    if (!coop) {
        return storeSchoolError();
    }
    if (storeModules(coop).indexOf('news') === -1) {
        return Mock.fail(T('store.module_unavailable'));
    }
    var rows = storeRows(MockAddons.news, coop).filter(function (post) {
        return post.is_active && post.published_at <= Mock.now();
    }).sort(function (a, b) { return a.published_at < b.published_at ? 1 : -1; }).map(function (post) {
        return { id: post.id, title: post.title, body: post.body, image_url: post.image_url, published_at: post.published_at };
    });
    return Mock.ok(Mock.paginate(rows, params, []));
});

Mock.register('store/clearance/list', function (params) {
    var coop = storeCoop(params);
    if (!coop) {
        return storeSchoolError();
    }
    if (storeModules(coop).indexOf('clearance') === -1) {
        return Mock.fail(T('store.module_unavailable'));
    }
    var now = Mock.now();
    var sales = storeRows(MockAddons.clearanceSales, coop).filter(function (sale) {
        return sale.is_active && sale.starts_at <= now && sale.ends_at >= now;
    });
    return Mock.ok(sales.map(function (sale) {
        return { id: sale.id, name: sale.name, ends_at: sale.ends_at,
            items: MockAddons.clearanceItems.filter(function (item) { return item.clearance_sale_id === sale.id && item.vendor_id === coop.vendor_id; })
                .map(function (item) {
                    var product = storeProduct(coop, item.product_id);
                    if (!product) {
                        return null;
                    }
                    var variant = item.variant_id ? mockFindVariant(product, item.variant_id) : null;
                    var regular = posUnitPrice(product, variant);
                    return { product_id: product.id, name: storeDisplayName(product), variant_label: variant ? variant.label : null, image_url: product.image_url,
                        regular_price: regular, sale_price: Math.min(regular, item.sale_price), qty_limit: item.qty_limit,
                        available: product.has_variants && !variant ? null : storeAvailable(coop, product.id, variant ? variant.id : 0) };
                }).filter(Boolean)
        };
    }));
});

// Kekalkan sesi, troli & token antara halaman (sessionStorage) — Peringkat 2: sesi PHP & jadual carts
Mock.persist('store', MockStore, ['session', 'carts', 'cartSeq']);
