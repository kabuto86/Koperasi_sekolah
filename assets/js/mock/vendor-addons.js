// Data mock modul add-on (U6): berita, jualan murah, kalendar cuti, pembukaan sekolah, WhatsApp/SMS.
// Dikongsi panel vendor DAN store ibu bapa (dimuat sebelum store.js) — "pangkalan data" yang sama,
// jadi berita / jualan murah / cuti / slot yang dicipta vendor terus kelihatan di store.
// Bergantung pada mock/vendor.js. (vendor-finance.js dibuang dalam B12 — langganan, invois & log aktiviti kini sebenar.)
//
// Keselamatan (sama seperti Peringkat 2 — ModuleAccess::has($cooperativeId, $code)):
// - Setiap laluan vendor mengesahkan koperasi milik vendor sesi DAN modul dilanggan koperasi itu.
// - vendor_id rekod baharu datang dari sesi, tidak pernah dari input.

var MockAddons = {
    // news_posts — image_url (Peringkat 2: image_path). email_* diisi bila e-mel dimasukkan ke email_queue
    news: [
        { id: 1, vendor_id: 1, cooperative_id: 1, title: 'Pre-order kit sekolah 2027 kini dibuka',
          body: 'Ibu bapa boleh membuat tempahan kit Darjah 1 hingga 6 untuk sesi 2027. Barang boleh diambil mulai 2 Januari 2027 di koperasi sekolah. Sila pilih slot ambil semasa checkout.',
          image_url: null, published_at: '2026-10-20T09:00:00+08:00', is_active: true, send_email: true, email_sent_at: '2026-10-20T09:00:00+08:00', email_recipients: 14 },
        { id: 2, vendor_id: 1, cooperative_id: 1, title: 'Waktu operasi koperasi semasa cuti sekolah',
          body: 'Koperasi ditutup sepanjang cuti akhir tahun (19 Disember – 1 Januari). Pesanan online masih boleh dibuat dan diambil mengikut slot yang dipilih.',
          image_url: null, published_at: '2026-10-12T10:30:00+08:00', is_active: true, send_email: false, email_sent_at: null, email_recipients: 0 },
        { id: 3, vendor_id: 1, cooperative_id: 1, title: 'Stok baju sekolah saiz L & XL telah tiba',
          body: 'Stok baru telah tiba minggu ini. Sila tempah awal kerana kuantiti terhad.',
          image_url: null, published_at: '2026-09-28T08:00:00+08:00', is_active: true, send_email: false, email_sent_at: null, email_recipients: 0 },
        { id: 4, vendor_id: 1, cooperative_id: 1, title: 'Hari Sukan Tahunan — koperasi dibuka separuh hari',
          body: 'Koperasi dibuka 7.30 pagi hingga 11.00 pagi pada Hari Sukan Tahunan.',
          image_url: null, published_at: '2026-11-20T08:00:00+08:00', is_active: true, send_email: true, email_sent_at: null, email_recipients: 0 },
        { id: 5, vendor_id: 2, cooperative_id: 3, title: 'Hari Terbuka koperasi',
          body: 'Modul berita tidak aktif untuk koperasi ini — berita ini tidak dipaparkan.',
          image_url: null, published_at: '2026-10-01T08:00:00+08:00', is_active: true, send_email: false, email_sent_at: null, email_recipients: 0 }
    ],

    // clearance_sales + clearance_items (starts_at/ends_at ISO; UI menghantar tarikh sahaja)
    clearanceSales: [
        { id: 1, vendor_id: 2, cooperative_id: 3, name: 'Jualan murah hujung tahun', starts_at: '2026-10-01T00:00:00+08:00', ends_at: '2026-12-31T23:59:59+08:00', is_active: true },
        { id: 2, vendor_id: 1, cooperative_id: 1, name: 'Jualan stok lama 2026', starts_at: '2026-10-01T00:00:00+08:00', ends_at: '2026-12-31T23:59:59+08:00', is_active: true }
    ],
    clearanceItems: [
        { id: 1, vendor_id: 2, clearance_sale_id: 1, product_id: 24, variant_id: null, sale_price: 4500, qty_limit: 1 },
        { id: 2, vendor_id: 2, clearance_sale_id: 1, product_id: 23, variant_id: 17, sale_price: 3900, qty_limit: 2 },
        { id: 3, vendor_id: 1, clearance_sale_id: 2, product_id: 17, variant_id: null, sale_price: 5900, qty_limit: 1 }
    ],

    // calendar_events — blocks_orders: tempahan online ditutup pada hari itu
    calendarEvents: [
        { id: 1, vendor_id: 1, cooperative_id: 1, title: 'Cuti Deepavali', event_type: 'coop_closed', starts_on: '2026-11-08', ends_on: '2026-11-09', blocks_orders: true },
        { id: 2, vendor_id: 1, cooperative_id: 1, title: 'Hari Sukan Tahunan', event_type: 'other', starts_on: '2026-11-21', ends_on: '2026-11-21', blocks_orders: false },
        { id: 3, vendor_id: 1, cooperative_id: 1, title: 'Cuti akhir tahun persekolahan', event_type: 'school_holiday', starts_on: '2026-12-19', ends_on: '2027-01-01', blocks_orders: false },
        { id: 4, vendor_id: 1, cooperative_id: 1, title: 'Koperasi tutup — stock take tahunan', event_type: 'coop_closed', starts_on: '2026-12-16', ends_on: '2026-12-18', blocks_orders: true },
        { id: 5, vendor_id: 1, cooperative_id: 1, title: 'Hari pertama persekolahan 2027', event_type: 'school_opening', starts_on: '2027-01-04', ends_on: '2027-01-04', blocks_orders: false },
        { id: 6, vendor_id: 2, cooperative_id: 3, title: 'Cuti koperasi VENDOR02', event_type: 'coop_closed', starts_on: '2026-11-01', ends_on: '2026-11-30', blocks_orders: true }
    ],

    // preorder_seasons + pickup_slots (grade_level_code null = semua darjah)
    seasons: [
        { id: 1, vendor_id: 1, cooperative_id: 1, school_year: 2027, opens_at: '2026-10-01', closes_at: '2026-12-15', pickup_starts_on: '2027-01-02',
          note: 'Kit Darjah 1–6 sesi 2027. Ambil ikut slot yang dipilih.' },
        { id: 2, vendor_id: 2, cooperative_id: 3, school_year: 2027, opens_at: '2026-10-01', closes_at: '2026-12-20', pickup_starts_on: '2027-01-02', note: '' }
    ],
    slots: [
        { id: 1, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-02', time_start: '08:00', time_end: '10:00', grade_level_code: 'D1', capacity: 30, booked: 4 },
        { id: 2, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-02', time_start: '10:00', time_end: '12:00', grade_level_code: 'D2', capacity: 30, booked: 2 },
        { id: 3, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-02', time_start: '14:00', time_end: '16:00', grade_level_code: 'D3', capacity: 30, booked: 0 },
        { id: 4, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-03', time_start: '08:00', time_end: '10:00', grade_level_code: 'D4', capacity: 2, booked: 2 },
        { id: 5, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-03', time_start: '10:00', time_end: '12:00', grade_level_code: 'D5', capacity: 30, booked: 1 },
        { id: 6, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-03', time_start: '14:00', time_end: '16:00', grade_level_code: 'D6', capacity: 30, booked: 0 },
        { id: 7, vendor_id: 1, cooperative_id: 1, preorder_season_id: 1, slot_date: '2027-01-03', time_start: '16:00', time_end: '18:00', grade_level_code: null, capacity: 40, booked: 3 },
        { id: 8, vendor_id: 2, cooperative_id: 3, preorder_season_id: 2, slot_date: '2027-01-02', time_start: '08:00', time_end: '12:00', grade_level_code: null, capacity: 50, booked: 0 }
    ],

    // Tetapan WhatsApp/SMS per koperasi (UI sahaja — penghantaran sebenar dalam B13). api_key tidak pernah dipulangkan
    messaging: {
        1: { provider: 'whatsapp_cloud', channel: 'whatsapp', sender: '60123456789', api_key: 'demo-key', is_enabled: true,
             events: { order_ready: true, order_collected: true, cash_reminder: false },
             templates: {
                 order_ready: 'Salam {parent_name}, pesanan {short_code} untuk {child_name} sedia diambil di koperasi {school_name}.',
                 order_collected: 'Pesanan {short_code} untuk {child_name} telah diambil oleh {picked_by} pada {time}. Terima kasih.',
                 cash_reminder: 'Peringatan: pesanan tunai {short_code} akan luput pada {expires_on}. Sila bayar di koperasi {school_name}.'
             } }
    },

    // notification_queue — log mesej (recipient disimpan penuh di pelayan, dipapar bertopeng)
    notifications: [
        { id: 1, vendor_id: 1, cooperative_id: 1, channel: 'whatsapp', recipient: '0123344556', event: 'order_ready', message: 'Salam Rosnah binti Ali, pesanan A123 untuk Aisyah binti Rahman sedia diambil di koperasi SK Taman Melati.', status: 'sent', attempts: 1, created_at: '2026-10-24T09:12:00+08:00', sent_at: '2026-10-24T09:12:05+08:00' },
        { id: 2, vendor_id: 1, cooperative_id: 1, channel: 'whatsapp', recipient: '0193344221', event: 'order_collected', message: 'Pesanan B456 untuk Irfan bin Zainal telah diambil oleh Murid pada 24/10 10:40. Terima kasih.', status: 'sent', attempts: 1, created_at: '2026-10-24T10:40:00+08:00', sent_at: '2026-10-24T10:40:03+08:00' },
        { id: 3, vendor_id: 1, cooperative_id: 1, channel: 'whatsapp', recipient: '0145566778', event: 'order_ready', message: 'Salam Ravi Chandran, pesanan C789 untuk Divya a/p Ravi sedia diambil di koperasi SK Taman Melati.', status: 'failed', attempts: 3, created_at: '2026-10-23T15:02:00+08:00', sent_at: null },
        { id: 4, vendor_id: 2, cooperative_id: 3, channel: 'sms', recipient: '0183344221', event: 'order_ready', message: 'VENDOR02 — tidak boleh dilihat oleh VENDOR01.', status: 'sent', attempts: 1, created_at: '2026-10-22T08:00:00+08:00', sent_at: '2026-10-22T08:00:02+08:00' }
    ]
};

var ADDON_EVENT_TYPES = ['school_holiday', 'coop_closed', 'school_opening', 'other'];
var ADDON_MESSAGE_EVENTS = ['order_ready', 'order_collected', 'cash_reminder'];
var ADDON_PROVIDERS = ['whatsapp_cloud', 'twilio', 'isms'];

// ---------- Pembantu ----------

// ModuleAccess::has — koperasi milik vendor sesi + modul dilanggan. Pulangkan { coop } atau { error }
// Tarikh mock (zon Asia/Kuala_Lumpur) — dahulu dalam vendor-finance.js
function mockToday() {
    return Mock.now().slice(0, 10);
}

function mockDaysFromToday(days) {
    return new Date(Date.now() + days * 86400000 + 8 * 3600000).toISOString().slice(0, 10);
}

// Log aktiviti sebenar sejak B12 (activity_logs) — tindakan mock modul tidak direkod (job B13 akan menulisnya)
function mockActivity() {}

function addonRequire(params, code) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return { error: mockCoopError() };
    }
    if ((MockVendor.activeModules[coop.id] || []).indexOf(code) === -1) {
        var res = Mock.fail(T('addon.module_locked'));
        res.module_locked = true;
        return { error: res };
    }
    return { coop: coop };
}

// Harga biasa seunit (sama seperti posUnitPrice) — panel vendor tidak memuat pos-cart.js
function addonRegularPrice(product, variant) {
    return Math.max(0, product.price + (variant ? variant.price_delta : 0));
}

function addonFind(rows, id, coop) {
    return mockCoopRows(rows, coop.id).filter(function (row) {
        return row.id === Number(id);
    })[0] || null;
}

function addonDateValid(text) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(text || ''));
}

function addonTimeValid(text) {
    return /^([01]\d|2[0-3]):[0-5]\d$/.test(String(text || ''));
}

// Bilangan ibu bapa yang mempunyai anak aktif di koperasi ini (penerima e-mel berita). Sejak B11 MockOrders dibuang —
// nilai tetap ikut data contoh lama; B13 mengira dari jadual children
var MOCK_ADDON_RECIPIENTS = { 1: 12, 2: 3, 3: 2 };

function addonRecipients(coop) {
    return MOCK_ADDON_RECIPIENTS[coop.id] || 0;
}

// 0123344556 → 012-***4556
function addonMaskPhone(phone) {
    var digits = String(phone).replace(/\D/g, '');
    return digits.slice(0, 3) + '-***' + digits.slice(-4);
}

// ---------- Berita (news) ----------

function addonNewsState(post) {
    if (!post.is_active) {
        return 'draft';
    }
    return post.published_at > Mock.now() ? 'scheduled' : 'published';
}

function addonNewsRow(post) {
    return { id: post.id, title: post.title, body: post.body, image_url: post.image_url, published_at: post.published_at, is_active: post.is_active,
        send_email: post.send_email, email_sent_at: post.email_sent_at, email_recipients: post.email_recipients, state: addonNewsState(post) };
}

// E-mel dimasukkan ke email_queue sekali sahaja, bila berita sudah terbit (berjadual → cron semasa terbit)
function addonQueueNewsEmail(post, coop) {
    if (post.send_email && !post.email_sent_at && addonNewsState(post) === 'published') {
        post.email_sent_at = Mock.now();
        post.email_recipients = addonRecipients(coop);
        return post.email_recipients;
    }
    return 0;
}

Mock.register('vendor/news/list', function (params) {
    var ctx = addonRequire(params, 'news');
    if (ctx.error) {
        return ctx.error;
    }
    var rows = mockCoopRows(MockAddons.news, ctx.coop.id).map(function (post) {
        addonQueueNewsEmail(post, ctx.coop);
        return addonNewsRow(post);
    }).filter(function (row) {
        return !params.state || row.state === params.state;
    }).sort(function (a, b) { return a.published_at < b.published_at ? 1 : -1; });
    return Mock.ok(Mock.paginate(rows, params, ['title', 'body']));
});

Mock.register('vendor/news/recipients', function (params) {
    var ctx = addonRequire(params, 'news');
    return ctx.error ? ctx.error : Mock.ok({ count: addonRecipients(ctx.coop) });
});

Mock.register('vendor/news/save', function (body) {
    var ctx = addonRequire(body, 'news');
    if (ctx.error) {
        return ctx.error;
    }
    var errors = Mock.requireFields(body, ['title', 'body', 'published_at']);
    if (String(body.title || '').length > 150) {
        errors.title = T('error.invalid');
    }
    if (body.image_url && String(body.image_url).indexOf('data:image/') !== 0 && String(body.image_url).indexOf('http') !== 0) {
        errors.image_url = T('error.invalid');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var post = body.id ? addonFind(MockAddons.news, body.id, ctx.coop) : null;
    if (body.id && !post) {
        return Mock.fail(T('error.not_found'));
    }
    if (!post) {
        post = { id: Mock.nextId(MockAddons.news), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id, email_sent_at: null, email_recipients: 0 };
        MockAddons.news.push(post);
    }
    post.title = String(body.title).trim();
    post.body = String(body.body).trim();
    post.image_url = body.image_url || null;
    post.published_at = String(body.published_at).length === 16 ? body.published_at + ':00+08:00' : body.published_at;
    post.is_active = Boolean(body.is_active);
    // E-mel yang sudah dihantar tidak boleh "dihantar semula" dengan menyunting
    post.send_email = post.email_sent_at ? true : Boolean(body.send_email);
    var queued = addonQueueNewsEmail(post, ctx.coop);
    mockActivity('news_save', ctx.coop.id, 'news_post', post.id, post.title);
    return Mock.ok({ post: addonNewsRow(post), emails_queued: queued });
});

Mock.register('vendor/news/delete', function (body) {
    var ctx = addonRequire(body, 'news');
    if (ctx.error) {
        return ctx.error;
    }
    var post = addonFind(MockAddons.news, body.id, ctx.coop);
    if (!post) {
        return Mock.fail(T('error.not_found'));
    }
    MockAddons.news.splice(MockAddons.news.indexOf(post), 1);
    mockActivity('news_delete', ctx.coop.id, 'news_post', post.id, post.title);
    return Mock.ok({ id: post.id });
});

// ---------- Jualan murah (clearance) ----------

function addonSaleState(sale) {
    var now = Mock.now();
    if (!sale.is_active) {
        return 'inactive';
    }
    if (sale.starts_at > now) {
        return 'scheduled';
    }
    return sale.ends_at < now ? 'ended' : 'active';
}

function addonSaleItems(sale) {
    return MockAddons.clearanceItems.filter(function (item) {
        return item.vendor_id === sale.vendor_id && item.clearance_sale_id === sale.id;
    });
}

function addonSaleRow(sale) {
    return { id: sale.id, name: sale.name, starts_on: sale.starts_at.slice(0, 10), ends_on: sale.ends_at.slice(0, 10), is_active: sale.is_active,
        state: addonSaleState(sale), item_count: addonSaleItems(sale).length };
}

function addonItemRow(item, coop) {
    var product = mockFindProduct(item.product_id, coop.id);
    var variant = product && item.variant_id ? mockFindVariant(product, item.variant_id) : null;
    var regular = product ? addonRegularPrice(product, variant) : 0;
    var level = product ? mockStockLevel(coop.id, product.id, variant ? variant.id : 0) : null;
    return { id: item.id, product_id: item.product_id, variant_id: item.variant_id, product_name: product ? product.name : T('store.cart.removed_product'),
        sku: variant ? variant.sku : (product ? product.sku : ''), variant_label: variant ? variant.label : null, has_variants: product ? product.has_variants : false,
        regular_price: regular, sale_price: item.sale_price, qty_limit: item.qty_limit,
        discount_percent: regular > 0 ? Math.round((1 - item.sale_price / regular) * 100) : 0,
        available: level ? Math.max(0, level.qty_on_hand - level.qty_reserved) : null };
}

Mock.register('vendor/clearance/list', function (params) {
    var ctx = addonRequire(params, 'clearance');
    if (ctx.error) {
        return ctx.error;
    }
    var rows = mockCoopRows(MockAddons.clearanceSales, ctx.coop.id).map(addonSaleRow).filter(function (row) {
        return !params.state || row.state === params.state;
    }).sort(function (a, b) { return a.starts_on < b.starts_on ? 1 : -1; });
    return Mock.ok(Mock.paginate(rows, params, ['name']));
});

Mock.register('vendor/clearance/get', function (params) {
    var ctx = addonRequire(params, 'clearance');
    if (ctx.error) {
        return ctx.error;
    }
    var sale = addonFind(MockAddons.clearanceSales, params.id, ctx.coop);
    if (!sale) {
        return Mock.fail(T('error.not_found'));
    }
    var data = addonSaleRow(sale);
    data.items = addonSaleItems(sale).map(function (item) { return addonItemRow(item, ctx.coop); });
    return Mock.ok(data);
});

Mock.register('vendor/clearance/save', function (body) {
    var ctx = addonRequire(body, 'clearance');
    if (ctx.error) {
        return ctx.error;
    }
    var errors = Mock.requireFields(body, ['name', 'starts_on', 'ends_on']);
    if (!errors.starts_on && !addonDateValid(body.starts_on)) {
        errors.starts_on = T('error.invalid');
    }
    if (!errors.ends_on && !addonDateValid(body.ends_on)) {
        errors.ends_on = T('error.invalid');
    }
    if (!errors.starts_on && !errors.ends_on && body.ends_on < body.starts_on) {
        errors.ends_on = T('addon.end_before_start');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var sale = body.id ? addonFind(MockAddons.clearanceSales, body.id, ctx.coop) : null;
    if (body.id && !sale) {
        return Mock.fail(T('error.not_found'));
    }
    if (!sale) {
        sale = { id: Mock.nextId(MockAddons.clearanceSales), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id };
        MockAddons.clearanceSales.push(sale);
    }
    sale.name = String(body.name).trim();
    sale.starts_at = body.starts_on + 'T00:00:00+08:00';
    sale.ends_at = body.ends_on + 'T23:59:59+08:00';
    sale.is_active = Boolean(body.is_active);
    mockActivity('clearance_save', ctx.coop.id, 'clearance_sale', sale.id, sale.name);
    return Mock.ok(addonSaleRow(sale));
});

Mock.register('vendor/clearance/delete', function (body) {
    var ctx = addonRequire(body, 'clearance');
    if (ctx.error) {
        return ctx.error;
    }
    var sale = addonFind(MockAddons.clearanceSales, body.id, ctx.coop);
    if (!sale) {
        return Mock.fail(T('error.not_found'));
    }
    MockAddons.clearanceItems = MockAddons.clearanceItems.filter(function (item) {
        return !(item.vendor_id === sale.vendor_id && item.clearance_sale_id === sale.id);
    });
    MockAddons.clearanceSales.splice(MockAddons.clearanceSales.indexOf(sale), 1);
    mockActivity('clearance_delete', ctx.coop.id, 'clearance_sale', sale.id, sale.name);
    return Mock.ok({ id: sale.id });
});

// Tambah / sunting item: harga jualan mesti lebih rendah dari harga biasa; produk + varian unik dalam satu jualan
Mock.register('vendor/clearance/item-save', function (body) {
    var ctx = addonRequire(body, 'clearance');
    if (ctx.error) {
        return ctx.error;
    }
    var sale = addonFind(MockAddons.clearanceSales, body.sale_id, ctx.coop);
    if (!sale) {
        return Mock.fail(T('error.not_found'));
    }
    var product = mockFindProduct(body.product_id, ctx.coop.id);
    var errors = {};
    if (!product) {
        errors.product_id = T('error.required');
        return Mock.fail(T('error.check_form'), errors);
    }
    var variantId = body.variant_id ? Number(body.variant_id) : null;
    var variant = variantId ? mockFindVariant(product, variantId) : null;
    if (variantId && !variant) {
        errors.variant_id = T('error.not_found');
    }
    var regular = addonRegularPrice(product, variant);
    var salePrice = Number(body.sale_price);
    if (!(salePrice > 0)) {
        errors.sale_price = T('error.required');
    } else if (salePrice >= regular) {
        errors.sale_price = T('clearance.price_not_lower', { amount: formatRM(regular) });
    }
    if (body.qty_limit !== null && body.qty_limit !== undefined && body.qty_limit !== '' && !(Number(body.qty_limit) >= 1)) {
        errors.qty_limit = T('error.invalid');
    }
    var duplicate = addonSaleItems(sale).some(function (item) {
        return item.id !== Number(body.id || 0) && item.product_id === product.id && (item.variant_id || null) === variantId;
    });
    if (duplicate) {
        errors.product_id = T('clearance.duplicate_item');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var item = body.id ? addonSaleItems(sale).filter(function (row) { return row.id === Number(body.id); })[0] : null;
    if (body.id && !item) {
        return Mock.fail(T('error.not_found'));
    }
    if (!item) {
        item = { id: Mock.nextId(MockAddons.clearanceItems), vendor_id: MockVendor.sessionVendorId, clearance_sale_id: sale.id };
        MockAddons.clearanceItems.push(item);
    }
    item.product_id = product.id;
    item.variant_id = variantId;
    item.sale_price = salePrice;
    item.qty_limit = body.qty_limit ? Number(body.qty_limit) : null;
    return Mock.ok(addonItemRow(item, ctx.coop));
});

Mock.register('vendor/clearance/item-delete', function (body) {
    var ctx = addonRequire(body, 'clearance');
    if (ctx.error) {
        return ctx.error;
    }
    var sale = addonFind(MockAddons.clearanceSales, body.sale_id, ctx.coop);
    var item = sale ? addonSaleItems(sale).filter(function (row) { return row.id === Number(body.id); })[0] : null;
    if (!item) {
        return Mock.fail(T('error.not_found'));
    }
    MockAddons.clearanceItems.splice(MockAddons.clearanceItems.indexOf(item), 1);
    return Mock.ok({ id: item.id });
});

// ---------- Kalendar cuti (calendar) ----------

// Acara yang menyekat tempahan pada tarikh ini (untuk store & papan pemuka)
function addonBlockingEvent(coop, day) {
    if ((MockVendor.activeModules[coop.id] || []).indexOf('calendar') === -1) {
        return null;
    }
    return mockCoopRows(MockAddons.calendarEvents, coop.id).filter(function (event) {
        return event.blocks_orders && event.starts_on <= day && event.ends_on >= day;
    })[0] || null;
}

// Penutupan akan datang (dalam 14 hari) untuk banner store
function addonUpcomingClosure(coop) {
    if ((MockVendor.activeModules[coop.id] || []).indexOf('calendar') === -1) {
        return null;
    }
    var today = mockToday();
    var limit = mockDaysFromToday(14);
    return mockCoopRows(MockAddons.calendarEvents, coop.id).filter(function (event) {
        return event.blocks_orders && event.ends_on >= today && event.starts_on <= limit;
    }).sort(function (a, b) { return a.starts_on < b.starts_on ? -1 : 1; })[0] || null;
}

function addonNextDay(day) {
    var date = new Date(Date.UTC(+day.slice(0, 4), +day.slice(5, 7) - 1, +day.slice(8, 10) + 1));
    return date.toISOString().slice(0, 10);
}

function addonEventRow(event) {
    return { id: event.id, title: event.title, event_type: event.event_type, starts_on: event.starts_on, ends_on: event.ends_on, blocks_orders: event.blocks_orders };
}

// Senarai acara yang bertindih dengan julat [from, to]
Mock.register('vendor/calendar/list', function (params) {
    var ctx = addonRequire(params, 'calendar');
    if (ctx.error) {
        return ctx.error;
    }
    var from = params.from || '0000-01-01';
    var to = params.to || '9999-12-31';
    var rows = mockCoopRows(MockAddons.calendarEvents, ctx.coop.id).filter(function (event) {
        return event.ends_on >= from && event.starts_on <= to;
    }).sort(function (a, b) { return a.starts_on < b.starts_on ? -1 : 1; }).map(addonEventRow);
    var today = mockToday();
    var blocking = addonBlockingEvent(ctx.coop, today);
    return Mock.ok({ items: rows, today: today, today_blocked: blocking ? addonEventRow(blocking) : null });
});

Mock.register('vendor/calendar/save', function (body) {
    var ctx = addonRequire(body, 'calendar');
    if (ctx.error) {
        return ctx.error;
    }
    var errors = Mock.requireFields(body, ['title', 'event_type', 'starts_on', 'ends_on']);
    if (body.event_type && ADDON_EVENT_TYPES.indexOf(body.event_type) === -1) {
        errors.event_type = T('error.required');
    }
    if (!errors.starts_on && !addonDateValid(body.starts_on)) {
        errors.starts_on = T('error.invalid');
    }
    if (!errors.ends_on && !addonDateValid(body.ends_on)) {
        errors.ends_on = T('error.invalid');
    }
    if (!errors.starts_on && !errors.ends_on && body.ends_on < body.starts_on) {
        errors.ends_on = T('addon.end_before_start');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var event = body.id ? addonFind(MockAddons.calendarEvents, body.id, ctx.coop) : null;
    if (body.id && !event) {
        return Mock.fail(T('error.not_found'));
    }
    if (!event) {
        event = { id: Mock.nextId(MockAddons.calendarEvents), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id };
        MockAddons.calendarEvents.push(event);
    }
    event.title = String(body.title).trim();
    event.event_type = body.event_type;
    event.starts_on = body.starts_on;
    event.ends_on = body.ends_on;
    event.blocks_orders = Boolean(body.blocks_orders);
    mockActivity('calendar_save', ctx.coop.id, 'calendar_event', event.id, event.title);
    return Mock.ok(addonEventRow(event));
});

Mock.register('vendor/calendar/delete', function (body) {
    var ctx = addonRequire(body, 'calendar');
    if (ctx.error) {
        return ctx.error;
    }
    var event = addonFind(MockAddons.calendarEvents, body.id, ctx.coop);
    if (!event) {
        return Mock.fail(T('error.not_found'));
    }
    MockAddons.calendarEvents.splice(MockAddons.calendarEvents.indexOf(event), 1);
    mockActivity('calendar_delete', ctx.coop.id, 'calendar_event', event.id, event.title);
    return Mock.ok({ id: event.id });
});

// ---------- Pembukaan sekolah (school_opening) ----------

function addonSeasonState(season) {
    var today = mockToday();
    if (season.opens_at > today) {
        return 'upcoming';
    }
    return season.closes_at < today ? 'closed' : 'open';
}

function addonSeasonSlots(season) {
    return MockAddons.slots.filter(function (slot) {
        return slot.vendor_id === season.vendor_id && slot.preorder_season_id === season.id;
    }).sort(function (a, b) {
        var ka = a.slot_date + a.time_start;
        var kb = b.slot_date + b.time_start;
        return ka < kb ? -1 : (ka > kb ? 1 : 0);
    });
}

function addonSeasonRow(season) {
    var slots = addonSeasonSlots(season);
    return { id: season.id, school_year: season.school_year, opens_at: season.opens_at, closes_at: season.closes_at, pickup_starts_on: season.pickup_starts_on,
        note: season.note, state: addonSeasonState(season), slot_count: slots.length,
        capacity: slots.reduce(function (sum, slot) { return sum + slot.capacity; }, 0),
        booked: slots.reduce(function (sum, slot) { return sum + slot.booked; }, 0) };
}

function addonSlotRow(slot) {
    return { id: slot.id, slot_date: slot.slot_date, time_start: slot.time_start, time_end: slot.time_end, grade_level_code: slot.grade_level_code,
        capacity: slot.capacity, booked: slot.booked, remaining: Math.max(0, slot.capacity - slot.booked) };
}

// Musim pre-order yang sedang dibuka (modul aktif sahaja)
function addonOpenSeason(coop) {
    if ((MockVendor.activeModules[coop.id] || []).indexOf('school_opening') === -1) {
        return null;
    }
    return mockCoopRows(MockAddons.seasons, coop.id).filter(function (season) {
        return addonSeasonState(season) === 'open';
    })[0] || null;
}

// Slot yang sesuai untuk darjah anak-anak dalam troli (null = semua darjah)
function addonSlotsFor(coop, grades) {
    var season = addonOpenSeason(coop);
    if (!season) {
        return [];
    }
    return addonSeasonSlots(season).filter(function (slot) {
        return slot.grade_level_code === null || grades.indexOf(slot.grade_level_code) !== -1;
    });
}

Mock.register('vendor/school-opening/seasons', function (params) {
    var ctx = addonRequire(params, 'school_opening');
    if (ctx.error) {
        return ctx.error;
    }
    var rows = mockCoopRows(MockAddons.seasons, ctx.coop.id).sort(function (a, b) { return b.school_year - a.school_year; }).map(addonSeasonRow);
    return Mock.ok({ items: rows, grades: mockGradesFor(ctx.coop.school_type) });
});

Mock.register('vendor/school-opening/season-save', function (body) {
    var ctx = addonRequire(body, 'school_opening');
    if (ctx.error) {
        return ctx.error;
    }
    var errors = Mock.requireFields(body, ['school_year', 'opens_at', 'closes_at', 'pickup_starts_on']);
    ['opens_at', 'closes_at', 'pickup_starts_on'].forEach(function (field) {
        if (!errors[field] && !addonDateValid(body[field])) {
            errors[field] = T('error.invalid');
        }
    });
    var year = Number(body.school_year);
    if (!errors.school_year && (year < 2020 || year > 2100)) {
        errors.school_year = T('error.invalid');
    }
    if (!errors.opens_at && !errors.closes_at && body.closes_at < body.opens_at) {
        errors.closes_at = T('addon.end_before_start');
    }
    if (!errors.closes_at && !errors.pickup_starts_on && body.pickup_starts_on < body.closes_at) {
        errors.pickup_starts_on = T('opening.pickup_after_close');
    }
    var duplicate = mockCoopRows(MockAddons.seasons, ctx.coop.id).some(function (season) {
        return season.school_year === year && season.id !== Number(body.id || 0);
    });
    if (!errors.school_year && duplicate) {
        errors.school_year = T('opening.duplicate_year');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var season = body.id ? addonFind(MockAddons.seasons, body.id, ctx.coop) : null;
    if (body.id && !season) {
        return Mock.fail(T('error.not_found'));
    }
    if (!season) {
        season = { id: Mock.nextId(MockAddons.seasons), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id };
        MockAddons.seasons.push(season);
    }
    season.school_year = year;
    season.opens_at = body.opens_at;
    season.closes_at = body.closes_at;
    season.pickup_starts_on = body.pickup_starts_on;
    season.note = String(body.note || '').trim();
    mockActivity('season_save', ctx.coop.id, 'preorder_season', season.id, String(season.school_year));
    return Mock.ok(addonSeasonRow(season));
});

Mock.register('vendor/school-opening/season-delete', function (body) {
    var ctx = addonRequire(body, 'school_opening');
    if (ctx.error) {
        return ctx.error;
    }
    var season = addonFind(MockAddons.seasons, body.id, ctx.coop);
    if (!season) {
        return Mock.fail(T('error.not_found'));
    }
    if (addonSeasonSlots(season).some(function (slot) { return slot.booked > 0; })) {
        return Mock.fail(T('opening.season_has_bookings'));
    }
    MockAddons.slots = MockAddons.slots.filter(function (slot) {
        return !(slot.vendor_id === season.vendor_id && slot.preorder_season_id === season.id);
    });
    MockAddons.seasons.splice(MockAddons.seasons.indexOf(season), 1);
    mockActivity('season_delete', ctx.coop.id, 'preorder_season', season.id, String(season.school_year));
    return Mock.ok({ id: season.id });
});

Mock.register('vendor/school-opening/slots', function (params) {
    var ctx = addonRequire(params, 'school_opening');
    if (ctx.error) {
        return ctx.error;
    }
    var season = addonFind(MockAddons.seasons, params.season_id, ctx.coop);
    if (!season) {
        return Mock.fail(T('error.not_found'));
    }
    return Mock.ok({ season: addonSeasonRow(season), items: addonSeasonSlots(season).map(addonSlotRow) });
});

// Tambah slot: satu slot untuk setiap darjah dipilih (tiada darjah = semua darjah). Sunting: satu slot
Mock.register('vendor/school-opening/slot-save', function (body) {
    var ctx = addonRequire(body, 'school_opening');
    if (ctx.error) {
        return ctx.error;
    }
    var season = addonFind(MockAddons.seasons, body.season_id, ctx.coop);
    if (!season) {
        return Mock.fail(T('error.not_found'));
    }
    var errors = Mock.requireFields(body, ['slot_date', 'time_start', 'time_end', 'capacity']);
    if (!errors.slot_date && !addonDateValid(body.slot_date)) {
        errors.slot_date = T('error.invalid');
    } else if (!errors.slot_date && body.slot_date < season.pickup_starts_on) {
        errors.slot_date = T('opening.slot_before_pickup', { date: formatDate(season.pickup_starts_on) });
    }
    if (!errors.time_start && !addonTimeValid(body.time_start)) {
        errors.time_start = T('error.invalid');
    }
    if (!errors.time_end && !addonTimeValid(body.time_end)) {
        errors.time_end = T('error.invalid');
    }
    if (!errors.time_start && !errors.time_end && body.time_end <= body.time_start) {
        errors.time_end = T('opening.time_order');
    }
    var capacity = Number(body.capacity);
    if (!errors.capacity && !(capacity >= 1 && capacity <= 1000)) {
        errors.capacity = T('error.invalid');
    }
    var grades = mockGradesFor(ctx.coop.school_type);
    var wanted = (body.grades || []).filter(function (code) { return grades.indexOf(code) !== -1; });
    var slot = body.id ? addonSeasonSlots(season).filter(function (row) { return row.id === Number(body.id); })[0] : null;
    if (body.id && !slot) {
        return Mock.fail(T('error.not_found'));
    }
    if (slot && !errors.capacity && capacity < slot.booked) {
        errors.capacity = T('opening.capacity_below_booked', { n: slot.booked });
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var targets = slot ? [slot] : (wanted.length ? wanted : [null]).map(function (grade) {
        var row = { id: Mock.nextId(MockAddons.slots), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id, preorder_season_id: season.id,
            grade_level_code: grade, booked: 0 };
        MockAddons.slots.push(row);
        return row;
    });
    targets.forEach(function (row) {
        row.slot_date = body.slot_date;
        row.time_start = body.time_start;
        row.time_end = body.time_end;
        row.capacity = capacity;
        if (slot) {
            row.grade_level_code = wanted[0] || null;
        }
    });
    return Mock.ok({ created: slot ? 0 : targets.length, items: targets.map(addonSlotRow) });
});

Mock.register('vendor/school-opening/slot-delete', function (body) {
    var ctx = addonRequire(body, 'school_opening');
    if (ctx.error) {
        return ctx.error;
    }
    var slot = addonFind(MockAddons.slots, body.id, ctx.coop);
    if (!slot) {
        return Mock.fail(T('error.not_found'));
    }
    if (slot.booked > 0) {
        return Mock.fail(T('opening.slot_has_bookings', { n: slot.booked }));
    }
    MockAddons.slots.splice(MockAddons.slots.indexOf(slot), 1);
    return Mock.ok({ id: slot.id });
});

// ---------- WhatsApp / SMS (UI sahaja) ----------

function addonMessagingDefaults() {
    var templates = {};
    ADDON_MESSAGE_EVENTS.forEach(function (event) {
        templates[event] = T('messaging.default.' + event);
    });
    return { provider: 'whatsapp_cloud', channel: 'whatsapp', sender: '', api_key: '', is_enabled: false,
        events: { order_ready: true, order_collected: true, cash_reminder: false }, templates: templates };
}

function addonMessagingPublic(row) {
    return { provider: row.provider, channel: row.channel, sender: row.sender, api_key_set: Boolean(row.api_key), is_enabled: row.is_enabled,
        events: Object.assign({}, row.events), templates: Object.assign({}, row.templates) };
}

var ADDON_PLACEHOLDERS = ['parent_name', 'child_name', 'short_code', 'school_name', 'picked_by', 'time', 'expires_on'];

Mock.register('vendor/messaging/get', function (params) {
    var ctx = addonRequire(params, 'whatsapp_sms');
    if (ctx.error) {
        return ctx.error;
    }
    var row = MockAddons.messaging[ctx.coop.id] || addonMessagingDefaults();
    var data = addonMessagingPublic(row);
    data.placeholders = ADDON_PLACEHOLDERS;
    return Mock.ok(data);
});

Mock.register('vendor/messaging/save', function (body) {
    var ctx = addonRequire(body, 'whatsapp_sms');
    if (ctx.error) {
        return ctx.error;
    }
    var row = MockAddons.messaging[ctx.coop.id] || addonMessagingDefaults();
    var errors = {};
    if (ADDON_PROVIDERS.indexOf(body.provider) === -1) {
        errors.provider = T('error.required');
    }
    if (['whatsapp', 'sms'].indexOf(body.channel) === -1) {
        errors.channel = T('error.required');
    }
    if (body.is_enabled && !/^60\d{8,11}$/.test(String(body.sender || ''))) {
        errors.sender = T('messaging.sender_format');
    }
    if (body.is_enabled && !body.api_key && !row.api_key) {
        errors.api_key = T('error.required');
    }
    ADDON_MESSAGE_EVENTS.forEach(function (event) {
        var text = String((body.templates || {})[event] || '').trim();
        var unknown = (text.match(/\{([a-z_]+)\}/g) || []).filter(function (tag) {
            return ADDON_PLACEHOLDERS.indexOf(tag.slice(1, -1)) === -1;
        });
        if (!text) {
            errors['templates.' + event] = T('error.required');
        } else if (text.length > 500) {
            errors['templates.' + event] = T('error.invalid');
        } else if (unknown.length) {
            errors['templates.' + event] = T('messaging.unknown_placeholder', { tags: unknown.join(', ') });
        }
    });
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    row.provider = body.provider;
    row.channel = body.channel;
    row.sender = String(body.sender || '').trim();
    // Rahsia: kosong = kekalkan nilai lama
    if (body.api_key) {
        row.api_key = String(body.api_key);
    }
    row.is_enabled = Boolean(body.is_enabled);
    ADDON_MESSAGE_EVENTS.forEach(function (event) {
        row.events[event] = Boolean((body.events || {})[event]);
        row.templates[event] = String(body.templates[event]).trim();
    });
    MockAddons.messaging[ctx.coop.id] = row;
    mockActivity('messaging_save', ctx.coop.id, 'messaging', null, '');
    return Mock.ok(addonMessagingPublic(row));
});

// Mesej ujian → baris gilir (Peringkat 1: terus "dihantar")
Mock.register('vendor/messaging/test', function (body) {
    var ctx = addonRequire(body, 'whatsapp_sms');
    if (ctx.error) {
        return ctx.error;
    }
    var row = MockAddons.messaging[ctx.coop.id];
    if (!row || !row.is_enabled) {
        return Mock.fail(T('messaging.enable_first'));
    }
    var phone = String(body.phone || '').replace(/\D/g, '');
    if (!/^01\d{8,9}$/.test(phone)) {
        return Mock.fail(T('error.check_form'), { phone: T('store.auth.phone_invalid') });
    }
    var note = { id: Mock.nextId(MockAddons.notifications), vendor_id: MockVendor.sessionVendorId, cooperative_id: ctx.coop.id, channel: row.channel,
        recipient: phone, event: 'test', message: T('messaging.test_message', { school: ctx.coop.school_name }), status: 'sent', attempts: 1,
        created_at: Mock.now(), sent_at: Mock.now() };
    MockAddons.notifications.unshift(note);
    return Mock.ok({ id: note.id });
});

Mock.register('vendor/messaging/log', function (params) {
    var ctx = addonRequire(params, 'whatsapp_sms');
    if (ctx.error) {
        return ctx.error;
    }
    var rows = mockCoopRows(MockAddons.notifications, ctx.coop.id).filter(function (row) {
        return !params.status || row.status === params.status;
    }).sort(function (a, b) { return a.created_at < b.created_at ? 1 : -1; }).map(function (row) {
        return { id: row.id, channel: row.channel, recipient: addonMaskPhone(row.recipient), event: row.event, message: row.message,
            status: row.status, attempts: row.attempts, created_at: row.created_at, sent_at: row.sent_at };
    });
    return Mock.ok(Mock.paginate(rows, params, ['message']));
});

Mock.persist('addons', MockAddons, ['news', 'clearanceSales', 'clearanceItems', 'calendarEvents', 'seasons', 'slots', 'messaging', 'notifications']);
