// Data mock vendor bahagian 3 (U3c): bayaran, refund, promosi, kakitangan, laporan, tetapan,
// langganan & bil, kedai modul, log aktiviti.
// Bergantung pada mock/vendor.js (MockVendor, mockRequireCoop …) dan mock/vendor-orders.js (MockOrders).

var MockFinance = {
    // Salinan katalog owner (payment_gateways). Vendor hanya melihat driver yang dibenarkan owner
    drivers: [
        { code: 'toyyibpay', name: 'ToyyibPay', allowed_for_vendors: true, supports_refund: false,
          fields: [{ name: 'secret_key', secret: true }, { name: 'category_code', secret: false }] },
        { code: 'billplz', name: 'Billplz', allowed_for_vendors: true, supports_refund: false,
          fields: [{ name: 'api_key', secret: true }, { name: 'collection_id', secret: false }, { name: 'x_signature', secret: true }] },
        { code: 'senangpay', name: 'SenangPay', allowed_for_vendors: false, supports_refund: false,
          fields: [{ name: 'merchant_id', secret: false }, { name: 'secret_key', secret: true }] },
        { code: 'stripe', name: 'Stripe', allowed_for_vendors: true, supports_refund: true,
          fields: [{ name: 'publishable_key', secret: false }, { name: 'secret_key', secret: true }, { name: 'webhook_secret', secret: true }] }
    ],

    // cooperative_gateways. Rahsia disimpan (Peringkat 2: disulitkan sodium) tetapi TIDAK pernah dipulangkan
    gateways: [
        { id: 1, vendor_id: 1, cooperative_id: 1, gateway_code: 'toyyibpay', mode: 'live', is_active: true,
          values: { category_code: 'tmelati01' }, secrets: { secret_key: 'sk-live-xxxx' } },
        { id: 2, vendor_id: 1, cooperative_id: 2, gateway_code: 'billplz', mode: 'sandbox', is_active: true,
          values: { collection_id: 'chunghwa26' }, secrets: { api_key: 'xxxx', x_signature: 'yyyy' } },
        { id: 3, vendor_id: 2, cooperative_id: 3, gateway_code: 'toyyibpay', mode: 'live', is_active: true,
          values: { category_code: 'bukitindah' }, secrets: { secret_key: 'zzzz' } }
    ],

    refunds: [],

    promotions: [
        { id: 1, vendor_id: 1, cooperative_id: 1, name: 'Diskaun pembukaan sekolah 2027', code: 'SEKOLAH27', type: 'percent', value: 5,
          min_spend: 10000, starts_on: '2026-11-01', ends_on: '2027-01-15', max_uses: 500, used_count: 0, is_active: true },
        { id: 2, vendor_id: 1, cooperative_id: 1, name: 'Jimat RM5', code: 'JIMAT5', type: 'fixed', value: 500,
          min_spend: 5000, starts_on: '2026-10-01', ends_on: '2026-10-31', max_uses: 200, used_count: 37, is_active: true },
        { id: 3, vendor_id: 1, cooperative_id: 1, name: 'Promosi Merdeka', code: 'MERDEKA10', type: 'percent', value: 10,
          min_spend: 0, starts_on: '2026-08-25', ends_on: '2026-09-16', max_uses: null, used_count: 88, is_active: true },
        { id: 4, vendor_id: 1, cooperative_id: 1, name: 'Diskaun automatik 3%', code: null, type: 'percent', value: 3,
          min_spend: 20000, starts_on: '2026-01-01', ends_on: '2026-12-31', max_uses: null, used_count: 12, is_active: false },
        { id: 5, vendor_id: 1, cooperative_id: 2, name: '开学优惠 Back to school', code: 'BTS2027', type: 'percent', value: 8,
          min_spend: 5000, starts_on: '2026-12-01', ends_on: '2027-01-10', max_uses: 300, used_count: 0, is_active: true },
        { id: 6, vendor_id: 2, cooperative_id: 3, name: 'Promo SMK Bukit Indah', code: 'BI10', type: 'fixed', value: 1000,
          min_spend: 8000, starts_on: '2026-10-01', ends_on: '2026-12-31', max_uses: 100, used_count: 4, is_active: true }
    ],

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

    // cooperative_settings (pickup_methods & pickup_code_required_above disegerakkan dengan MockOrders.pickupSettings)
    settings: {
        1: { alert_emails: ['ahmad@niagasekolah.my', 'koperasi.tmelati@gmail.com'], receipt_header: 'Koperasi SK Taman Melati',
             receipt_footer: 'Terima kasih. Barang boleh ditukar dalam tempoh 7 hari dengan resit.', receipt_show_logo: true, receipt_paper: '80',
             tax_rate: 0, cash_order_expiry_days: 3, low_stock_default: 5 },
        2: { alert_emails: ['chunghwa.coop@gmail.com'], receipt_header: '中华国民型小学合作社 SJKC Chung Hwa',
             receipt_footer: '谢谢惠顾 Terima kasih', receipt_show_logo: false, receipt_paper: '58',
             tax_rate: 0, cash_order_expiry_days: 3, low_stock_default: 10 },
        3: { alert_emails: ['meiling@bestari.my'], receipt_header: 'Koperasi SMK Bukit Indah', receipt_footer: '',
             receipt_show_logo: true, receipt_paper: '80', tax_rate: 0, cash_order_expiry_days: 5, low_stock_default: 5 }
    },

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

// ---------- Data contoh: refund & log aktiviti ----------

(function seedFinance() {
    // Satu refund contoh: pesanan TM-2610-0006 (sudah diambil), satu buku dipulang.
    // Kesan pada pesanan hanya dikenakan sekali (pesanan yang tersimpan dalam sessionStorage sudah membawanya)
    var order = Mock.findById(MockOrders.orders, 6);
    if (order) {
        var item = order.items[order.items.length - 1];
        if (order.refunded_amount === undefined) {
            item.returned_qty = 1;
            order.refunded_amount = item.unit_price;
        }
        MockFinance.refunds.push({ id: 1, vendor_id: 1, cooperative_id: 1, refund_no: 'RF-1001', order_id: order.id, amount: item.unit_price,
            method: 'bank_transfer', reason: 'Saiz tidak sesuai — ibu bapa pulangkan', restock: true,
            items: [{ order_item_id: item.id, qty: 1, cancelled: 0 }], user_name: 'Ahmad bin Ismail', created_at: '2026-10-24T11:30:00+08:00' });
    }

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

function mockDriver(code) {
    return MockFinance.drivers.filter(function (driver) { return driver.code === code; })[0] || null;
}

function mockCoopGateway(cooperativeId) {
    return mockCoopRows(MockFinance.gateways, cooperativeId).filter(function (row) { return row.is_active; })[0] || null;
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

// Baki item yang belum diambil / dibatalkan / dipulangkan
function mockReturnableQty(item) {
    return item.qty - (item.cancelled_qty || 0) - (item.returned_qty || 0);
}

function mockPromotionState(promo) {
    var today = mockToday();
    if (!promo.is_active) {
        return 'inactive';
    }
    if (promo.max_uses !== null && promo.used_count >= promo.max_uses) {
        return 'used_up';
    }
    if (today < promo.starts_on) {
        return 'scheduled';
    }
    if (today > promo.ends_on) {
        return 'expired';
    }
    return 'active';
}

// ---------- Bayaran: gateway, kaedah & rekod ----------
// Sejak B8: vendor/payments/* sebenar (api/v1/vendor/payments/, jobs/Payment/). MockFinance.gateways kekal untuk refund & POS mock.

// ---------- Pemulangan & refund ----------

function mockRefundSummary(refund) {
    var order = Mock.findById(MockOrders.orders, refund.order_id);
    return { id: refund.id, refund_no: refund.refund_no, order_id: refund.order_id, order_no: order.order_no, short_code: order.short_code,
        customer_name: order.customer_name, amount: refund.amount, method: refund.method, reason: refund.reason, restock: refund.restock,
        item_count: refund.items.reduce(function (sum, row) { return sum + row.qty; }, 0), user_name: refund.user_name, created_at: refund.created_at };
}

Mock.register('vendor/refunds/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockFinance.refunds, coop.id).map(mockRefundSummary).filter(function (row) {
        var day = row.created_at.slice(0, 10);
        return (!params.method || row.method === params.method) && (!params.date_from || day >= params.date_from) && (!params.date_to || day <= params.date_to);
    }).sort(function (a, b) { return a.created_at < b.created_at ? 1 : -1; });
    var data = Mock.paginate(rows, params, ['refund_no', 'order_no', 'short_code', 'customer_name', 'reason']);
    data.total_amount = rows.reduce(function (sum, row) { return sum + row.amount; }, 0);
    return Mock.ok(data);
});

// Cari pesanan yang boleh direfund (dibayar) ikut id / no. pesanan / kod pendek
Mock.register('vendor/refunds/order', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var q = String(params.q || '').trim().toLowerCase();
    var order = params.order_id ? mockFindOrder(params.order_id, coop.id) : mockCoopRows(MockOrders.orders, coop.id).filter(function (row) {
        return q && (row.order_no.toLowerCase() === q || row.short_code.toLowerCase() === q);
    })[0];
    if (!order) {
        return Mock.fail(T('refund.order_not_found'));
    }
    if (order.payment_status !== 'paid') {
        return Mock.fail(T('refund.order_not_paid'));
    }
    var gateway = order.payment_method === 'online' ? mockCoopGateway(coop.id) : null;
    var driver = gateway ? mockDriver(gateway.gateway_code) : null;
    var refunded = order.refunded_amount || 0;
    return Mock.ok({
        id: order.id, order_no: order.order_no, short_code: order.short_code, status: order.status, customer_name: order.customer_name,
        payment_method: order.payment_method, gateway_name: driver ? driver.name : null, gateway_supports_refund: Boolean(driver && driver.supports_refund),
        total: order.total, refunded_amount: refunded, refundable_amount: order.total - refunded,
        items: order.items.map(function (item) {
            return { id: item.id, product_name: item.product_name, variant_label: item.variant_label, child_name: item.child_name,
                unit_price: item.unit_price, qty: item.qty, collected_qty: item.collected_qty, cancelled_qty: item.cancelled_qty || 0,
                returned_qty: item.returned_qty || 0, returnable_qty: mockReturnableQty(item) };
        })
    });
});

