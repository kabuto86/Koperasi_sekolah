// Data mock vendor bahagian 3 (U3c): langganan & bil, kedai modul, log aktiviti.
// Bayaran (B8), promosi (B10), kakitangan (B2), tetapan (B9), refund & laporan (B11) kini laluan sebenar (api/v1/vendor/).
// Bergantung pada mock/vendor.js (MockVendor, mockRequireCoop …).

var MockFinance = {
    // vendor_users + vendor_user_cooperatives. vendor_admin: semua koperasi (cooperative_ids diabaikan)
    staff: [
        { id: 10, vendor_id: 1, full_name: 'Ahmad bin Ismail', email: 'ahmad@niagasekolah.my', phone: '012-3456789', role: 'vendor_admin',
          cooperative_ids: [], is_active: true, pin_set: false, totp_enabled: true, last_login_at: '2026-10-25T08:02:00+08:00' },
        { id: 11, vendor_id: 1, full_name: 'Siti Nurhaliza binti Kassim', email: 'siti.n@niagasekolah.my', phone: '013-4455667', role: 'coop_manager',
          cooperative_ids: [1], is_active: true, pin_set: false, totp_enabled: false, last_login_at: '2026-10-24T07:45:00+08:00' },
        { id: 12, vendor_id: 1, full_name: 'Siti Hajar binti Yusof', email: 'hajar@niagasekolah.my', phone: '017-2233445', role: 'cashier',
          cooperative_ids: [1], is_active: true, pin_set: true, totp_enabled: false, last_login_at: '2026-10-24T10:10:00+08:00' },
        { id: 13, vendor_id: 1, full_name: 'Lee Chong Hui', email: 'chonghui@niagasekolah.my', phone: '016-8899001', role: 'cashier',
          cooperative_ids: [2], is_active: true, pin_set: true, totp_enabled: false, last_login_at: '2026-10-23T07:30:00+08:00' },
        { id: 14, vendor_id: 1, full_name: 'Mohd Faiz bin Rosli', email: 'faiz@niagasekolah.my', phone: '011-22334455', role: 'cashier',
          cooperative_ids: [1, 2], is_active: false, pin_set: false, totp_enabled: false, last_login_at: '2026-06-30T12:00:00+08:00' },
        { id: 20, vendor_id: 2, full_name: 'Lim Mei Ling', email: 'meiling@bestari.my', phone: '017-2223344', role: 'vendor_admin',
          cooperative_ids: [], is_active: true, pin_set: false, totp_enabled: false, last_login_at: '2026-10-24T09:00:00+08:00' },
        { id: 21, vendor_id: 2, full_name: 'Azman bin Salleh', email: 'azman@bestari.my', phone: '019-3332211', role: 'cashier',
          cooperative_ids: [3], is_active: true, pin_set: true, totp_enabled: false, last_login_at: '2026-10-24T08:00:00+08:00' }
    ],
    sessionUserId: 10,

    // Katalog pelan (salinan owner)
    plans: [
        { id: 1, code: 'ASAS', name: 'Asas', price_monthly: 4900, price_yearly: 49000, max_cooperatives: 2, max_products: 300, max_staff: 3,
          features: ['Store ibu bapa', 'Kit sekolah', 'Serah barang'] },
        { id: 2, code: 'PRO', name: 'Pro', price_monthly: 9900, price_yearly: 99000, max_cooperatives: 5, max_products: 2000, max_staff: 10,
          features: ['Semua dalam Asas', 'POS kaunter', 'Laporan penuh'] },
        { id: 3, code: 'PREMIUM', name: 'Premium', price_monthly: 19900, price_yearly: 199000, max_cooperatives: null, max_products: null, max_staff: null,
          features: ['Semua dalam Pro', 'Koperasi tanpa had', 'Sokongan keutamaan'] }
    ],

    subscriptions: {
        1: { plan_id: 2, billing_cycle: 'yearly', status: 'active', started_at: '2026-01-05', current_period_start: '2026-01-05',
             current_period_end: '2027-01-04', pending_change: null },
        2: { plan_id: 1, billing_cycle: 'monthly', status: 'active', started_at: '2026-02-11', current_period_start: '2026-10-11',
             current_period_end: '2026-11-10', pending_change: null }
    },

    // Katalog modul add-on (nama & keterangan melalui kunci terjemahan module.{code} / addon.desc.{code})
    modules: [
        { code: 'news', price_monthly: 1500, price_yearly: 15000, is_active: true },
        { code: 'clearance', price_monthly: 1000, price_yearly: 10000, is_active: true },
        { code: 'calendar', price_monthly: 500, price_yearly: 5000, is_active: true },
        { code: 'school_opening', price_monthly: 2000, price_yearly: 20000, is_active: true },
        { code: 'mobile_app', price_monthly: 3000, price_yearly: 30000, is_active: true },
        { code: 'whatsapp_sms', price_monthly: 2500, price_yearly: 25000, is_active: true },
        { code: 'advanced_report', price_monthly: 2000, price_yearly: 20000, is_active: true },
        { code: 'membership', price_monthly: 3000, price_yearly: 30000, is_active: false }
    ],

    // cooperative_modules (status aktif juga dicerminkan dalam MockVendor.activeModules untuk menu)
    moduleSubs: [
        { vendor_id: 1, cooperative_id: 1, code: 'news', started_at: '2026-01-05', cancel_at_period_end: false },
        { vendor_id: 1, cooperative_id: 1, code: 'school_opening', started_at: '2026-10-10', cancel_at_period_end: false },
        { vendor_id: 1, cooperative_id: 1, code: 'calendar', started_at: '2026-03-02', cancel_at_period_end: false },
        { vendor_id: 1, cooperative_id: 1, code: 'whatsapp_sms', started_at: '2026-08-15', cancel_at_period_end: false },
        { vendor_id: 2, cooperative_id: 3, code: 'clearance', started_at: '2026-04-11', cancel_at_period_end: false }
    ],

    // Template premium dilanggan per koperasi
    templateSubs: [
        { vendor_id: 1, cooperative_id: 2, template_id: 2, started_at: '2026-02-14' }
    ],

    sstRate: 8,
    invoiceSeq: 51,

    invoices: [
        { id: 1, vendor_id: 1, invoice_no: 'INV-2026-0001', period_start: '2026-01-05', period_end: '2027-01-04', subtotal: 193000, tax: 15440, total: 208440,
          status: 'paid', issued_at: '2026-01-05T09:00:00+08:00', due_at: '2026-01-12T23:59:59+08:00', paid_at: '2026-01-06T10:12:00+08:00',
          lines: [
              { description: 'Pelan Pro (tahunan)', item_type: 'plan', qty: 1, unit_price: 99000, amount: 99000, is_prorated: false },
              { description: 'Koperasi tambahan: SJKC Chung Hwa', item_type: 'cooperative', qty: 1, unit_price: 79000, amount: 79000, is_prorated: false },
              { description: 'Modul Berita & pengumuman: SK Taman Melati', item_type: 'module', qty: 1, unit_price: 15000, amount: 15000, is_prorated: false }
          ],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-88812', amount: 208440, paid_at: '2026-01-06T10:12:00+08:00' }] },
        { id: 2, vendor_id: 1, invoice_no: 'INV-2026-0039', period_start: '2026-10-10', period_end: '2027-01-04', subtotal: 4712, tax: 377, total: 5089,
          status: 'paid', issued_at: '2026-10-10T10:00:00+08:00', due_at: '2026-10-17T23:59:59+08:00', paid_at: '2026-10-10T10:05:00+08:00',
          lines: [{ description: 'Modul Pembukaan sekolah: SK Taman Melati (prorata 87 hari)', item_type: 'module', qty: 1, unit_price: 4712, amount: 4712, is_prorated: true }],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-91877', amount: 5089, paid_at: '2026-10-10T10:05:00+08:00' }] },
        { id: 3, vendor_id: 1, invoice_no: 'INV-2026-0050', period_start: '2026-10-22', period_end: '2027-01-04', subtotal: 2055, tax: 164, total: 2219,
          status: 'issued', issued_at: '2026-10-22T09:00:00+08:00', due_at: '2026-10-29T23:59:59+08:00', paid_at: null,
          lines: [{ description: 'Template 2 — Ceria: SJKC Chung Hwa (prorata 75 hari)', item_type: 'template', qty: 1, unit_price: 2055, amount: 2055, is_prorated: true }],
          payments: [] },
        { id: 4, vendor_id: 2, invoice_no: 'INV-2026-0046', period_start: '2026-10-11', period_end: '2026-11-10', subtotal: 5900, tax: 472, total: 6372,
          status: 'paid', issued_at: '2026-10-11T00:05:00+08:00', due_at: '2026-10-18T23:59:59+08:00', paid_at: '2026-10-12T08:40:00+08:00',
          lines: [
              { description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false },
              { description: 'Modul Jualan murah: SMK Bukit Indah', item_type: 'module', qty: 1, unit_price: 1000, amount: 1000, is_prorated: false }
          ],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-90211', amount: 6372, paid_at: '2026-10-12T08:40:00+08:00' }] }
    ],

    // activity_logs — action ialah kod; label melalui T('activity.action.' + action)
    activity: []
};

// ---------- Data contoh: log aktiviti ----------

(function seedFinance() {
    var rows = [
        [1, 1, 10, 'login', null, null, '', '2026-10-25T08:02:00+08:00'],
        [1, 1, 12, 'handover_collect', 'order', 5, 'TM-2610-0005', '2026-10-24T10:15:00+08:00'],
        [1, 1, 10, 'refund_create', 'refund', 1, 'RF-1001 · RM 2.50', '2026-10-24T11:30:00+08:00'],
        [1, 1, 11, 'order_status', 'order', 3, 'TM-2610-0003 → Sedia diambil', '2026-10-24T09:12:00+08:00'],
        [1, 1, 11, 'stock_adjust', 'product', 8, 'Pembaris 30cm · Rosak', '2026-10-23T14:40:00+08:00'],
        [1, 1, 10, 'promotion_save', 'promotion', 1, 'SEKOLAH27', '2026-10-22T16:05:00+08:00'],
        [1, 2, 10, 'template_subscribe', 'template', 2, 'Template 2 — Ceria', '2026-10-22T09:00:00+08:00'],
        [1, 1, 10, 'kit_publish', 'school_kit', 4, 'Darjah 4 · 2026', '2026-10-21T15:30:00+08:00'],
        [1, 2, 13, 'po_receive', 'purchase_order', 5, 'PO-2026-0008', '2026-10-20T11:00:00+08:00'],
        [1, null, 10, 'staff_save', 'vendor_user', 13, 'Lee Chong Hui', '2026-10-15T10:00:00+08:00'],
        [1, 1, 10, 'module_subscribe', 'module', 4, 'Pembukaan sekolah', '2026-10-10T10:00:00+08:00'],
        [1, 1, 10, 'settings_save', 'cooperative', 1, '', '2026-10-09T17:20:00+08:00'],
        [1, 1, 10, 'gateway_save', 'cooperative', 1, 'ToyyibPay · live', '2026-10-01T09:00:00+08:00'],
        [1, null, 10, 'invoice_pay', 'invoice', 2, 'INV-2026-0039', '2026-10-10T10:05:00+08:00'],
        [2, 3, 20, 'login', null, null, '', '2026-10-24T09:00:00+08:00'],
        [2, 3, 20, 'promotion_save', 'promotion', 6, 'BI10', '2026-10-01T09:30:00+08:00']
    ];
    rows.forEach(function (row, index) {
        MockFinance.activity.push({ id: index + 1, vendor_id: row[0], cooperative_id: row[1], user_id: row[2], action: row[3], entity: row[4],
            entity_id: row[5], detail: row[6], created_at: row[7] });
    });
    MockFinance.activity.sort(function (a, b) { return a.created_at < b.created_at ? 1 : -1; });
})();

// ---------- Pembantu ----------

function mockToday() {
    return Mock.now().slice(0, 10);
}

// Bilangan hari dari a hingga b (termasuk kedua-dua hari). Tarikh 'YYYY-MM-DD'
function mockDaysInclusive(a, b) {
    var from = Date.UTC(+a.slice(0, 4), +a.slice(5, 7) - 1, +a.slice(8, 10));
    var to = Date.UTC(+b.slice(0, 4), +b.slice(5, 7) - 1, +b.slice(8, 10));
    return Math.round((to - from) / 86400000) + 1;
}

// 'YYYY-MM-DD' n hari dari hari ini (zon Asia/Kuala_Lumpur)
function mockDaysFromToday(days) {
    return new Date(Date.now() + days * 86400000 + 8 * 3600000).toISOString().slice(0, 10);
}

function mockSessionUser() {
    return Mock.findById(MockFinance.staff, MockFinance.sessionUserId);
}

function mockStaffName(id) {
    var user = Mock.findById(MockFinance.staff, id);
    return user ? user.full_name : '';
}

// Rekod log aktiviti (Peringkat 2: jadual activity_logs, ditulis oleh job)
function mockActivity(action, cooperativeId, entity, entityId, detail) {
    MockFinance.activity.unshift({ id: Mock.nextId(MockFinance.activity), vendor_id: MockVendor.sessionVendorId, cooperative_id: cooperativeId || null,
        user_id: MockFinance.sessionUserId, action: action, entity: entity || null, entity_id: entityId || null, detail: detail || '', created_at: Mock.now() });
}

function mockVendorSubscription() {
    return MockFinance.subscriptions[MockVendor.sessionVendorId];
}

function mockPlan(id) {
    return Mock.findById(MockFinance.plans, id);
}

function mockCyclePrice(row, cycle) {
    return cycle === 'yearly' ? row.price_yearly : row.price_monthly;
}

function mockActiveCoops() {
    return mockOwnRows(MockVendor.cooperatives).filter(function (coop) { return coop.status !== 'closed'; })
        .sort(function (a, b) { return a.id - b.id; });
}

// Harga koperasi ke-n (n = 1 termasuk dalam pelan) ikut peringkat pelan vendor
function mockCoopPrice(number, cycle) {
    if (number <= 1) {
        return 0;
    }
    var tier = MockVendor.plans[MockVendor.sessionVendorId].tiers.filter(function (row) {
        return number >= row.from_coop && (row.to_coop === null || number <= row.to_coop);
    })[0];
    return tier ? mockCyclePrice(tier, cycle) : 0;
}

function mockModuleSub(cooperativeId, code) {
    return mockCoopRows(MockFinance.moduleSubs, cooperativeId).filter(function (row) { return row.code === code; })[0] || null;
}

function mockTemplateSubscribed(cooperativeId, templateId) {
    var template = Mock.findById(MockVendor.templates, templateId);
    return Boolean(template && template.is_free) || mockCoopRows(MockFinance.templateSubs, cooperativeId).some(function (row) {
        return row.template_id === Number(templateId);
    });
}

// Prorata: harga kitaran × baki hari / hari dalam tempoh (PELAN 14)
function mockProrate(price) {
    var sub = mockVendorSubscription();
    var today = mockToday();
    var periodDays = mockDaysInclusive(sub.current_period_start, sub.current_period_end);
    var daysLeft = Math.max(1, mockDaysInclusive(today, sub.current_period_end));
    var amount = Math.round(price * daysLeft / periodDays);
    return { period_start: today, period_end: sub.current_period_end, days_left: daysLeft, period_days: periodDays,
        amount: amount, tax: Math.round(amount * MockFinance.sstRate / 100), total: amount + Math.round(amount * MockFinance.sstRate / 100) };
}

function mockInvoiceSummary(invoice) {
    return { id: invoice.id, invoice_no: invoice.invoice_no, period_start: invoice.period_start, period_end: invoice.period_end,
        subtotal: invoice.subtotal, tax: invoice.tax, total: invoice.total, status: invoice.status, issued_at: invoice.issued_at,
        due_at: invoice.due_at, paid_at: invoice.paid_at, line_count: invoice.lines.length };
}

function mockOwnInvoice(id) {
    return mockOwnRows(MockFinance.invoices).filter(function (row) { return row.id === Number(id); })[0] || null;
}

// Bayaran: laluan sebenar sejak B8 (api/v1/vendor/payments/, jobs/Payment/)

// Pemulangan & refund: laluan sebenar sejak B11 (api/v1/vendor/refunds/, jobs/Refund/)

// Promosi / kupon: laluan sebenar sejak B10 (api/v1/vendor/promotions/*, jobs/Promotion/)

// Kakitangan vendor: laluan sebenar sejak B2 (api/v1/vendor/staff/*)

// Tetapan koperasi: laluan sebenar sejak B9 (api/v1/vendor/settings/*)

// ---------- Langganan & bil ----------

function mockSubscriptionData() {
    var sub = mockVendorSubscription();
    var plan = mockPlan(sub.plan_id);
    var cycle = sub.billing_cycle;
    var coops = mockActiveCoops();
    var lines = [{ type: 'plan', label: plan.name, cooperative_name: null, price: mockCyclePrice(plan, cycle) }];

    var cooperatives = coops.map(function (coop, index) {
        var number = index + 1;
        var modules = mockCoopRows(MockFinance.moduleSubs, coop.id).map(function (row) {
            var module = MockFinance.modules.filter(function (item) { return item.code === row.code; })[0];
            lines.push({ type: 'module', label: row.code, cooperative_name: coop.school_name, price: mockCyclePrice(module, cycle), cancelling: row.cancel_at_period_end });
            return { code: row.code, price: mockCyclePrice(module, cycle), started_at: row.started_at, cancel_at_period_end: row.cancel_at_period_end };
        });
        var template = Mock.findById(MockVendor.templates, coop.template_id);
        var templatePrice = template && !template.is_free ? mockCyclePrice(template, cycle) : 0;
        if (number > 1) {
            lines.push({ type: 'cooperative', label: coop.school_name, cooperative_name: coop.school_name, price: mockCoopPrice(number, cycle) });
        }
        if (templatePrice) {
            lines.push({ type: 'template', label: template.name, cooperative_name: coop.school_name, price: templatePrice });
        }
        return { id: coop.id, school_name: coop.school_name, number: number, included: number === 1, price: mockCoopPrice(number, cycle),
            template_name: template ? template.name : '', template_price: templatePrice, modules: modules };
    });

    var subtotal = lines.reduce(function (sum, line) { return sum + (line.cancelling ? 0 : line.price); }, 0);
    var pending = sub.pending_change ? Object.assign({ plan_name: mockPlan(sub.pending_change.plan_id).name }, sub.pending_change) : null;
    var unpaid = mockOwnRows(MockFinance.invoices).filter(function (row) { return row.status === 'issued'; });
    return {
        plan: { id: plan.id, code: plan.code, name: plan.name, price_monthly: plan.price_monthly, price_yearly: plan.price_yearly,
            max_cooperatives: plan.max_cooperatives, max_products: plan.max_products, max_staff: plan.max_staff, features: plan.features },
        billing_cycle: cycle, status: sub.status, started_at: sub.started_at, current_period_start: sub.current_period_start,
        current_period_end: sub.current_period_end, days_left: Math.max(0, mockDaysInclusive(mockToday(), sub.current_period_end) - 1),
        usage: {
            cooperatives: coops.length,
            products: mockOwnRows(MockVendor.products).length,
            staff: mockOwnRows(MockFinance.staff).filter(function (row) { return row.is_active; }).length
        },
        cooperatives: cooperatives,
        renewal: { lines: lines, subtotal: subtotal, tax: Math.round(subtotal * MockFinance.sstRate / 100),
            total: subtotal + Math.round(subtotal * MockFinance.sstRate / 100), sst_rate: MockFinance.sstRate },
        pending_change: pending,
        unpaid_count: unpaid.length,
        unpaid_total: unpaid.reduce(function (sum, row) { return sum + row.total; }, 0)
    };
}

Mock.register('vendor/subscription/get', function () {
    return Mock.ok(mockSubscriptionData());
});

Mock.register('vendor/subscription/plans', function () {
    var sub = mockVendorSubscription();
    return Mock.ok({ current_plan_id: sub.plan_id, billing_cycle: sub.billing_cycle, plans: MockFinance.plans });
});

// Tukar pelan/kitaran berkuat kuasa pada tarikh pembaharuan (tiada prorata untuk turun taraf)
Mock.register('vendor/subscription/change', function (body) {
    var sub = mockVendorSubscription();
    var plan = mockPlan(body.plan_id);
    if (!plan || ['monthly', 'yearly'].indexOf(body.billing_cycle) === -1) {
        return Mock.fail(T('error.invalid'));
    }
    if (plan.id === sub.plan_id && body.billing_cycle === sub.billing_cycle) {
        sub.pending_change = null;
        return Mock.ok(mockSubscriptionData());
    }
    var usage = mockSubscriptionData().usage;
    var over = (plan.max_cooperatives !== null && usage.cooperatives > plan.max_cooperatives)
        || (plan.max_products !== null && usage.products > plan.max_products)
        || (plan.max_staff !== null && usage.staff > plan.max_staff);
    if (over) {
        return Mock.fail(T('billing.downgrade_blocked', { plan: plan.name }));
    }
    sub.pending_change = { plan_id: plan.id, billing_cycle: body.billing_cycle, effective_at: sub.current_period_end };
    mockActivity('subscription_change', null, 'subscription', MockVendor.sessionVendorId, plan.name + ' · ' + T('billing.cycle.' + body.billing_cycle));
    return Mock.ok(mockSubscriptionData());
});

Mock.register('vendor/subscription/cancel-change', function () {
    mockVendorSubscription().pending_change = null;
    return Mock.ok(mockSubscriptionData());
});

// ---------- Kedai modul add-on & template ----------

Mock.register('vendor/addons/catalog', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var sub = mockVendorSubscription();
    return Mock.ok({
        billing_cycle: sub.billing_cycle, current_period_end: sub.current_period_end,
        modules: MockFinance.modules.map(function (module) {
            var row = mockModuleSub(coop.id, module.code);
            var status = !module.is_active ? 'unavailable' : (row ? (row.cancel_at_period_end ? 'cancelling' : 'active') : 'available');
            return { code: module.code, price_monthly: module.price_monthly, price_yearly: module.price_yearly,
                price: mockCyclePrice(module, sub.billing_cycle), status: status, started_at: row ? row.started_at : null };
        }),
        templates: MockVendor.templates.map(function (template) {
            return { id: template.id, code: template.code, name: template.name, is_free: template.is_free,
                price: mockCyclePrice(template, sub.billing_cycle), subscribed: mockTemplateSubscribed(coop.id, template.id),
                in_use: coop.template_id === template.id };
        })
    });
});

function mockAddonItem(body, coop) {
    if (body.item_type === 'module') {
        var module = MockFinance.modules.filter(function (row) { return row.code === body.code && row.is_active; })[0];
        return module ? { name: T('module.' + module.code), price: mockCyclePrice(module, mockVendorSubscription().billing_cycle), owned: Boolean(mockModuleSub(coop.id, module.code)) } : null;
    }
    if (body.item_type === 'template') {
        var template = MockVendor.templates.filter(function (row) { return row.code === body.code; })[0];
        return template && !template.is_free ? { id: template.id, name: template.name, price: mockCyclePrice(template, mockVendorSubscription().billing_cycle),
            owned: mockTemplateSubscribed(coop.id, template.id) } : null;
    }
    return null;
}

// Anggaran harga prorata hingga hujung tempoh langganan semasa
Mock.register('vendor/addons/quote', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var item = mockAddonItem(params, coop);
    if (!item) {
        return Mock.fail(T('error.not_found'));
    }
    var prorate = mockProrate(item.price);
    return Mock.ok(Object.assign({ item_name: item.name, cooperative_name: coop.school_name, billing_cycle: mockVendorSubscription().billing_cycle,
        unit_price: item.price, sst_rate: MockFinance.sstRate, owned: item.owned }, prorate));
});

// Langgan: aktif serta-merta + invois prorata diterbitkan (bayar dalam tempoh tangguh — PELAN 14)
Mock.register('vendor/addons/subscribe', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var item = mockAddonItem(body, coop);
    if (!item) {
        return Mock.fail(T('error.not_found'));
    }
    if (item.owned) {
        return Mock.fail(T('addon.already_subscribed'));
    }
    var prorate = mockProrate(item.price);
    var invoice = {
        id: Mock.nextId(MockFinance.invoices), vendor_id: MockVendor.sessionVendorId,
        invoice_no: 'INV-2026-' + String(MockFinance.invoiceSeq++).padStart(4, '0'),
        period_start: prorate.period_start, period_end: prorate.period_end, subtotal: prorate.amount, tax: prorate.tax, total: prorate.total,
        status: 'issued', issued_at: Mock.now(), due_at: mockDaysFromToday(7) + 'T23:59:59+08:00', paid_at: null,
        lines: [{ description: T(body.item_type === 'module' ? 'billing.line_module' : 'billing.line_template', { name: item.name, school: coop.school_name, days: prorate.days_left }),
            item_type: body.item_type, qty: 1, unit_price: prorate.amount, amount: prorate.amount, is_prorated: prorate.days_left < prorate.period_days }],
        payments: []
    };
    MockFinance.invoices.push(invoice);

    if (body.item_type === 'module') {
        MockFinance.moduleSubs.push({ vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id, code: body.code, started_at: mockToday(), cancel_at_period_end: false });
        MockVendor.activeModules[coop.id] = (MockVendor.activeModules[coop.id] || []).concat([body.code]);
        mockActivity('module_subscribe', coop.id, 'module', null, item.name);
    } else {
        MockFinance.templateSubs.push({ vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id, template_id: item.id, started_at: mockToday() });
        Mock.findById(MockVendor.templates, item.id).subscribed = true;
        mockActivity('template_subscribe', coop.id, 'template', item.id, item.name);
    }
    return Mock.ok({ invoice_id: invoice.id, invoice_no: invoice.invoice_no, total: invoice.total });
});

