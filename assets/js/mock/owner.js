// Data mock panel owner (Peringkat 1). Bentuk objek = kontrak API (PELAN 18).
// Sejak B3 hanya laluan yang belum sebenar kekal di sini: langganan & invois (B12), terjemahan (B14).
// Data vendor/pelan/koperasi di bawah hanya untuk ringkasan laluan itu — id mock tidak sepadan dengan DB.

var MockOwner = {
    plans: [
        { id: 1, code: 'ASAS', name: 'Asas', price_monthly: 4900, price_yearly: 49000, max_cooperatives: 2, max_products: 300, max_staff: 3,
          features: ['Store ibu bapa', 'Kit sekolah', 'Serah barang'], is_active: true, sort_order: 1 },
        { id: 2, code: 'PRO', name: 'Pro', price_monthly: 9900, price_yearly: 99000, max_cooperatives: 5, max_products: 2000, max_staff: 10,
          features: ['Semua dalam Asas', 'POS kaunter', 'Laporan penuh'], is_active: true, sort_order: 2 },
        { id: 3, code: 'PREMIUM', name: 'Premium', price_monthly: 19900, price_yearly: 199000, max_cooperatives: null, max_products: null, max_staff: null,
          features: ['Semua dalam Pro', 'Koperasi tanpa had', 'Sokongan keutamaan'], is_active: true, sort_order: 3 }
    ],

    vendors: [
        { id: 1, code: 'VENDOR01', company_name: 'Niaga Sekolah Sdn Bhd', registration_no: '202301012345', contact_name: 'Ahmad bin Ismail',
          phone: '012-3456789', email: 'ahmad@niagasekolah.my', address: 'No. 12, Jalan Melati 3, 53100 Gombak, Selangor',
          status: 'active', trial_ends_at: null, plan_id: 2, billing_cycle: 'yearly', created_at: '2026-01-05T09:00:00+08:00' },
        { id: 2, code: 'VENDOR02', company_name: 'Koperasi Bestari Enterprise', registration_no: 'JM0876543-T', contact_name: 'Lim Mei Ling',
          phone: '017-2223344', email: 'meiling@bestari.my', address: '8, Jalan Indah 2, 81200 Johor Bahru, Johor',
          status: 'active', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-02-11T10:30:00+08:00' },
        { id: 3, code: 'VENDOR03', company_name: 'Pustaka Ilmu Trading', registration_no: 'SA0123456-A', contact_name: 'Nurul Huda',
          phone: '019-8887766', email: 'huda@pustakailmu.my', address: '22, Jalan Seri Pagi, 08000 Sungai Petani, Kedah',
          status: 'trial', trial_ends_at: '2026-11-03T23:59:59+08:00', plan_id: 1, billing_cycle: 'monthly', created_at: '2026-10-20T14:00:00+08:00' },
        { id: 4, code: 'VENDOR04', company_name: 'Kedai Buku Cemerlang', registration_no: 'PG0456789-K', contact_name: 'Muthu a/l Rajan',
          phone: '016-5554433', email: 'muthu@cemerlang.my', address: '5, Lorong Ladang, 14000 Bukit Mertajam, Pulau Pinang',
          status: 'past_due', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-03-02T08:15:00+08:00' },
        { id: 5, code: 'VENDOR05', company_name: 'Seri Murni Supplies', registration_no: 'KT0345678-P', contact_name: 'Wan Zainab',
          phone: '013-9998877', email: 'zainab@serimurni.my', address: '17, Jalan Sultan Zainal Abidin, 20000 Kuala Terengganu',
          status: 'suspended', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-04-18T11:00:00+08:00' },
        { id: 6, code: 'VENDOR06', company_name: 'Delima Niaga', registration_no: 'NS0234567-D', contact_name: 'Faizal Rahman',
          phone: '011-23456789', email: 'faizal@delima.my', address: '3, Jalan Delima, 70000 Seremban, Negeri Sembilan',
          status: 'cancelled', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-02-01T09:45:00+08:00' },
        { id: 7, code: 'VENDOR07', company_name: 'Mutiara Edu Sdn Bhd', registration_no: '202201098765', contact_name: 'Tan Wei Jie',
          phone: '018-7776655', email: 'weijie@mutiaraedu.my', address: 'Presint 9, 62250 Putrajaya',
          status: 'active', trial_ends_at: null, plan_id: 3, billing_cycle: 'monthly', created_at: '2026-01-20T16:20:00+08:00' }
    ],

    cooperatives: [
        { id: 1, vendor_id: 1, slug: 'sk-taman-melati', school_name: 'SK Taman Melati', school_code: 'BBA1234', school_type: 'SK', state: 'Selangor', district: 'Gombak', status: 'active', template_id: 1, modules: ['news', 'school_opening', 'calendar', 'whatsapp_sms'], created_at: '2026-01-06T10:00:00+08:00' },
        { id: 2, vendor_id: 1, slug: 'sjkc-chung-hwa', school_name: 'SJKC Chung Hwa', school_code: 'BBC5678', school_type: 'SJKC', state: 'Selangor', district: 'Petaling', status: 'active', template_id: 2, modules: [], created_at: '2026-02-14T10:00:00+08:00' },
        { id: 3, vendor_id: 2, slug: 'smk-bukit-indah', school_name: 'SMK Bukit Indah', school_code: 'JEA9012', school_type: 'SMK', state: 'Johor', district: 'Johor Bahru', status: 'active', template_id: 1, modules: ['clearance'], created_at: '2026-02-12T10:00:00+08:00' },
        { id: 4, vendor_id: 3, slug: 'sk-seri-pagi', school_name: 'SK Seri Pagi', school_code: 'KBA3456', school_type: 'SK', state: 'Kedah', district: 'Kuala Muda', status: 'active', template_id: 1, modules: [], created_at: '2026-10-20T15:00:00+08:00' },
        { id: 5, vendor_id: 4, slug: 'sjkt-ladang-bukit', school_name: 'SJKT Ladang Bukit', school_code: 'PBD2345', school_type: 'SJKT', state: 'Pulau Pinang', district: 'Seberang Perai Tengah', status: 'active', template_id: 1, modules: [], created_at: '2026-03-03T10:00:00+08:00' },
        { id: 6, vendor_id: 5, slug: 'smka-al-amin', school_name: 'SMKA Al-Amin', school_code: 'TEA7788', school_type: 'SMKA', state: 'Terengganu', district: 'Kuala Terengganu', status: 'suspended', template_id: 1, modules: ['news'], created_at: '2026-04-19T10:00:00+08:00' },
        { id: 7, vendor_id: 7, slug: 'sk-presint-9', school_name: 'SK Putrajaya Presint 9 (1)', school_code: 'WPA0011', school_type: 'SK', state: 'W.P. Putrajaya', district: 'Putrajaya', status: 'active', template_id: 2, modules: ['news', 'calendar', 'mobile_app'], created_at: '2026-01-21T10:00:00+08:00' },
        { id: 8, vendor_id: 7, slug: 'smk-presint-9', school_name: 'SMK Putrajaya Presint 9 (1)', school_code: 'WEA0022', school_type: 'SMK', state: 'W.P. Putrajaya', district: 'Putrajaya', status: 'active', template_id: 1, modules: ['news'], created_at: '2026-02-01T10:00:00+08:00' },
        { id: 9, vendor_id: 7, slug: 'sk-bandar-baru-bangi', school_name: 'SK Bandar Baru Bangi', school_code: 'BBA9090', school_type: 'SK', state: 'Selangor', district: 'Hulu Langat', status: 'closed', template_id: 1, modules: [], created_at: '2026-03-15T10:00:00+08:00' }
    ],

    subscriptions: [
        { id: 1, vendor_id: 1, plan_id: 2, billing_cycle: 'yearly', status: 'active', started_at: '2026-01-05', current_period_start: '2026-01-05', current_period_end: '2027-01-04' },
        { id: 2, vendor_id: 2, plan_id: 1, billing_cycle: 'monthly', status: 'active', started_at: '2026-02-11', current_period_start: '2026-10-11', current_period_end: '2026-11-10' },
        { id: 3, vendor_id: 3, plan_id: 1, billing_cycle: 'monthly', status: 'trial', started_at: '2026-10-20', current_period_start: '2026-10-20', current_period_end: '2026-11-03' },
        { id: 4, vendor_id: 4, plan_id: 1, billing_cycle: 'monthly', status: 'past_due', started_at: '2026-03-02', current_period_start: '2026-10-02', current_period_end: '2026-11-01' },
        { id: 5, vendor_id: 5, plan_id: 1, billing_cycle: 'monthly', status: 'suspended', started_at: '2026-04-18', current_period_start: '2026-08-18', current_period_end: '2026-09-17' },
        { id: 6, vendor_id: 6, plan_id: 1, billing_cycle: 'monthly', status: 'cancelled', started_at: '2026-02-01', current_period_start: '2026-06-01', current_period_end: '2026-06-30' },
        { id: 7, vendor_id: 7, plan_id: 3, billing_cycle: 'monthly', status: 'active', started_at: '2026-01-20', current_period_start: '2026-10-20', current_period_end: '2026-11-19' }
    ],

    invoices: [
        { id: 1, invoice_no: 'INV-2026-0001', vendor_id: 1, period_start: '2026-01-05', period_end: '2027-01-04', subtotal: 178000, tax: 14240, total: 192240, status: 'paid',
          issued_at: '2026-01-05T09:00:00+08:00', due_at: '2026-01-12T23:59:59+08:00', paid_at: '2026-01-06T10:12:00+08:00',
          lines: [
              { description: 'Pelan Pro (tahunan)', item_type: 'plan', qty: 1, unit_price: 99000, amount: 99000, is_prorated: false },
              { description: 'Koperasi tambahan: SJKC Chung Hwa', item_type: 'cooperative', qty: 1, unit_price: 79000, amount: 79000, is_prorated: false }
          ],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-88812', amount: 192240, paid_at: '2026-01-06T10:12:00+08:00' }] },
        { id: 2, invoice_no: 'INV-2026-0046', vendor_id: 2, period_start: '2026-10-11', period_end: '2026-11-10', subtotal: 5900, tax: 472, total: 6372, status: 'paid',
          issued_at: '2026-10-11T00:05:00+08:00', due_at: '2026-10-18T23:59:59+08:00', paid_at: '2026-10-12T08:40:00+08:00',
          lines: [
              { description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false },
              { description: 'Modul Jualan murah: SMK Bukit Indah', item_type: 'module', qty: 1, unit_price: 1000, amount: 1000, is_prorated: false }
          ],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-90211', amount: 6372, paid_at: '2026-10-12T08:40:00+08:00' }] },
        { id: 3, invoice_no: 'INV-2026-0041', vendor_id: 4, period_start: '2026-10-02', period_end: '2026-11-01', subtotal: 4900, tax: 392, total: 5292, status: 'issued',
          issued_at: '2026-10-02T00:05:00+08:00', due_at: '2026-10-09T23:59:59+08:00', paid_at: null,
          lines: [{ description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false }],
          payments: [] },
        { id: 4, invoice_no: 'INV-2026-0032', vendor_id: 5, period_start: '2026-08-18', period_end: '2026-09-17', subtotal: 6400, tax: 512, total: 6912, status: 'issued',
          issued_at: '2026-08-18T00:05:00+08:00', due_at: '2026-08-25T23:59:59+08:00', paid_at: null,
          lines: [
              { description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false },
              { description: 'Modul Berita: SMKA Al-Amin', item_type: 'module', qty: 1, unit_price: 1500, amount: 1500, is_prorated: false }
          ],
          payments: [] },
        { id: 5, invoice_no: 'INV-2026-0048', vendor_id: 7, period_start: '2026-10-20', period_end: '2026-11-19', subtotal: 49300, tax: 3944, total: 53244, status: 'issued',
          issued_at: '2026-10-20T00:05:00+08:00', due_at: '2026-10-27T23:59:59+08:00', paid_at: null,
          lines: [
              { description: 'Pelan Premium (bulanan)', item_type: 'plan', qty: 1, unit_price: 19900, amount: 19900, is_prorated: false },
              { description: 'Koperasi tambahan: SMK Putrajaya Presint 9 (1)', item_type: 'cooperative', qty: 1, unit_price: 9900, amount: 9900, is_prorated: false },
              { description: 'Modul Berita × 2 koperasi', item_type: 'module', qty: 2, unit_price: 1500, amount: 3000, is_prorated: false },
              { description: 'Modul Kalendar cuti: SK Putrajaya Presint 9 (1)', item_type: 'module', qty: 1, unit_price: 500, amount: 500, is_prorated: false },
              { description: 'Modul Apps telefon: SK Putrajaya Presint 9 (1)', item_type: 'module', qty: 1, unit_price: 3000, amount: 3000, is_prorated: false },
              { description: 'Template Ceria: SK Putrajaya Presint 9 (1)', item_type: 'template', qty: 1, unit_price: 1000, amount: 1000, is_prorated: false },
              { description: 'Koperasi tambahan: SK Bandar Baru Bangi (prorata 12 hari)', item_type: 'cooperative', qty: 1, unit_price: 12000, amount: 12000, is_prorated: true }
          ],
          payments: [] },
        { id: 6, invoice_no: 'INV-2026-0049', vendor_id: 3, period_start: '2026-11-04', period_end: '2026-12-03', subtotal: 4900, tax: 392, total: 5292, status: 'draft',
          issued_at: null, due_at: null, paid_at: null,
          lines: [{ description: 'Pelan Asas (bulanan) — selepas trial', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false }],
          payments: [] },
        { id: 7, invoice_no: 'INV-2026-0012', vendor_id: 6, period_start: '2026-06-01', period_end: '2026-06-30', subtotal: 4900, tax: 392, total: 5292, status: 'void',
          issued_at: '2026-06-01T00:05:00+08:00', due_at: '2026-06-08T23:59:59+08:00', paid_at: null,
          lines: [{ description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false }],
          payments: [] }
    ],

    // Contoh jadual terjemahan (Peringkat 2: dijana dari jadual translations)
    translations: [
        { key: 'menu.dashboard', values: { ms: 'Papan pemuka', en: 'Dashboard', zh: '仪表板', ta: 'முகப்புப் பலகை' } },
        { key: 'menu.orders', values: { ms: 'Pesanan online', en: 'Online orders', zh: '网上订单', ta: 'இணைய ஆர்டர்கள்' } },
        { key: 'menu.handover', values: { ms: 'Serah barang', en: 'Hand over items', zh: '交付物品', ta: 'பொருள் ஒப்படைப்பு' } },
        { key: 'btn.save', values: { ms: 'Simpan', en: 'Save', zh: '保存', ta: 'சேமி' } },
        { key: 'btn.cancel', values: { ms: 'Batal', en: 'Cancel', zh: '取消', ta: 'ரத்து செய்' } },
        { key: 'order.status.ready', values: { ms: 'Sedia diambil', en: 'Ready for collection', zh: '可领取', ta: 'பெறத் தயார்' } },
        { key: 'order.status.collected', values: { ms: 'Telah diambil', en: 'Collected', zh: '已领取', ta: 'பெறப்பட்டது' } },
        { key: 'order.status.unpaid_cash', values: { ms: 'Bayar tunai semasa ambil', en: 'Pay cash on collection', zh: '领取时付现金', ta: '' } },
        { key: 'pickup.collected_by', values: { ms: 'Diambil oleh {name} ({class}) pada {time}', en: 'Collected by {name} ({class}) at {time}', zh: '', ta: '' } },
        { key: 'store.kit.uncheck_hint', values: { ms: 'Nyahtanda barang yang tidak diperlukan.', en: 'Untick items you do not need.', zh: '取消勾选不需要的物品。', ta: '' } },
        { key: 'store.checkout.pickup_by', values: { ms: 'Siapa akan ambil barang?', en: 'Who will collect the items?', zh: '', ta: '' } },
        { key: 'email.order_ready.subject', values: { ms: 'Pesanan {order_no} sedia diambil', en: 'Order {order_no} is ready for collection', zh: '订单 {order_no} 可领取', ta: 'ஆர்டர் {order_no} பெறத் தயார்' } },
        { key: 'grade.D1', values: { ms: 'Darjah 1', en: 'Year 1', zh: '一年级', ta: 'ஆண்டு 1' } },
        { key: 'grade.T1', values: { ms: 'Tingkatan 1', en: 'Form 1', zh: '中一', ta: 'படிவம் 1' } },
        { key: 'common.no_data', values: { ms: 'Tiada data untuk dipaparkan.', en: 'No data to display.', zh: '没有可显示的数据。', ta: 'காட்ட தரவு இல்லை.' } },
        { key: 'error.network', values: { ms: 'Gagal menghubungi pelayan. Sila cuba lagi.', en: 'Could not reach the server. Please try again.', zh: '', ta: '' } },
        { key: 'vendor.closed_notice', values: { ms: 'Koperasi ditutup sementara', en: 'Cooperative temporarily closed', zh: '合作社暂时关闭', ta: '' } }
    ],

    // Kadar SST untuk paparan invois mock (tetapan sebenar: owner/settings/get — B3)
    settings: { sst_rate: 8 },

    planById: function (id) {
        return Mock.findById(MockOwner.plans, id);
    },

    vendorById: function (id) {
        return Mock.findById(MockOwner.vendors, id);
    },

    // Log audit sebenar sejak B3 (audit_logs); tindakan invois mock tidak direkod
    audit: function () {}
};

// ---------- Ringkasan (dikira dari data mock) ----------

function mockSubscriptionSummary(sub) {
    var vendor = MockOwner.vendorById(sub.vendor_id);
    var plan = MockOwner.planById(sub.plan_id);
    var coopCount = MockOwner.cooperatives.filter(function (coop) {
        return coop.vendor_id === sub.vendor_id && coop.status !== 'closed';
    }).length;

    return {
        id: sub.id, vendor_id: sub.vendor_id, vendor_code: vendor.code, vendor_name: vendor.company_name,
        plan_id: sub.plan_id, plan_name: plan.name, billing_cycle: sub.billing_cycle, status: sub.status,
        started_at: sub.started_at, current_period_start: sub.current_period_start, current_period_end: sub.current_period_end,
        cooperative_count: coopCount
    };
}

function mockInvoiceSummary(invoice) {
    var vendor = MockOwner.vendorById(invoice.vendor_id);
    return {
        id: invoice.id, invoice_no: invoice.invoice_no, vendor_id: invoice.vendor_id, vendor_code: vendor.code,
        vendor_name: vendor.company_name, period_start: invoice.period_start, period_end: invoice.period_end,
        subtotal: invoice.subtotal, tax: invoice.tax, total: invoice.total, status: invoice.status,
        issued_at: invoice.issued_at, due_at: invoice.due_at, paid_at: invoice.paid_at
    };
}

// ---------- Langganan & invois ----------

Mock.register('owner/subscriptions/list', function (params) {
    var rows = Mock.filterBy(MockOwner.subscriptions, params, ['status', 'billing_cycle', 'plan_id']).map(mockSubscriptionSummary);
    return Mock.ok(Mock.paginate(rows, params, ['vendor_code', 'vendor_name', 'plan_name']));
});

Mock.register('owner/invoices/list', function (params) {
    var rows = Mock.filterBy(MockOwner.invoices, params, ['status', 'vendor_id']).slice().sort(function (a, b) {
        return b.id - a.id;
    }).map(mockInvoiceSummary);
    return Mock.ok(Mock.paginate(rows, params, ['invoice_no', 'vendor_code', 'vendor_name']));
});

Mock.register('owner/invoices/get', function (params) {
    var invoice = Mock.findById(MockOwner.invoices, params.id);
    if (!invoice) {
        return Mock.fail(T('error.not_found'));
    }
    var data = mockInvoiceSummary(invoice);
    var vendor = MockOwner.vendorById(invoice.vendor_id);
    data.vendor = { id: vendor.id, code: vendor.code, company_name: vendor.company_name, registration_no: vendor.registration_no, address: vendor.address, email: vendor.email };
    data.lines = invoice.lines;
    data.payments = invoice.payments;
    data.sst_rate = MockOwner.settings.sst_rate;
    return Mock.ok(data);
});

// Tanda dibayar secara manual (pindahan bank / tunai / lain-lain)
Mock.register('owner/invoices/mark-paid', function (body) {
    var invoice = Mock.findById(MockOwner.invoices, body.id);
    if (!invoice) {
        return Mock.fail(T('error.not_found'));
    }
    if (invoice.status !== 'issued') {
        return Mock.fail(T('invoice.cannot_mark_paid'));
    }
    var errors = Mock.requireFields(body, ['method', 'reference', 'paid_at']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var paidAt = body.paid_at + 'T12:00:00+08:00';
    invoice.status = 'paid';
    invoice.paid_at = paidAt;
    invoice.payments.push({ method: body.method, gateway_code: null, reference: body.reference, amount: invoice.total, paid_at: paidAt, note: body.note || '' });
    MockOwner.audit('invoice.mark_paid', 'invoice', invoice.id, body.reference);
    return Mock.ok(mockInvoiceSummary(invoice));
});

Mock.register('owner/invoices/issue', function (body) {
    var invoice = Mock.findById(MockOwner.invoices, body.id);
    if (!invoice || invoice.status !== 'draft') {
        return Mock.fail(T('error.invalid'));
    }
    invoice.status = 'issued';
    invoice.issued_at = Mock.now();
    invoice.due_at = '2026-10-31T23:59:59+08:00';
    MockOwner.audit('invoice.issue', 'invoice', invoice.id, invoice.invoice_no);
    return Mock.ok(mockInvoiceSummary(invoice));
});

Mock.register('owner/invoices/void', function (body) {
    var invoice = Mock.findById(MockOwner.invoices, body.id);
    if (!invoice || ['draft', 'issued'].indexOf(invoice.status) === -1) {
        return Mock.fail(T('error.invalid'));
    }
    invoice.status = 'void';
    MockOwner.audit('invoice.void', 'invoice', invoice.id, invoice.invoice_no);
    return Mock.ok(mockInvoiceSummary(invoice));
});

// ---------- Bahasa & terjemahan ----------

Mock.register('owner/translations/list', function (params) {
    var languages = ['ms', 'en', 'zh', 'ta'];
    var rows = MockOwner.translations.map(function (row) {
        var missing = languages.filter(function (code) {
            return !String(row.values[code] || '').trim();
        });
        return { key: row.key, group: row.key.split('.')[0], values: row.values, missing: missing };
    }).filter(function (row) {
        if (params.missing && row.missing.indexOf(params.missing) === -1 && !(params.missing === 'any' && row.missing.length)) {
            return false;
        }
        return !params.group || row.group === params.group;
    });

    var q = String(params.q || '').toLowerCase();
    if (q) {
        rows = rows.filter(function (row) {
            return row.key.toLowerCase().indexOf(q) !== -1 || languages.some(function (code) {
                return String(row.values[code] || '').toLowerCase().indexOf(q) !== -1;
            });
        });
    }

    var data = Mock.paginate(rows, { page: params.page, per_page: params.per_page }, []);
    data.groups = MockOwner.translations.map(function (row) {
        return row.key.split('.')[0];
    }).filter(function (group, index, all) {
        return all.indexOf(group) === index;
    }).sort();
    data.missing_counts = {};
    languages.forEach(function (code) {
        data.missing_counts[code] = MockOwner.translations.filter(function (row) {
            return !String(row.values[code] || '').trim();
        }).length;
    });
    return Mock.ok(data);
});

// Nilai Melayu wajib — ia jatuh balik untuk bahasa lain
Mock.register('owner/translations/save', function (body) {
    var row = MockOwner.translations.filter(function (item) { return item.key === body.key; })[0];
    if (!row) {
        return Mock.fail(T('error.not_found'));
    }
    if (!String((body.values || {}).ms || '').trim()) {
        return Mock.fail(T('translation.ms_required'), { 'values.ms': T('error.required') });
    }
    row.values = body.values;
    MockOwner.audit('translation.update', 'translation', row.key, '');
    return Mock.ok(row);
});

// Kekalkan perubahan antara halaman (sessionStorage)
Mock.persist('owner', MockOwner, ['plans', 'vendors', 'cooperatives', 'subscriptions', 'invoices', 'translations']);