// items = [{ item_id, qty }] — barang belum diambil: dibatalkan (tahanan stok dilepaskan);
// barang sudah diambil: dipulangkan (restock → stok masuk semula). Amaun ≤ baki boleh direfund
Mock.register('vendor/refunds/create', function (body) {
    var order = mockFindOrder(body.order_id, body.cooperative_id);
    if (!order) {
        return mockCoopError();
    }
    if (order.payment_status !== 'paid') {
        return Mock.fail(T('refund.order_not_paid'));
    }
    var refundable = order.total - (order.refunded_amount || 0);
    var errors = Mock.requireFields(body, ['amount', 'method', 'reason']);
    if (body.amount !== null && !(body.amount > 0 && body.amount <= refundable)) {
        errors.amount = T('refund.amount_range', { max: formatRM(refundable) });
    }
    var gateway = mockCoopGateway(order.cooperative_id);
    var driver = gateway ? mockDriver(gateway.gateway_code) : null;
    if (body.method === 'gateway' && !(order.payment_method === 'online' && driver && driver.supports_refund)) {
        errors.method = T('refund.gateway_unsupported');
    }
    if (body.method && ['cash', 'bank_transfer', 'gateway'].indexOf(body.method) === -1) {
        errors.method = T('error.invalid');
    }
    var lines = (body.items || []).filter(function (line) { return line.qty > 0; });
    var tooMany = lines.some(function (line) {
        var item = order.items.filter(function (row) { return row.id === Number(line.item_id); })[0];
        return !item || line.qty > mockReturnableQty(item);
    });
    if (tooMany) {
        errors.items = T('refund.qty_too_many');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(errors.items && Object.keys(errors).length === 1 ? errors.items : T('error.check_form'), errors);
    }

    var refundId = Mock.nextId(MockFinance.refunds);
    var refund = { id: refundId, vendor_id: order.vendor_id, cooperative_id: order.cooperative_id, refund_no: 'RF-' + (1000 + refundId),
        order_id: order.id, amount: body.amount, method: body.method, reason: String(body.reason).trim(), restock: Boolean(body.restock),
        items: [], user_name: mockSessionUser().full_name, created_at: Mock.now() };

    lines.forEach(function (line) {
        var item = order.items.filter(function (row) { return row.id === Number(line.item_id); })[0];
        var level = mockStockLevel(order.cooperative_id, item.product_id, item.variant_id);
        var uncollected = item.qty - item.collected_qty - (item.cancelled_qty || 0);
        var cancel = Math.min(line.qty, Math.max(0, uncollected));
        var returned = line.qty - cancel;
        if (cancel > 0) {
            item.cancelled_qty = (item.cancelled_qty || 0) + cancel;
            if (level) {
                level.qty_reserved = Math.max(0, level.qty_reserved - cancel);
                mockMoveStock(level, 'release', 0, 'order', order.id, refund.refund_no);
            }
        }
        if (returned > 0) {
            item.returned_qty = (item.returned_qty || 0) + returned;
            if (level && refund.restock) {
                mockMoveStock(level, 'refund', returned, 'order', order.id, refund.refund_no);
            }
        }
        refund.items.push({ order_item_id: item.id, qty: line.qty, cancelled: cancel });
    });

    order.refunded_amount = (order.refunded_amount || 0) + refund.amount;
    if (order.refunded_amount >= order.total) {
        mockLogStatus(order, 'refunded', refund.refund_no + ' · ' + refund.reason);
    } else if (['ready', 'partially_collected'].indexOf(order.status) !== -1 && order.items.every(function (item) {
        return item.qty - item.collected_qty - (item.cancelled_qty || 0) <= 0;
    })) {
        mockLogStatus(order, 'collected', refund.refund_no);
    }
    MockFinance.refunds.push(refund);
    mockActivity('refund_create', order.cooperative_id, 'refund', refund.id, refund.refund_no + ' · ' + formatRM(refund.amount));
    return Mock.ok(mockRefundSummary(refund));
});