// Henti langganan modul pada hujung tempoh (tiada refund; modul kekal aktif hingga tarikh itu)
Mock.register('vendor/addons/cancel', function (body) {
    var coop = mockRequireCoop(body);
    var row = coop ? mockModuleSub(coop.id, body.code) : null;
    if (!row) {
        return mockCoopError();
    }
    row.cancel_at_period_end = !body.undo;
    mockActivity('module_cancel', coop.id, 'module', null, T('module.' + row.code) + (body.undo ? ' ↺' : ''));
    return Mock.ok({ code: row.code, cancel_at_period_end: row.cancel_at_period_end, ends_at: mockVendorSubscription().current_period_end });
});

// ---------- Invois vendor ----------

Mock.register('vendor/invoices/list', function (params) {
    var rows = mockOwnRows(MockFinance.invoices).filter(function (row) {
        return !params.status || row.status === params.status;
    }).sort(function (a, b) { return b.id - a.id; }).map(mockInvoiceSummary);
    return Mock.ok(Mock.paginate(rows, params, ['invoice_no']));
});

Mock.register('vendor/invoices/get', function (params) {
    var invoice = mockOwnInvoice(params.id);
    if (!invoice || invoice.status === 'draft') {
        return mockCoopError();
    }
    var vendor = MockVendor.vendors.filter(function (row) { return row.id === MockVendor.sessionVendorId; })[0];
    var data = JSON.parse(JSON.stringify(invoice));
    delete data.vendor_id;
    data.vendor = { code: vendor.code, company_name: vendor.company_name };
    data.sst_rate = MockFinance.sstRate;
    return Mock.ok(data);
});