// ---------- Promosi / kupon ----------

function mockPromotionRow(promo) {
    var copy = JSON.parse(JSON.stringify(promo));
    delete copy.vendor_id;
    copy.state = mockPromotionState(promo);
    return copy;
}

function mockFindPromotion(id, cooperativeId) {
    return mockCoopRows(MockFinance.promotions, cooperativeId).filter(function (row) { return row.id === Number(id); })[0] || null;
}

Mock.register('vendor/promotions/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockFinance.promotions, coop.id).map(mockPromotionRow).filter(function (row) {
        return !params.state || row.state === params.state;
    }).sort(function (a, b) { return a.starts_on < b.starts_on ? 1 : -1; });
    return Mock.ok(Mock.paginate(rows, params, ['name', 'code']));
});

Mock.register('vendor/promotions/save', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['name', 'type', 'value', 'starts_on', 'ends_on']);
    var code = body.code ? String(body.code).trim().toUpperCase() : null;
    if (code && !/^[A-Z0-9-]{3,20}$/.test(code)) {
        errors.code = T('promo.code_format');
    }
    if (code && mockCoopRows(MockFinance.promotions, coop.id).some(function (row) { return row.code === code && row.id !== Number(body.id); })) {
        errors.code = T('error.duplicate');
    }
    if (body.type === 'percent' && body.value !== null && !(body.value >= 1 && body.value <= 100)) {
        errors.value = T('error.range', { min: 1, max: 100 });
    }
    if (body.type === 'fixed' && body.value !== null && !(body.value > 0)) {
        errors.value = T('promo.value_positive');
    }
    if (body.starts_on && body.ends_on && body.ends_on < body.starts_on) {
        errors.ends_on = T('promo.end_before_start');
    }
    if (body.max_uses !== null && body.max_uses !== undefined && !(body.max_uses >= 1)) {
        errors.max_uses = T('promo.value_positive');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var promo = body.id ? mockFindPromotion(body.id, coop.id) : null;
    if (body.id && !promo) {
        return mockCoopError();
    }
    if (!promo) {
        promo = { id: Mock.nextId(MockFinance.promotions), vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id, used_count: 0 };
        MockFinance.promotions.push(promo);
    }
    promo.name = String(body.name).trim();
    promo.code = code;
    promo.type = body.type;
    promo.value = body.value;
    promo.min_spend = body.min_spend || 0;
    promo.starts_on = body.starts_on;
    promo.ends_on = body.ends_on;
    promo.max_uses = body.max_uses || null;
    promo.is_active = Boolean(body.is_active);
    mockActivity('promotion_save', coop.id, 'promotion', promo.id, promo.code || promo.name);
    return Mock.ok(mockPromotionRow(promo));
});

// Promosi yang pernah digunakan tidak boleh dipadam (rekod pesanan merujuknya) — nyahaktifkan sahaja
Mock.register('vendor/promotions/delete', function (body) {
    var promo = mockFindPromotion(body.id, body.cooperative_id);
    if (!promo) {
        return mockCoopError();
    }
    if (promo.used_count > 0) {
        return Mock.fail(T('promo.delete_used'));
    }
    MockFinance.promotions = MockFinance.promotions.filter(function (row) { return row.id !== promo.id; });
    mockActivity('promotion_delete', promo.cooperative_id, 'promotion', promo.id, promo.code || promo.name);
    return Mock.ok({ id: promo.id });
});

// Kakitangan vendor: laluan sebenar sejak B2 (api/v1/vendor/staff/*)

// ---------- Tetapan koperasi ----------

function mockSettingsData(coop) {
    var settings = MockFinance.settings[coop.id];
    var pickup = MockOrders.pickupSettings[coop.id];
    return Object.assign(JSON.parse(JSON.stringify(settings)), {
        default_language: coop.default_language, pickup_methods: pickup.methods.slice(), pickup_code_required_above: pickup.code_required_above
    });
}

Mock.register('vendor/settings/get', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    return Mock.ok(mockSettingsData(coop));
});