// Peringkat 1: simulasi bayaran berjaya. Peringkat 2: pulangkan { redirect_url } ke gateway owner;
// invois ditanda dibayar hanya selepas webhook disahkan (PELAN 13)
Mock.register('vendor/invoices/pay', function (body) {
    var invoice = mockOwnInvoice(body.id);
    if (!invoice) {
        return mockCoopError();
    }
    if (invoice.status !== 'issued') {
        return Mock.fail(T('billing.cannot_pay'));
    }
    invoice.status = 'paid';
    invoice.paid_at = Mock.now();
    invoice.payments.push({ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-' + (92000 + invoice.id), amount: invoice.total, paid_at: invoice.paid_at });
    mockActivity('invoice_pay', null, 'invoice', invoice.id, invoice.invoice_no);
    return Mock.ok({ paid: true, redirect_url: null, invoice: mockInvoiceSummary(invoice) });
});

// ---------- Log aktiviti ----------

Mock.register('vendor/activity-logs/list', function (params) {
    var coopFilter = params.scope === 'current' ? mockRequireCoop(params) : null;
    if (params.scope === 'current' && !coopFilter) {
        return mockCoopError();
    }
    var rows = mockOwnRows(MockFinance.activity).filter(function (row) {
        var day = row.created_at.slice(0, 10);
        return (!coopFilter || row.cooperative_id === coopFilter.id) && (!params.user_id || row.user_id === Number(params.user_id))
            && (!params.date_from || day >= params.date_from) && (!params.date_to || day <= params.date_to);
    }).map(function (row) {
        var coop = row.cooperative_id ? mockOwnCooperative(row.cooperative_id) : null;
        return { id: row.id, cooperative_id: row.cooperative_id, cooperative_name: coop ? coop.school_name : null, user_id: row.user_id,
            user_name: mockStaffName(row.user_id), action: row.action, entity: row.entity, entity_id: row.entity_id, detail: row.detail, created_at: row.created_at };
    });
    var data = Mock.paginate(rows, params, ['user_name', 'detail', 'action']);
    data.users = mockOwnRows(MockFinance.staff).map(function (user) { return { id: user.id, full_name: user.full_name }; });
    return Mock.ok(data);
});

// Laporan: laluan sebenar sejak B11 (api/v1/vendor/reports/run, jobs/Report/)

// Kekalkan perubahan antara halaman — mesti selepas data contoh dijana
Mock.persist('vendor-finance', MockFinance, ['staff', 'subscriptions', 'moduleSubs',
    'templateSubs', 'invoices', 'invoiceSeq', 'activity']);