Mock.register('vendor/settings/save', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = {};
    var emails = (body.alert_emails || []).map(function (email) { return String(email).trim().toLowerCase(); });
    if (emails.length === 0) {
        errors.alert_emails = T('error.required');
    } else if (emails.length > 5) {
        errors.alert_emails = T('settings_v.alert_emails_max', { max: 5 });
    } else if (emails.some(function (email) { return !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); })) {
        errors.alert_emails = T('settings_v.alert_emails_invalid');
    }
    var methods = (body.pickup_methods || []).filter(function (code) { return ['A', 'B', 'C', 'D', 'E'].indexOf(code) !== -1; });
    if (!methods.some(function (code) { return code !== 'E'; })) {
        errors.pickup_methods = T('settings_v.pickup_required');
    }
    if (!(body.cash_order_expiry_days >= 1 && body.cash_order_expiry_days <= 14)) {
        errors.cash_order_expiry_days = T('error.range', { min: 1, max: 14 });
    }
    if (body.tax_rate === null || !(body.tax_rate >= 0 && body.tax_rate <= 10)) {
        errors.tax_rate = T('error.range', { min: 0, max: 10 });
    }
    if (!(body.low_stock_default >= 0)) {
        errors.low_stock_default = T('error.invalid');
    }
    if (['ms', 'en', 'zh', 'ta'].indexOf(body.default_language) === -1) {
        errors.default_language = T('error.invalid');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var settings = MockFinance.settings[coop.id];
    settings.alert_emails = emails.filter(function (email, index) { return emails.indexOf(email) === index; });
    ['receipt_header', 'receipt_footer', 'receipt_paper', 'tax_rate', 'cash_order_expiry_days', 'low_stock_default'].forEach(function (field) {
        settings[field] = body[field];
    });
    settings.receipt_show_logo = Boolean(body.receipt_show_logo);
    coop.default_language = body.default_language;
    MockOrders.pickupSettings[coop.id] = { methods: ['A', 'B', 'C', 'D', 'E'].filter(function (code) { return methods.indexOf(code) !== -1; }),
        code_required_above: body.pickup_code_required_above || 0 };
    mockActivity('settings_save', coop.id, 'cooperative', coop.id, '');
    return Mock.ok(mockSettingsData(coop));
});

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

// ---------- Laporan ----------

// Item jualan dalam julat tarikh: pesanan dibayar (refund ditolak berasingan)
function mockSalesOrders(coop, params) {
    return mockCoopRows(MockOrders.orders, coop.id).filter(function (order) {
        var day = (order.paid_at || order.created_at).slice(0, 10);
        return order.payment_status === 'paid' && (!params.date_from || day >= params.date_from) && (!params.date_to || day <= params.date_to);
    });
}

function mockItemCost(item) {
    var product = Mock.findById(MockVendor.products, item.product_id);
    return (product ? product.cost_price : 0) * item.qty;
}

function mockGroupRows(orders, keyFn, labelFn) {
    var groups = {};
    orders.forEach(function (order) {
        var key = keyFn(order);
        var row = groups[key] = groups[key] || { key: key, label: labelFn(order), orders: 0, gross: 0, discount: 0, refunds: 0, net: 0, cash: 0, online: 0 };
        var net = order.total - (order.refunded_amount || 0);
        row.orders += 1;
        row.gross += order.subtotal;
        row.discount += order.discount;
        row.refunds += order.refunded_amount || 0;
        row.net += net;
        row[order.payment_method] += net;
    });
    return Object.keys(groups).sort().map(function (key) { return groups[key]; });
}

function mockSum(rows, keys) {
    var totals = {};
    keys.forEach(function (key) {
        totals[key] = rows.reduce(function (sum, row) { return sum + (row[key] || 0); }, 0);
    });
    return totals;
}

function mockMargin(profit, sales) {
    return sales ? Math.round(profit / sales * 1000) / 10 : 0;
}

var MOCK_REPORTS = {
    daily: function (coop, params) {
        var rows = mockGroupRows(mockSalesOrders(coop, params), function (o) { return (o.paid_at || o.created_at).slice(0, 10); }, function (o) { return (o.paid_at || o.created_at).slice(0, 10); })
            .map(function (row) { return { date: row.key, orders: row.orders, gross: row.gross, discount: row.discount, refunds: row.refunds, net: row.net, cash: row.cash, online: row.online }; });
        return { columns: [['date', 'date'], ['orders', 'int'], ['gross', 'money'], ['discount', 'money'], ['refunds', 'money'], ['net', 'money'], ['cash', 'money'], ['online', 'money']],
            rows: rows, totals: mockSum(rows, ['orders', 'gross', 'discount', 'refunds', 'net', 'cash', 'online']),
            chart: rows.map(function (row) { return { label: row.date.slice(8, 10) + '/' + row.date.slice(5, 7), value: row.net }; }) };
    },
    monthly: function (coop, params) {
        var rows = mockGroupRows(mockSalesOrders(coop, params), function (o) { return (o.paid_at || o.created_at).slice(0, 7); }, function () { return ''; })
            .map(function (row) { return { month: row.key, orders: row.orders, gross: row.gross, discount: row.discount, refunds: row.refunds, net: row.net, cash: row.cash, online: row.online }; });
        return { columns: [['month', 'month'], ['orders', 'int'], ['gross', 'money'], ['discount', 'money'], ['refunds', 'money'], ['net', 'money'], ['cash', 'money'], ['online', 'money']],
            rows: rows, totals: mockSum(rows, ['orders', 'gross', 'discount', 'refunds', 'net', 'cash', 'online']),
            chart: rows.map(function (row) { return { label: row.month, value: row.net, format: 'month' }; }) };
    },
    product: function (coop, params) {
        var groups = {};
        mockSalesOrders(coop, params).forEach(function (order) {
            order.items.forEach(function (item) {
                var key = item.product_id + ':' + (item.variant_id || 0);
                var row = groups[key] = groups[key] || { product: item.product_name + (item.variant_label ? ' — ' + item.variant_label : ''), sku: item.sku, qty: 0, sales: 0, cost: 0, profit: 0 };
                row.qty += item.qty;
                row.sales += item.line_total;
                row.cost += mockItemCost(item);
            });
        });
        var rows = Object.keys(groups).map(function (key) {
            var row = groups[key];
            row.profit = row.sales - row.cost;
            row.margin = mockMargin(row.profit, row.sales);
            return row;
        }).sort(function (a, b) { return b.sales - a.sales; });
        var totals = mockSum(rows, ['qty', 'sales', 'cost', 'profit']);
        totals.margin = mockMargin(totals.profit, totals.sales);
        return { columns: [['product', 'text'], ['sku', 'text'], ['qty', 'int'], ['sales', 'money'], ['cost', 'money'], ['profit', 'money'], ['margin', 'percent']],
            rows: rows, totals: totals, chart: rows.slice(0, 8).map(function (row) { return { label: row.sku, value: row.sales }; }) };
    },
    grade: function (coop, params) {
        return mockChildReport(coop, params, false);
    },
    class: function (coop, params) {
        return mockChildReport(coop, params, true);
    },
    profit: function (coop, params) {
        var groups = {};
        mockSalesOrders(coop, params).forEach(function (order) {
            order.items.forEach(function (item) {
                var product = Mock.findById(MockVendor.products, item.product_id);
                var name = mockCategoryName(product.category_id);
                var row = groups[name] = groups[name] || { category: name, qty: 0, sales: 0, cost: 0, profit: 0 };
                row.qty += item.qty;
                row.sales += item.line_total;
                row.cost += mockItemCost(item);
            });
        });
        var rows = Object.keys(groups).map(function (key) {
            var row = groups[key];
            row.profit = row.sales - row.cost;
            row.margin = mockMargin(row.profit, row.sales);
            return row;
        }).sort(function (a, b) { return b.profit - a.profit; });
        var totals = mockSum(rows, ['qty', 'sales', 'cost', 'profit']);
        totals.margin = mockMargin(totals.profit, totals.sales);
        return { columns: [['category', 'text'], ['qty', 'int'], ['sales', 'money'], ['cost', 'money'], ['profit', 'money'], ['margin', 'percent']],
            rows: rows, totals: totals, chart: rows.map(function (row) { return { label: row.category, value: row.profit }; }) };
    },
    stock: function (coop) {
        var rows = mockCoopRows(MockVendor.stock, coop.id).map(function (level) {
            var info = mockInventoryRow(level);
            var product = Mock.findById(MockVendor.products, level.product_id);
            var variant = level.variant_id ? mockFindVariant(product, level.variant_id) : null;
            var price = product.price + (variant ? variant.price_delta : 0);
            return { product: info.product_name + (info.variant_label ? ' — ' + info.variant_label : ''), sku: info.sku, on_hand: info.qty_on_hand,
                reserved: info.qty_reserved, available: info.qty_available, cost_value: info.qty_on_hand * product.cost_price, retail_value: info.qty_on_hand * price };
        }).sort(function (a, b) { return b.retail_value - a.retail_value; });
        return { columns: [['product', 'text'], ['sku', 'text'], ['on_hand', 'int'], ['reserved', 'int'], ['available', 'int'], ['cost_value', 'money'], ['retail_value', 'money']],
            rows: rows, totals: mockSum(rows, ['on_hand', 'reserved', 'available', 'cost_value', 'retail_value']), chart: null, no_dates: true };
    },
    payment: function (coop, params) {
        var gateway = mockCoopGateway(coop.id);
        var groups = {};
        mockSalesOrders(coop, params).forEach(function (order) {
            var key = order.payment_method;
            var row = groups[key] = groups[key] || { method: key, channel: key === 'online' && gateway ? mockDriver(gateway.gateway_code).name : '', orders: 0, amount: 0, refunds: 0 };
            row.orders += 1;
            row.amount += order.total;
            row.refunds += order.refunded_amount || 0;
        });
        var rows = Object.keys(groups).map(function (key) { return groups[key]; });
        var totals = mockSum(rows, ['orders', 'amount', 'refunds']);
        rows.forEach(function (row) { row.share = mockMargin(row.amount, totals.amount); });
        totals.share = rows.length ? 100 : 0;
        return { columns: [['method', 'method'], ['channel', 'text'], ['orders', 'int'], ['amount', 'money'], ['refunds', 'money'], ['share', 'percent']],
            rows: rows, totals: totals, chart: rows.map(function (row) { return { label: T('payment.method.' + row.method), value: row.amount }; }) };
    }
};

// Jualan ikut darjah / kelas (dari salinan nama anak & kelas dalam order_items)
function mockChildReport(coop, params, byClass) {
    var grades = mockGradesFor(coop.school_type);
    var groups = {};
    mockSalesOrders(coop, params).forEach(function (order) {
        order.items.forEach(function (item) {
            if (!item.grade_level_code) {
                return;
            }
            var key = item.grade_level_code + (byClass ? '|' + item.class_name : '');
            var row = groups[key] = groups[key] || { grade: item.grade_level_code, class_name: item.class_name, children: [], orders: [], qty: 0, sales: 0 };
            if (row.children.indexOf(item.child_id) === -1) {
                row.children.push(item.child_id);
            }
            if (row.orders.indexOf(order.id) === -1) {
                row.orders.push(order.id);
            }
            row.qty += item.qty;
            row.sales += item.line_total;
        });
    });
    var rows = Object.keys(groups).map(function (key) {
        var row = groups[key];
        var result = { grade: row.grade, students: row.children.length, orders: row.orders.length, qty: row.qty, sales: row.sales,
            average: row.children.length ? Math.round(row.sales / row.children.length) : 0 };
        if (byClass) {
            result.class_name = row.class_name;
        }
        return result;
    }).sort(function (a, b) {
        return grades.indexOf(a.grade) - grades.indexOf(b.grade) || String(a.class_name || '').localeCompare(String(b.class_name || ''));
    });
    var columns = [['grade', 'grade']].concat(byClass ? [['class_name', 'text']] : []).concat([['students', 'int'], ['orders', 'int'], ['qty', 'int'], ['sales', 'money'], ['average', 'money']]);
    var totals = mockSum(rows, ['students', 'orders', 'qty', 'sales']);
    totals.average = totals.students ? Math.round(totals.sales / totals.students) : 0;
    return { columns: columns, rows: rows, totals: totals,
        chart: byClass ? null : rows.map(function (row) { return { label: row.grade, value: row.sales, format: 'grade' }; }) };
}

Mock.register('vendor/reports/run', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var builder = MOCK_REPORTS[params.type];
    if (!builder) {
        return Mock.fail(T('error.invalid'));
    }
    if (params.date_from && params.date_to && params.date_to < params.date_from) {
        return Mock.fail(T('promo.end_before_start'), { date_to: T('promo.end_before_start') });
    }
    var report = builder(coop, params);
    return Mock.ok({
        type: params.type, school_name: coop.school_name, date_from: report.no_dates ? null : params.date_from || null,
        date_to: report.no_dates ? null : params.date_to || null, generated_at: Mock.now(),
        columns: report.columns.map(function (column) { return { key: column[0], label: 'report.col.' + column[0], format: column[1] }; }),
        rows: report.rows, totals: report.totals, chart: report.chart
    });
});

// Kekalkan perubahan antara halaman — mesti selepas data contoh dijana
Mock.persist('vendor-finance', MockFinance, ['gateways', 'refunds', 'promotions', 'staff', 'settings', 'subscriptions', 'moduleSubs',
    'templateSubs', 'invoices', 'invoiceSeq', 'activity']);
