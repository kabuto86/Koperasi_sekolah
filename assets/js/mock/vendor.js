// Data mock panel vendor (Peringkat 1). Bentuk objek = kontrak API (PELAN 18).
// Pengguna demo log masuk sebagai VENDOR01. Data VENDOR02 wujud untuk menguji kebocoran:
// setiap laluan menapis vendor_id dari "sesi" dan mengesahkan cooperative_id milik vendor itu.

var MockVendor = {
    // vendor_id datang dari "sesi" — TIDAK PERNAH dari parameter
    sessionVendorId: 1,
    sessionUserName: 'Encik Ahmad',

    vendors: [
        { id: 1, code: 'VENDOR01', company_name: 'Niaga Sekolah Sdn Bhd' },
        { id: 2, code: 'VENDOR02', company_name: 'Koperasi Bestari Enterprise' }
    ],

    // Pelan vendor semasa (untuk amaran harga koperasi tambahan & had)
    plans: {
        1: { plan_name: 'Pro', billing_cycle: 'yearly', max_cooperatives: 5, max_products: 2000, current_period_end: '2027-01-04',
             tiers: [{ from_coop: 2, to_coop: 3, price_monthly: 7900, price_yearly: 79000 }, { from_coop: 4, to_coop: null, price_monthly: 5900, price_yearly: 59000 }] },
        2: { plan_name: 'Asas', billing_cycle: 'monthly', max_cooperatives: 2, max_products: 300, current_period_end: '2026-11-10',
             tiers: [{ from_coop: 2, to_coop: null, price_monthly: 4900, price_yearly: 49000 }] }
    },

    templates: [
        { id: 1, code: 'template1', name: 'Template 1 — Asas', is_free: true, price_monthly: 0, price_yearly: 0, subscribed: true },
        { id: 2, code: 'template2', name: 'Template 2 — Ceria', is_free: false, price_monthly: 1000, price_yearly: 10000, subscribed: true }
    ],

    // Slug koperasi vendor lain (unik seluruh platform) — tidak didedahkan milik siapa
    otherSlugs: ['sk-seri-pagi', 'sjkt-ladang-bukit', 'smka-al-amin', 'sk-presint-9', 'smk-presint-9', 'sk-bandar-baru-bangi'],

    cooperatives: [
        { id: 1, vendor_id: 1, slug: 'sk-taman-melati', school_name: 'SK Taman Melati', school_code: 'BBA1234', school_type: 'SK',
          state: 'Selangor', district: 'Gombak', address: 'Jalan Melati 5, Taman Melati, 53100 Kuala Lumpur', phone: '03-41081234',
          logo_url: null, template_id: 1, default_language: 'ms', accept_cash: true, accept_online: true, status: 'active',
          opening_hours: { mon: { open: true, from: '07:30', to: '13:00' }, tue: { open: true, from: '07:30', to: '13:00' }, wed: { open: true, from: '07:30', to: '13:00' },
                           thu: { open: true, from: '07:30', to: '13:00' }, fri: { open: true, from: '07:30', to: '12:00' }, sat: { open: false, from: '', to: '' }, sun: { open: false, from: '', to: '' } },
          created_at: '2026-01-06T10:00:00+08:00' },
        { id: 2, vendor_id: 1, slug: 'sjkc-chung-hwa', school_name: 'SJKC Chung Hwa', school_code: 'BBC5678', school_type: 'SJKC',
          state: 'Selangor', district: 'Petaling', address: 'Jalan SS2/75, 47300 Petaling Jaya', phone: '03-78761234',
          logo_url: null, template_id: 2, default_language: 'zh', accept_cash: false, accept_online: true, status: 'active',
          opening_hours: { mon: { open: true, from: '07:00', to: '12:30' }, tue: { open: true, from: '07:00', to: '12:30' }, wed: { open: true, from: '07:00', to: '12:30' },
                           thu: { open: true, from: '07:00', to: '12:30' }, fri: { open: true, from: '07:00', to: '12:30' }, sat: { open: true, from: '08:00', to: '11:00' }, sun: { open: false, from: '', to: '' } },
          created_at: '2026-02-14T10:00:00+08:00' },
        { id: 3, vendor_id: 2, slug: 'smk-bukit-indah', school_name: 'SMK Bukit Indah', school_code: 'JEA9012', school_type: 'SMK',
          state: 'Johor', district: 'Johor Bahru', address: 'Jalan Indah 15, 81200 Johor Bahru', phone: '07-2361234',
          logo_url: null, template_id: 1, default_language: 'ms', accept_cash: true, accept_online: true, status: 'active',
          opening_hours: { mon: { open: true, from: '07:30', to: '14:00' }, tue: { open: true, from: '07:30', to: '14:00' }, wed: { open: true, from: '07:30', to: '14:00' },
                           thu: { open: true, from: '07:30', to: '14:00' }, fri: { open: true, from: '07:30', to: '12:00' }, sat: { open: false, from: '', to: '' }, sun: { open: false, from: '', to: '' } },
          created_at: '2026-02-12T10:00:00+08:00' }
    ],

    activeModules: { 1: ['news', 'school_opening', 'calendar', 'whatsapp_sms'], 2: [], 3: ['clearance'] },

    classes: [],
    categories: [
        { id: 1, cooperative_id: 1, name: 'Buku latihan', sort_order: 1, is_active: true },
        { id: 2, cooperative_id: 1, name: 'Alat tulis', sort_order: 2, is_active: true },
        { id: 3, cooperative_id: 1, name: 'Pakaian seragam', sort_order: 3, is_active: true },
        { id: 4, cooperative_id: 1, name: 'Beg & kasut', sort_order: 4, is_active: true },
        { id: 5, cooperative_id: 1, name: 'Lain-lain', sort_order: 5, is_active: false },
        { id: 6, cooperative_id: 2, name: '文具 Alat tulis', sort_order: 1, is_active: true },
        { id: 7, cooperative_id: 2, name: '练习簿 Buku latihan', sort_order: 2, is_active: true },
        { id: 8, cooperative_id: 2, name: '校服 Pakaian', sort_order: 3, is_active: true },
        { id: 9, cooperative_id: 3, name: 'Pakaian sekolah', sort_order: 1, is_active: true },
        { id: 10, cooperative_id: 3, name: 'Alat tulis', sort_order: 2, is_active: true }
    ],
    products: [],
    stock: [],
    movements: [],

    suppliers: [
        { id: 1, vendor_id: 1, name: 'Pustaka Sinar Sdn Bhd', contact_name: 'Encik Halim', phone: '03-61234567', email: 'order@pustakasinar.my', address: 'Jalan Industri 3, 47000 Sungai Buloh', is_active: true },
        { id: 2, vendor_id: 1, name: 'Uniform Jaya Enterprise', contact_name: 'Puan Rohani', phone: '012-8889990', email: 'jualan@uniformjaya.my', address: 'Jalan Kenari 8, 47100 Puchong', is_active: true },
        { id: 3, vendor_id: 1, name: 'Faber Stationery Distributor', contact_name: 'Mr. Lee', phone: '03-80701122', email: 'lee@faberdist.my', address: 'Bandar Puteri, 47100 Puchong', is_active: true },
        { id: 4, vendor_id: 1, name: 'Kedai Kasut Murah', contact_name: 'Raju', phone: '016-3334455', email: '', address: 'Klang', is_active: false },
        { id: 5, vendor_id: 2, name: 'Johor Uniform Supply', contact_name: 'Azman', phone: '07-3332211', email: 'azman@jus.my', address: 'Johor Bahru', is_active: true }
    ],

    purchaseOrders: [],
    stockTakes: [],

    // Jualan & pesanan untuk papan pemuka (diisi sepenuhnya dalam U3b/U4)
    dashboard: {
        1: { sales_today: 184250, sales_today_count: 37, orders_to_prepare: 12, orders_ready: 8,
             sales_7days: [98500, 120300, 87600, 143000, 165400, 59000, 184250] },
        2: { sales_today: 45600, sales_today_count: 9, orders_to_prepare: 3, orders_ready: 1,
             sales_7days: [22000, 31500, 18000, 40500, 37800, 0, 45600] },
        3: { sales_today: 76000, sales_today_count: 15, orders_to_prepare: 5, orders_ready: 4,
             sales_7days: [50000, 61000, 43000, 70000, 66000, 0, 76000] }
    },

    announcements: [
        { id: 1, title: 'Penyelenggaraan sistem 1 November', body: 'Sistem akan ditutup dari 1:00 hingga 3:00 pagi untuk penyelenggaraan.', published_at: '2026-10-22T09:00:00+08:00' },
        { id: 2, title: 'Modul Pembukaan sekolah kini tersedia', body: 'Langgan modul ini untuk musim pre-order kit 2027.', published_at: '2026-10-10T10:00:00+08:00' }
    ]
};

// ---------- Data contoh yang dijana ----------

// Kelas sekolah: SK/SJKC/SJKT → PRA + D1–D6; SMK → T1–T5
(function seedClasses() {
    var plan = [
        [1, ['PRA'], ['Tadika Ceria']],
        [1, ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'], ['Bestari', 'Cemerlang', 'Dinamik']],
        [2, ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'], ['A', 'B']],
        [3, ['T1', 'T2', 'T3', 'T4', 'T5'], ['Amanah', 'Bestari']]
    ];
    var id = 1;
    plan.forEach(function (row) {
        row[1].forEach(function (grade) {
            row[2].forEach(function (name, index) {
                MockVendor.classes.push({ id: id++, vendor_id: row[0] === 3 ? 2 : 1, cooperative_id: row[0], grade_level_code: grade, class_name: name, sort_order: index + 1 });
            });
        });
    });
})();

// Produk: [koperasi, kategori, nama, sku, harga, kos, unit, darjah, stok, min, varian?, terjemahan?]
(function seedProducts() {
    var rows = [
        [1, 1, 'Buku latihan 80 muka (garis)', 'BL-080G', 180, 110, 'buah', ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'], 240, 50],
        [1, 1, 'Buku latihan 80 muka (petak)', 'BL-080P', 180, 110, 'buah', ['D3', 'D4', 'D5', 'D6'], 35, 40],
        [1, 1, 'Buku tulisan jawi', 'BL-JAWI', 250, 150, 'buah', ['D1', 'D2', 'D3'], 120, 30],
        [1, 1, 'Buku latihan Matematik Tahun 4', 'BL-MT4', 890, 620, 'buah', ['D4'], 64, 20],
        [1, 1, 'Buku latihan Sains Tahun 4', 'BL-SN4', 890, 620, 'buah', ['D4'], 8, 20],
        [1, 2, 'Pensel 2B (12 batang)', 'AT-P2B12', 650, 380, 'kotak', [], 90, 20],
        [1, 2, 'Pemadam', 'AT-PMD', 80, 35, 'buah', [], 300, 50],
        [1, 2, 'Pembaris 30cm', 'AT-PMB30', 120, 60, 'buah', [], 4, 30],
        [1, 2, 'Pensel warna 24 warna', 'AT-PW24', 1290, 850, 'kotak', ['PRA', 'D1', 'D2', 'D3'], 45, 15],
        [1, 2, 'Kotak pensel', 'AT-KTP', 990, 600, 'buah', [], 0, 10],
        [1, 3, 'Baju sekolah putih', 'PS-BJP', 2290, 1500, 'helai', [], null, 10, [['S', 'PS-BJP-S', 0, 25], ['M', 'PS-BJP-M', 0, 40], ['L', 'PS-BJP-L', 200, 6], ['XL', 'PS-BJP-XL', 400, 2]],
          { en: { name: 'White school shirt', description: '' } }],
        [1, 3, 'Seluar sekolah biru tua', 'PS-SLB', 2890, 1900, 'helai', [], null, 10, [['24', 'PS-SLB-24', 0, 18], ['26', 'PS-SLB-26', 0, 22], ['28', 'PS-SLB-28', 0, 9]]],
        [1, 3, 'Kain sekolah biru tua', 'PS-KNB', 2690, 1750, 'helai', [], null, 10, [['S', 'PS-KNB-S', 0, 15], ['M', 'PS-KNB-M', 0, 20], ['L', 'PS-KNB-L', 0, 12]]],
        [1, 3, 'Tali leher sekolah', 'PS-TL', 850, 400, 'helai', ['D4', 'D5', 'D6'], 60, 20],
        [1, 3, 'Lencana sekolah', 'PS-LNC', 350, 150, 'buah', [], 150, 30],
        [1, 4, 'Kasut sekolah putih', 'BK-KSP', 2490, 1600, 'pasang', [], null, 5, [['Saiz 3', 'BK-KSP-3', 0, 8], ['Saiz 4', 'BK-KSP-4', 0, 12], ['Saiz 5', 'BK-KSP-5', 0, 3]]],
        [1, 4, 'Beg sekolah beroda', 'BK-BGR', 7900, 5200, 'buah', [], 14, 5],
        [1, 5, 'Botol air 500ml', 'LL-BTL', 1500, 800, 'buah', [], 25, 10],
        [2, 6, '铅笔 Pensel HB (12)', 'SC-PHB', 600, 350, 'kotak', [], 80, 20, null,
          { zh: { name: 'HB 铅笔（12 支）', description: '' } }],
        [2, 6, '橡皮擦 Pemadam', 'SC-PMD', 100, 40, 'buah', [], 200, 40, null, { zh: { name: '橡皮擦', description: '' } }],
        [2, 7, '华文练习簿 Buku latihan Cina', 'SC-BLC', 220, 130, 'buah', ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'], 15, 50, null,
          { zh: { name: '华文练习簿', description: '' } }],
        [2, 8, '校服衬衫 Baju putih', 'SC-BJP', 2390, 1550, 'helai', [], null, 10, [['S', 'SC-BJP-S', 0, 10], ['M', 'SC-BJP-M', 0, 14]],
          { zh: { name: '白色校服衬衫', description: '' } }],
        [3, 9, 'Baju kurung sekolah', 'BI-BKR', 4590, 3000, 'pasang', ['T1', 'T2', 'T3', 'T4', 'T5'], null, 5, [['M', 'BI-BKR-M', 0, 10], ['L', 'BI-BKR-L', 0, 6]]],
        [3, 10, 'Kalkulator saintifik', 'BI-KLK', 5500, 3800, 'buah', ['T1', 'T2', 'T3', 'T4', 'T5'], 20, 5]
    ];

    var variantId = 1;
    rows.forEach(function (row, index) {
        var id = index + 1;
        var coopId = row[0];
        var vendorId = coopId === 3 ? 2 : 1;
        var variants = (row[10] || []).map(function (variant) {
            var item = { id: variantId++, label: variant[0], sku: variant[1], barcode: '955' + String(100000000 + id * 100 + variant[2] / 100 + variantId), price_delta: variant[2], is_active: true };
            MockVendor.stock.push({ vendor_id: vendorId, cooperative_id: coopId, product_id: id, variant_id: item.id, qty_on_hand: variant[3], qty_reserved: Math.min(2, variant[3]), min_stock: row[9] });
            return item;
        });

        MockVendor.products.push({
            id: id, vendor_id: vendorId, cooperative_id: coopId, category_id: row[1], name: row[2], description: '',
            sku: row[3], barcode: '955' + String(200000000 + id), price: row[4], cost_price: row[5], unit: row[6],
            image_url: null, max_per_order: null, has_variants: variants.length > 0, is_active: id !== 18, sort_order: index + 1,
            grade_levels: row[7], variants: variants, translations: row[11] || {}
        });

        if (variants.length === 0) {
            MockVendor.stock.push({ vendor_id: vendorId, cooperative_id: coopId, product_id: id, variant_id: 0, qty_on_hand: row[8], qty_reserved: Math.min(3, row[8]), min_stock: row[9] });
        }
    });
})();

// Sejarah pergerakan stok contoh (terbaharu dahulu)
(function seedMovements() {
    var types = ['restock', 'sale', 'reserve', 'collect', 'pos_sale', 'adjust', 'po_receive', 'release', 'refund', 'sale'];
    var changes = { restock: 50, sale: -2, reserve: 0, collect: -3, pos_sale: -1, adjust: -1, po_receive: 40, release: 0, refund: 1 };
    var id = 1;
    MockVendor.stock.forEach(function (level, levelIndex) {
        var balance = level.qty_on_hand;
        for (var n = 0; n < 4; n++) {
            var type = types[(levelIndex + n) % types.length];
            var day = 24 - n * 3 - (levelIndex % 3);
            MockVendor.movements.push({
                id: id++, vendor_id: level.vendor_id, cooperative_id: level.cooperative_id, product_id: level.product_id, variant_id: level.variant_id,
                type: type, qty_change: changes[type], balance_after: balance, ref_type: type === 'po_receive' ? 'purchase_order' : (type === 'restock' || type === 'adjust' ? null : 'order'),
                ref_id: type === 'po_receive' ? 1 : (type === 'restock' || type === 'adjust' ? null : 5000 + id),
                user_name: n % 2 ? 'Siti (juruwang)' : 'Encik Ahmad', note: type === 'adjust' ? 'Rosak' : '',
                created_at: '2026-10-' + (day < 10 ? '0' + day : day) + 'T' + String(9 + n).padStart(2, '0') + ':20:00+08:00'
            });
            balance -= changes[type];
        }
    });
    MockVendor.movements.sort(function (a, b) {
        return a.created_at < b.created_at ? 1 : -1;
    });
})();

(function seedPurchaseOrders() {
    MockVendor.purchaseOrders = [
        { id: 1, vendor_id: 1, cooperative_id: 1, supplier_id: 1, po_no: 'PO-2026-0007', status: 'received', ordered_at: '2026-10-01T10:00:00+08:00', received_at: '2026-10-08T11:00:00+08:00', note: '',
          items: [{ id: 1, product_id: 1, variant_id: 0, qty_ordered: 200, qty_received: 200, unit_cost: 110 }, { id: 2, product_id: 3, variant_id: 0, qty_ordered: 100, qty_received: 100, unit_cost: 150 }] },
        { id: 2, vendor_id: 1, cooperative_id: 1, supplier_id: 2, po_no: 'PO-2026-0009', status: 'partial', ordered_at: '2026-10-15T10:00:00+08:00', received_at: null, note: 'Saiz XL lewat',
          items: [{ id: 3, product_id: 11, variant_id: 3, qty_ordered: 20, qty_received: 10, unit_cost: 1500 }, { id: 4, product_id: 11, variant_id: 4, qty_ordered: 10, qty_received: 0, unit_cost: 1500 }] },
        { id: 3, vendor_id: 1, cooperative_id: 1, supplier_id: 3, po_no: 'PO-2026-0010', status: 'ordered', ordered_at: '2026-10-22T09:30:00+08:00', received_at: null, note: '',
          items: [{ id: 5, product_id: 8, variant_id: 0, qty_ordered: 60, qty_received: 0, unit_cost: 60 }, { id: 6, product_id: 10, variant_id: 0, qty_ordered: 30, qty_received: 0, unit_cost: 600 }] },
        { id: 4, vendor_id: 1, cooperative_id: 1, supplier_id: 1, po_no: 'PO-2026-0011', status: 'draft', ordered_at: null, received_at: null, note: 'Untuk sesi 2027',
          items: [{ id: 7, product_id: 5, variant_id: 0, qty_ordered: 40, qty_received: 0, unit_cost: 620 }] },
        { id: 5, vendor_id: 1, cooperative_id: 2, supplier_id: 3, po_no: 'PO-2026-0008', status: 'ordered', ordered_at: '2026-10-12T09:30:00+08:00', received_at: null, note: '',
          items: [{ id: 8, product_id: 21, variant_id: 0, qty_ordered: 100, qty_received: 0, unit_cost: 130 }] },
        { id: 6, vendor_id: 2, cooperative_id: 3, supplier_id: 5, po_no: 'PO-2026-0003', status: 'ordered', ordered_at: '2026-10-10T09:30:00+08:00', received_at: null, note: '',
          items: [{ id: 9, product_id: 23, variant_id: 0, qty_ordered: 10, qty_received: 0, unit_cost: 3000 }] }
    ];

    MockVendor.stockTakes = [
        { id: 1, vendor_id: 1, cooperative_id: 1, status: 'completed', started_by: 'Encik Ahmad', started_at: '2026-07-01T08:00:00+08:00', completed_at: '2026-07-01T12:30:00+08:00', items: [] }
    ];
})();

// ---------- Pembantu: skop vendor & koperasi ----------

function mockOwnRows(rows) {
    return rows.filter(function (row) {
        return row.vendor_id === MockVendor.sessionVendorId;
    });
}

function mockOwnCooperative(id) {
    return mockOwnRows(MockVendor.cooperatives).filter(function (coop) {
        return coop.id === Number(id);
    })[0] || null;
}

// Baris milik vendor sesi DAN koperasi yang diminta (selepas koperasi disahkan)
function mockCoopRows(rows, cooperativeId) {
    return mockOwnRows(rows).filter(function (row) {
        return row.cooperative_id === Number(cooperativeId);
    });
}

// Sama seperti ValidateCooperativeJob: koperasi bukan milik vendor → ralat
function mockRequireCoop(params) {
    return mockOwnCooperative(params.cooperative_id);
}

function mockCoopError() {
    return Mock.fail(T('error.not_found'));
}

// Kekalkan nama lama untuk kod sedia ada (U1)
MockVendor.ownCooperatives = function () {
    return mockOwnRows(MockVendor.cooperatives);
};
MockVendor.findOwnCooperative = mockOwnCooperative;

function mockFindProduct(id, cooperativeId) {
    return mockCoopRows(MockVendor.products, cooperativeId).filter(function (product) {
        return product.id === Number(id);
    })[0] || null;
}

function mockFindVariant(product, variantId) {
    return product.variants.filter(function (variant) {
        return variant.id === Number(variantId);
    })[0] || null;
}

function mockStockLevel(cooperativeId, productId, variantId) {
    return mockCoopRows(MockVendor.stock, cooperativeId).filter(function (level) {
        return level.product_id === Number(productId) && level.variant_id === Number(variantId || 0);
    })[0] || null;
}

function mockCategoryName(categoryId) {
    var category = Mock.findById(MockVendor.categories, categoryId);
    return category ? category.name : '';
}

// Rekod pergerakan stok + kemas kini baki (seperti StockMovementJob di B4)
function mockMoveStock(level, type, qtyChange, refType, refId, note) {
    level.qty_on_hand += qtyChange;
    MockVendor.movements.unshift({
        id: Mock.nextId(MockVendor.movements), vendor_id: level.vendor_id, cooperative_id: level.cooperative_id,
        product_id: level.product_id, variant_id: level.variant_id, type: type, qty_change: qtyChange,
        balance_after: level.qty_on_hand, ref_type: refType || null, ref_id: refId || null,
        user_name: MockVendor.sessionUserName, note: note || '', created_at: Mock.now()
    });
}

function mockInventoryRow(level) {
    var product = Mock.findById(MockVendor.products, level.product_id);
    var variant = level.variant_id ? mockFindVariant(product, level.variant_id) : null;
    var available = level.qty_on_hand - level.qty_reserved;
    return {
        product_id: product.id, variant_id: level.variant_id, product_name: product.name, variant_label: variant ? variant.label : null,
        sku: variant ? variant.sku : product.sku, category_id: product.category_id, category_name: mockCategoryName(product.category_id),
        unit: product.unit, qty_on_hand: level.qty_on_hand, qty_reserved: level.qty_reserved, qty_available: available,
        min_stock: level.min_stock, is_low: available <= level.min_stock, is_active: product.is_active
    };
}

function mockProductSummary(product) {
    var levels = mockCoopRows(MockVendor.stock, product.cooperative_id).filter(function (level) {
        return level.product_id === product.id;
    });
    var available = levels.reduce(function (sum, level) { return sum + level.qty_on_hand - level.qty_reserved; }, 0);
    var isLow = levels.some(function (level) { return level.qty_on_hand - level.qty_reserved <= level.min_stock; });
    return {
        id: product.id, name: product.name, sku: product.sku, barcode: product.barcode, category_id: product.category_id,
        category_name: mockCategoryName(product.category_id), price: product.price, unit: product.unit, image_url: product.image_url,
        has_variants: product.has_variants, variant_count: product.variants.length, grade_levels: product.grade_levels,
        stock_available: available, is_low: isLow, is_active: product.is_active
    };
}

function mockSupplierName(id) {
    var supplier = Mock.findById(MockVendor.suppliers, id);
    return supplier ? supplier.name : '';
}

function mockPurchaseOrderDetail(po) {
    var copy = JSON.parse(JSON.stringify(po));
    copy.supplier_name = mockSupplierName(po.supplier_id);
    copy.items = po.items.map(function (item) {
        var product = Mock.findById(MockVendor.products, item.product_id);
        var variant = item.variant_id ? mockFindVariant(product, item.variant_id) : null;
        var line = JSON.parse(JSON.stringify(item));
        line.product_name = product.name;
        line.variant_label = variant ? variant.label : null;
        line.sku = variant ? variant.sku : product.sku;
        line.amount = item.qty_ordered * item.unit_cost;
        return line;
    });
    copy.total = copy.items.reduce(function (sum, item) { return sum + item.amount; }, 0);
    delete copy.vendor_id;
    return copy;
}

function mockStockTakeSummary(take) {
    var counted = take.items.filter(function (item) { return item.qty_counted !== null; });
    return {
        id: take.id, cooperative_id: take.cooperative_id, status: take.status, started_by: take.started_by,
        started_at: take.started_at, completed_at: take.completed_at, item_count: take.items.length || 24,
        counted_count: take.status === 'completed' && take.items.length === 0 ? 24 : counted.length,
        variance_total: counted.reduce(function (sum, item) { return sum + (item.qty_counted - item.qty_system); }, 0)
    };
}

// Darjah ikut jenis sekolah
function mockGradesFor(schoolType) {
    var primary = ['PRA', 'D1', 'D2', 'D3', 'D4', 'D5', 'D6'];
    var secondary = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'];
    if (['SK', 'SJKC', 'SJKT'].indexOf(schoolType) !== -1) {
        return primary;
    }
    if (['SMK', 'SMKA', 'SBP'].indexOf(schoolType) !== -1) {
        return secondary;
    }
    return primary.concat(secondary);
}

// Log masuk, pilih koperasi & senarai koperasi: laluan sebenar sejak B2 (api/v1/vendor/auth|session|cooperatives/list)

Mock.register('vendor/modules/active', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    return Mock.ok({ cooperative_id: coop.id, modules: MockVendor.activeModules[coop.id] || [] });
});

// ---------- Papan pemuka ----------

Mock.register('vendor/dashboard/summary', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var stats = MockVendor.dashboard[coop.id];
    var lowRows = mockCoopRows(MockVendor.stock, coop.id).map(mockInventoryRow).filter(function (row) {
        return row.is_low && row.is_active;
    }).sort(function (a, b) {
        return a.qty_available - b.qty_available;
    });
    var days = stats.sales_7days.map(function (amount, index) {
        var day = 19 + index;
        return { date: '2026-10-' + day, amount: amount };
    });

    return Mock.ok({
        sales_today: stats.sales_today, sales_today_count: stats.sales_today_count,
        orders_to_prepare: stats.orders_to_prepare, orders_ready: stats.orders_ready,
        low_stock_count: lowRows.length, low_stock: lowRows.slice(0, 6), sales_7days: days,
        announcements: MockVendor.announcements
    });
});

// ---------- Koperasi saya ----------

function mockCooperativeSummary(coop) {
    var template = Mock.findById(MockVendor.templates, coop.template_id);
    return {
        id: coop.id, slug: coop.slug, school_name: coop.school_name, school_code: coop.school_code, school_type: coop.school_type,
        state: coop.state, district: coop.district, status: coop.status, logo_url: coop.logo_url,
        template_name: template ? template.name : '', accept_cash: coop.accept_cash, accept_online: coop.accept_online,
        class_count: mockCoopRows(MockVendor.classes, coop.id).length,
        product_count: mockCoopRows(MockVendor.products, coop.id).length,
        modules: MockVendor.activeModules[coop.id] || []
    };
}

Mock.register('vendor/cooperatives/get', function (params) {
    var coop = mockOwnCooperative(params.id);
    if (!coop) {
        return mockCoopError();
    }
    var data = JSON.parse(JSON.stringify(coop));
    delete data.vendor_id;
    return Mock.ok(data);
});

// Harga koperasi ke-N ikut peringkat pelan vendor + had pelan
Mock.register('vendor/cooperatives/pricing-preview', function () {
    var plan = MockVendor.plans[MockVendor.sessionVendorId];
    var number = mockOwnRows(MockVendor.cooperatives).filter(function (coop) { return coop.status !== 'closed'; }).length + 1;
    var tier = plan.tiers.filter(function (row) {
        return number >= row.from_coop && (row.to_coop === null || number <= row.to_coop);
    })[0];
    return Mock.ok({
        cooperative_number: number, plan_name: plan.plan_name, billing_cycle: plan.billing_cycle,
        price_monthly: tier ? tier.price_monthly : 0, price_yearly: tier ? tier.price_yearly : 0,
        current_period_end: plan.current_period_end, max_cooperatives: plan.max_cooperatives,
        limit_reached: plan.max_cooperatives !== null && number > plan.max_cooperatives
    });
});

// Slug unik seluruh platform. Tidak mendedahkan milik vendor mana
Mock.register('vendor/cooperatives/check-slug', function (params) {
    var slug = String(params.slug || '');
    var valid = /^[a-z0-9]+(-[a-z0-9]+)*$/.test(slug) && slug.length >= 3 && slug.length <= 60;
    var taken = MockVendor.otherSlugs.indexOf(slug) !== -1 || MockVendor.cooperatives.some(function (coop) {
        return coop.slug === slug && coop.id !== Number(params.id);
    });
    return Mock.ok({ slug: slug, valid: valid, available: valid && !taken });
});

Mock.register('vendor/templates/options', function () {
    return Mock.ok(MockVendor.templates);
});

Mock.register('vendor/cooperatives/save', function (body) {
    var errors = Mock.requireFields(body, ['school_name', 'school_code', 'school_type', 'state', 'district', 'address', 'slug', 'template_id']);
    var slugCheck = Mock.handle('GET', 'vendor/cooperatives/check-slug', { slug: body.slug, id: body.id }).data;
    if (body.slug && !slugCheck.valid) {
        errors.slug = T('coop.slug_format');
    } else if (body.slug && !slugCheck.available) {
        errors.slug = T('coop.slug_taken');
    }
    if (!body.accept_cash && !body.accept_online) {
        errors.accept_online = T('coop.payment_required');
    }
    var template = Mock.findById(MockVendor.templates, body.template_id);
    if (body.template_id && (!template || !template.subscribed)) {
        errors.template_id = T('coop.template_not_subscribed');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var coop = body.id ? mockOwnCooperative(body.id) : null;
    if (body.id && !coop) {
        return mockCoopError();
    }
    if (!coop) {
        var preview = Mock.handle('GET', 'vendor/cooperatives/pricing-preview', {}).data;
        if (preview.limit_reached) {
            return Mock.fail(T('coop.limit_reached', { max: preview.max_cooperatives }));
        }
        coop = { id: Mock.nextId(MockVendor.cooperatives), vendor_id: MockVendor.sessionVendorId, status: 'active', created_at: Mock.now() };
        MockVendor.cooperatives.push(coop);
        MockVendor.dashboard[coop.id] = { sales_today: 0, sales_today_count: 0, orders_to_prepare: 0, orders_ready: 0, sales_7days: [0, 0, 0, 0, 0, 0, 0] };
    }
    ['school_name', 'school_code', 'school_type', 'state', 'district', 'address', 'phone', 'slug', 'template_id', 'default_language',
        'accept_cash', 'accept_online', 'opening_hours', 'logo_url'].forEach(function (field) {
        if (body[field] !== undefined) {
            coop[field] = body[field];
        }
    });
    coop.template_id = Number(coop.template_id);
    return Mock.ok(mockCooperativeSummary(coop));
});

// ---------- Kelas sekolah ----------

Mock.register('vendor/grades/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    return Mock.ok(mockGradesFor(coop.school_type));
});

Mock.register('vendor/classes/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var grades = mockGradesFor(coop.school_type);
    var rows = mockCoopRows(MockVendor.classes, coop.id).map(function (row) {
        return { id: row.id, grade_level_code: row.grade_level_code, class_name: row.class_name, sort_order: row.sort_order };
    }).sort(function (a, b) {
        return grades.indexOf(a.grade_level_code) - grades.indexOf(b.grade_level_code) || a.sort_order - b.sort_order;
    });
    return Mock.ok(rows);
});

// Tambah beberapa kelas sekali gus: names = ['Bestari', 'Cemerlang']
Mock.register('vendor/classes/add', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['grade_level_code']);
    var names = (body.names || []).filter(function (name) { return name.trim() !== ''; });
    if (names.length === 0) {
        errors.names = T('error.required');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var existing = mockCoopRows(MockVendor.classes, coop.id).filter(function (row) {
        return row.grade_level_code === body.grade_level_code;
    });
    var duplicates = names.filter(function (name) {
        return existing.some(function (row) { return row.class_name.toLowerCase() === name.toLowerCase(); });
    });
    if (duplicates.length) {
        return Mock.fail(T('class.duplicate', { names: duplicates.join(', ') }), { names: T('class.duplicate', { names: duplicates.join(', ') }) });
    }
    names.forEach(function (name, index) {
        MockVendor.classes.push({ id: Mock.nextId(MockVendor.classes), vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id,
            grade_level_code: body.grade_level_code, class_name: name, sort_order: existing.length + index + 1 });
    });
    return Mock.ok({ added: names.length });
});

Mock.register('vendor/classes/update', function (body) {
    var row = mockCoopRows(MockVendor.classes, body.cooperative_id).filter(function (item) { return item.id === Number(body.id); })[0];
    if (!row) {
        return mockCoopError();
    }
    if (!String(body.class_name || '').trim()) {
        return Mock.fail(T('error.required'), { class_name: T('error.required') });
    }
    row.class_name = body.class_name;
    return Mock.ok(row);
});

Mock.register('vendor/classes/delete', function (body) {
    var row = mockCoopRows(MockVendor.classes, body.cooperative_id).filter(function (item) { return item.id === Number(body.id); })[0];
    if (!row) {
        return mockCoopError();
    }
    MockVendor.classes = MockVendor.classes.filter(function (item) { return item.id !== row.id; });
    return Mock.ok({ id: row.id });
});

// ---------- Kategori ----------

Mock.register('vendor/categories/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRowsByCoop(MockVendor.categories, coop.id).map(function (category) {
        return {
            id: category.id, name: category.name, sort_order: category.sort_order, is_active: category.is_active,
            product_count: mockCoopRows(MockVendor.products, coop.id).filter(function (product) { return product.category_id === category.id; }).length
        };
    });
    return Mock.ok(mockSortBy(rows, 'sort_order'));
});

// Kategori tiada vendor_id dalam mock — skop melalui koperasi yang sudah disahkan
function mockCoopRowsByCoop(rows, cooperativeId) {
    return rows.filter(function (row) {
        return row.cooperative_id === Number(cooperativeId);
    });
}

function mockSortBy(rows, field) {
    return rows.slice().sort(function (a, b) {
        return a[field] - b[field];
    });
}

Mock.register('vendor/categories/save', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['name']);
    var clash = mockCoopRowsByCoop(MockVendor.categories, coop.id).some(function (row) {
        return row.name.toLowerCase() === String(body.name || '').toLowerCase() && row.id !== Number(body.id);
    });
    if (clash) {
        errors.name = T('error.duplicate');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var category = body.id ? mockCoopRowsByCoop(MockVendor.categories, coop.id).filter(function (row) { return row.id === Number(body.id); })[0] : null;
    if (body.id && !category) {
        return mockCoopError();
    }
    if (!category) {
        category = { id: Mock.nextId(MockVendor.categories), cooperative_id: coop.id };
        MockVendor.categories.push(category);
    }
    category.name = body.name;
    category.sort_order = body.sort_order || 0;
    category.is_active = body.is_active;
    return Mock.ok(category);
});

Mock.register('vendor/categories/delete', function (body) {
    var coop = mockRequireCoop(body);
    var category = coop ? mockCoopRowsByCoop(MockVendor.categories, coop.id).filter(function (row) { return row.id === Number(body.id); })[0] : null;
    if (!category) {
        return mockCoopError();
    }
    var inUse = mockCoopRows(MockVendor.products, coop.id).some(function (product) { return product.category_id === category.id; });
    if (inUse) {
        return Mock.fail(T('category.delete_in_use'));
    }
    MockVendor.categories = MockVendor.categories.filter(function (row) { return row.id !== category.id; });
    return Mock.ok({ id: category.id });
});

// ---------- Produk ----------

Mock.register('vendor/products/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockVendor.products, coop.id).filter(function (product) {
        return (!params.category_id || product.category_id === Number(params.category_id))
            && (!params.grade || product.grade_levels.indexOf(params.grade) !== -1 || (params.grade === 'all' && product.grade_levels.length === 0))
            && (!params.status || (params.status === 'active') === product.is_active);
    }).map(mockProductSummary).filter(function (row) {
        return !params.low_only || row.is_low;
    });
    return Mock.ok(Mock.paginate(rows, params, ['name', 'sku', 'barcode']));
});

Mock.register('vendor/products/get', function (params) {
    var product = mockFindProduct(params.id, params.cooperative_id);
    if (!product) {
        return mockCoopError();
    }
    var data = JSON.parse(JSON.stringify(product));
    delete data.vendor_id;
    return Mock.ok(data);
});

// Simpan produk + varian. Harga dalam sen. Barcode unik dalam koperasi
Mock.register('vendor/products/save', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['name', 'category_id', 'price', 'unit']);
    var own = mockCoopRows(MockVendor.products, coop.id);
    if (body.barcode && own.some(function (row) { return row.barcode === body.barcode && row.id !== Number(body.id); })) {
        errors.barcode = T('error.duplicate');
    }
    if (body.sku && own.some(function (row) { return row.sku.toLowerCase() === body.sku.toLowerCase() && row.id !== Number(body.id); })) {
        errors.sku = T('error.duplicate');
    }
    if (body.has_variants && (!body.variants || body.variants.length === 0)) {
        errors.variants = T('product.variants_required');
    }
    if (!mockCoopRowsByCoop(MockVendor.categories, coop.id).some(function (row) { return row.id === Number(body.category_id); })) {
        errors.category_id = T('error.invalid');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var product = body.id ? mockFindProduct(body.id, coop.id) : null;
    if (body.id && !product) {
        return mockCoopError();
    }
    if (!product) {
        product = { id: Mock.nextId(MockVendor.products), vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id, variants: [], sort_order: own.length + 1 };
        MockVendor.products.push(product);
    }
    ['name', 'description', 'sku', 'barcode', 'price', 'cost_price', 'unit', 'max_per_order', 'is_active', 'grade_levels', 'translations', 'image_url'].forEach(function (field) {
        if (body[field] !== undefined) {
            product[field] = body[field];
        }
    });
    product.category_id = Number(body.category_id);
    product.has_variants = Boolean(body.has_variants);
    mockSaveVariants(product, body.has_variants ? body.variants : []);
    return Mock.ok(mockProductSummary(product));
});

// Varian baharu → baris stok 0; tanpa varian → satu baris stok variant_id 0
function mockSaveVariants(product, variants) {
    product.variants = variants.map(function (input) {
        var variant = input.id ? mockFindVariant(product, input.id) : null;
        if (!variant) {
            variant = { id: MockVendor.products.reduce(function (max, row) {
                return Math.max(max, row.variants.reduce(function (m, v) { return Math.max(m, v.id); }, 0));
            }, 0) + 1 };
        }
        variant.label = input.label;
        variant.sku = input.sku || '';
        variant.barcode = input.barcode || '';
        variant.price_delta = input.price_delta || 0;
        variant.is_active = input.is_active !== false;
        return variant;
    });

    var wanted = product.has_variants ? product.variants.map(function (variant) { return variant.id; }) : [0];
    wanted.forEach(function (variantId) {
        if (!mockStockLevel(product.cooperative_id, product.id, variantId)) {
            MockVendor.stock.push({ vendor_id: product.vendor_id, cooperative_id: product.cooperative_id, product_id: product.id, variant_id: variantId, qty_on_hand: 0, qty_reserved: 0, min_stock: 5 });
        }
    });
    // Baris stok varian yang dibuang disembunyikan (data sejarah kekal dalam pergerakan)
    MockVendor.stock = MockVendor.stock.filter(function (level) {
        return level.product_id !== product.id || wanted.indexOf(level.variant_id) !== -1;
    });
}

Mock.register('vendor/products/set-active', function (body) {
    var product = mockFindProduct(body.id, body.cooperative_id);
    if (!product) {
        return mockCoopError();
    }
    product.is_active = Boolean(body.is_active);
    return Mock.ok(mockProductSummary(product));
});

// Pilihan produk/varian rata untuk PO, stok masuk, dll.
Mock.register('vendor/products/options', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var options = [];
    mockCoopRows(MockVendor.products, coop.id).forEach(function (product) {
        if (product.has_variants) {
            product.variants.forEach(function (variant) {
                options.push({ product_id: product.id, variant_id: variant.id, name: product.name + ' — ' + variant.label, sku: variant.sku, cost_price: product.cost_price });
            });
        } else {
            options.push({ product_id: product.id, variant_id: 0, name: product.name, sku: product.sku, cost_price: product.cost_price });
        }
    });
    return Mock.ok(options);
});

// ---------- Inventori ----------

Mock.register('vendor/inventory/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockVendor.stock, coop.id).map(mockInventoryRow).filter(function (row) {
        return (!params.category_id || row.category_id === Number(params.category_id)) && (!params.low_only || row.is_low);
    });
    var data = Mock.paginate(rows, params, ['product_name', 'variant_label', 'sku']);
    data.low_count = rows.filter(function (row) { return row.is_low; }).length;
    return Mock.ok(data);
});

function mockRequireLevel(body) {
    var coop = mockRequireCoop(body);
    return coop ? mockStockLevel(coop.id, body.product_id, body.variant_id) : null;
}

// Stok masuk tanpa PO (cth. hadiah, pindahan)
Mock.register('vendor/inventory/stock-in', function (body) {
    var level = mockRequireLevel(body);
    if (!level) {
        return mockCoopError();
    }
    if (!(body.qty > 0)) {
        return Mock.fail(T('inventory.qty_positive'), { qty: T('inventory.qty_positive') });
    }
    mockMoveStock(level, 'restock', body.qty, null, null, body.note);
    return Mock.ok(mockInventoryRow(level));
});

// Pelarasan: kiraan sebenar baharu + sebab wajib. Tidak boleh di bawah kuantiti ditahan
Mock.register('vendor/inventory/adjust', function (body) {
    var level = mockRequireLevel(body);
    if (!level) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['new_qty', 'reason']);
    if (body.new_qty !== null && body.new_qty < level.qty_reserved) {
        errors.new_qty = T('inventory.below_reserved', { n: level.qty_reserved });
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var change = body.new_qty - level.qty_on_hand;
    if (change !== 0) {
        mockMoveStock(level, 'adjust', change, null, null, T('inventory.reason.' + body.reason) + (body.note ? ' — ' + body.note : ''));
    }
    level.min_stock = body.min_stock !== null && body.min_stock !== undefined ? body.min_stock : level.min_stock;
    return Mock.ok(mockInventoryRow(level));
});

Mock.register('vendor/inventory/movements', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockVendor.movements, coop.id).filter(function (row) {
        var day = row.created_at.slice(0, 10);
        return (!params.type || row.type === params.type)
            && (!params.product_id || row.product_id === Number(params.product_id))
            && (params.variant_id === undefined || params.variant_id === '' || params.variant_id === null || row.variant_id === Number(params.variant_id))
            && (!params.date_from || day >= params.date_from) && (!params.date_to || day <= params.date_to);
    }).map(function (row) {
        var product = Mock.findById(MockVendor.products, row.product_id);
        var variant = row.variant_id ? mockFindVariant(product, row.variant_id) : null;
        var item = JSON.parse(JSON.stringify(row));
        delete item.vendor_id;
        item.product_name = product.name;
        item.variant_label = variant ? variant.label : null;
        item.sku = variant ? variant.sku : product.sku;
        return item;
    });
    return Mock.ok(Mock.paginate(rows, params, ['product_name', 'variant_label', 'sku', 'note', 'user_name']));
});

// ---------- Pembekal ----------

Mock.register('vendor/suppliers/list', function (params) {
    var rows = mockOwnRows(MockVendor.suppliers).filter(function (row) {
        return !params.status || (params.status === 'active') === row.is_active;
    }).map(function (row) {
        var copy = JSON.parse(JSON.stringify(row));
        delete copy.vendor_id;
        copy.open_po_count = mockOwnRows(MockVendor.purchaseOrders).filter(function (po) {
            return po.supplier_id === row.id && ['ordered', 'partial'].indexOf(po.status) !== -1;
        }).length;
        return copy;
    });
    return Mock.ok(Mock.paginate(rows, params, ['name', 'contact_name', 'phone', 'email']));
});

Mock.register('vendor/suppliers/save', function (body) {
    var errors = Mock.requireFields(body, ['name', 'phone']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var supplier = body.id ? mockOwnRows(MockVendor.suppliers).filter(function (row) { return row.id === Number(body.id); })[0] : null;
    if (body.id && !supplier) {
        return mockCoopError();
    }
    if (!supplier) {
        supplier = { id: Mock.nextId(MockVendor.suppliers), vendor_id: MockVendor.sessionVendorId };
        MockVendor.suppliers.push(supplier);
    }
    ['name', 'contact_name', 'phone', 'email', 'address', 'is_active'].forEach(function (field) {
        supplier[field] = body[field];
    });
    return Mock.ok(supplier);
});

// ---------- Pesanan belian (PO) ----------

Mock.register('vendor/purchase-orders/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockVendor.purchaseOrders, coop.id).filter(function (po) {
        return (!params.status || po.status === params.status) && (!params.supplier_id || po.supplier_id === Number(params.supplier_id));
    }).sort(function (a, b) {
        return b.id - a.id;
    }).map(function (po) {
        var detail = mockPurchaseOrderDetail(po);
        delete detail.items;
        detail.item_count = po.items.length;
        return detail;
    });
    return Mock.ok(Mock.paginate(rows, params, ['po_no', 'supplier_name', 'note']));
});

function mockFindPo(id, cooperativeId) {
    return mockCoopRows(MockVendor.purchaseOrders, cooperativeId).filter(function (po) {
        return po.id === Number(id);
    })[0] || null;
}

Mock.register('vendor/purchase-orders/get', function (params) {
    var po = mockFindPo(params.id, params.cooperative_id);
    if (!po) {
        return mockCoopError();
    }
    return Mock.ok(mockPurchaseOrderDetail(po));
});

// Hanya draf boleh disunting. submit: true → status ordered
Mock.register('vendor/purchase-orders/save', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['supplier_id']);
    var items = (body.items || []).filter(function (item) { return item.qty_ordered > 0; });
    if (items.length === 0) {
        errors.items = T('po.items_required');
    }
    var supplier = mockOwnRows(MockVendor.suppliers).filter(function (row) { return row.id === Number(body.supplier_id); })[0];
    if (body.supplier_id && !supplier) {
        errors.supplier_id = T('error.invalid');
    }
    var badItem = items.some(function (item) {
        var product = mockFindProduct(item.product_id, coop.id);
        return !product || (item.variant_id && !mockFindVariant(product, item.variant_id));
    });
    if (badItem) {
        errors.items = T('error.invalid');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var po = body.id ? mockFindPo(body.id, coop.id) : null;
    if (body.id && !po) {
        return mockCoopError();
    }
    if (po && po.status !== 'draft') {
        return Mock.fail(T('po.not_editable'));
    }
    if (!po) {
        var count = MockVendor.purchaseOrders.length + 6;
        po = { id: Mock.nextId(MockVendor.purchaseOrders), vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id,
            po_no: 'PO-2026-' + String(count).padStart(4, '0'), status: 'draft', ordered_at: null, received_at: null };
        MockVendor.purchaseOrders.push(po);
    }
    po.supplier_id = Number(body.supplier_id);
    po.note = body.note || '';
    po.items = items.map(function (item, index) {
        return { id: po.id * 100 + index, product_id: Number(item.product_id), variant_id: Number(item.variant_id || 0),
            qty_ordered: item.qty_ordered, qty_received: 0, unit_cost: item.unit_cost || 0 };
    });
    if (body.submit) {
        po.status = 'ordered';
        po.ordered_at = Mock.now();
    }
    return Mock.ok(mockPurchaseOrderDetail(po));
});

// Terima barang: lines = [{ item_id, qty }] — stok masuk automatik (po_receive)
Mock.register('vendor/purchase-orders/receive', function (body) {
    var po = mockFindPo(body.id, body.cooperative_id);
    if (!po) {
        return mockCoopError();
    }
    if (['ordered', 'partial'].indexOf(po.status) === -1) {
        return Mock.fail(T('po.cannot_receive'));
    }
    var lines = (body.lines || []).filter(function (line) { return line.qty > 0; });
    if (lines.length === 0) {
        return Mock.fail(T('po.receive_nothing'));
    }
    var over = lines.some(function (line) {
        var item = po.items.filter(function (row) { return row.id === Number(line.item_id); })[0];
        return !item || line.qty > item.qty_ordered - item.qty_received;
    });
    if (over) {
        return Mock.fail(T('po.receive_too_many'));
    }

    lines.forEach(function (line) {
        var item = po.items.filter(function (row) { return row.id === Number(line.item_id); })[0];
        item.qty_received += line.qty;
        var level = mockStockLevel(po.cooperative_id, item.product_id, item.variant_id);
        mockMoveStock(level, 'po_receive', line.qty, 'purchase_order', po.id, po.po_no);
    });
    var complete = po.items.every(function (item) { return item.qty_received >= item.qty_ordered; });
    po.status = complete ? 'received' : 'partial';
    po.received_at = complete ? Mock.now() : null;
    return Mock.ok(mockPurchaseOrderDetail(po));
});

Mock.register('vendor/purchase-orders/cancel', function (body) {
    var po = mockFindPo(body.id, body.cooperative_id);
    if (!po) {
        return mockCoopError();
    }
    if (['draft', 'ordered'].indexOf(po.status) === -1) {
        return Mock.fail(T('po.cannot_cancel'));
    }
    po.status = 'cancelled';
    return Mock.ok(mockPurchaseOrderDetail(po));
});

// ---------- Stock take ----------

Mock.register('vendor/stock-takes/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockVendor.stockTakes, coop.id).sort(function (a, b) { return b.id - a.id; }).map(mockStockTakeSummary);
    return Mock.ok(Mock.paginate(rows, params, []));
});

// Mula: salin baki semasa (qty_system) bagi setiap produk aktif. Satu stock take terbuka sahaja
Mock.register('vendor/stock-takes/start', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var open = mockCoopRows(MockVendor.stockTakes, coop.id).some(function (take) { return take.status === 'open'; });
    if (open) {
        return Mock.fail(T('stocktake.already_open'));
    }
    var take = { id: Mock.nextId(MockVendor.stockTakes), vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id, status: 'open',
        started_by: MockVendor.sessionUserName, started_at: Mock.now(), completed_at: null, items: [] };
    mockCoopRows(MockVendor.stock, coop.id).forEach(function (level, index) {
        var row = mockInventoryRow(level);
        if (row.is_active) {
            take.items.push({ id: index + 1, product_id: level.product_id, variant_id: level.variant_id, product_name: row.product_name,
                variant_label: row.variant_label, sku: row.sku, category_name: row.category_name, qty_system: level.qty_on_hand, qty_counted: null });
        }
    });
    MockVendor.stockTakes.push(take);
    return Mock.ok(mockStockTakeSummary(take));
});

function mockFindTake(id, cooperativeId) {
    return mockCoopRows(MockVendor.stockTakes, cooperativeId).filter(function (take) {
        return take.id === Number(id);
    })[0] || null;
}

Mock.register('vendor/stock-takes/get', function (params) {
    var take = mockFindTake(params.id, params.cooperative_id);
    if (!take) {
        return mockCoopError();
    }
    var data = mockStockTakeSummary(take);
    data.items = take.items;
    return Mock.ok(data);
});

// counts = [{ item_id, qty_counted }] — qty_counted null = belum dikira
Mock.register('vendor/stock-takes/save-counts', function (body) {
    var take = mockFindTake(body.id, body.cooperative_id);
    if (!take) {
        return mockCoopError();
    }
    if (take.status !== 'open') {
        return Mock.fail(T('stocktake.not_open'));
    }
    (body.counts || []).forEach(function (count) {
        var item = take.items.filter(function (row) { return row.id === Number(count.item_id); })[0];
        if (item) {
            item.qty_counted = count.qty_counted === null || count.qty_counted === '' ? null : Math.max(0, Number(count.qty_counted));
        }
    });
    return Mock.ok(mockStockTakeSummary(take));
});

// Selesai: item yang dikira → pergerakan "stocktake" untuk perbezaan. Belum dikira → tidak diubah
Mock.register('vendor/stock-takes/complete', function (body) {
    var take = mockFindTake(body.id, body.cooperative_id);
    if (!take) {
        return mockCoopError();
    }
    if (take.status !== 'open') {
        return Mock.fail(T('stocktake.not_open'));
    }
    take.items.forEach(function (item) {
        if (item.qty_counted === null) {
            return;
        }
        var level = mockStockLevel(take.cooperative_id, item.product_id, item.variant_id);
        // Perbezaan dikira terhadap baki semasa (jualan semasa kiraan sudah direkod)
        var change = item.qty_counted - item.qty_system;
        if (level && change !== 0) {
            mockMoveStock(level, 'stocktake', change, 'stock_take', take.id, '');
        }
    });
    take.status = 'completed';
    take.completed_at = Mock.now();
    return Mock.ok(mockStockTakeSummary(take));
});

// Kekalkan perubahan antara halaman (sessionStorage) — mesti selepas data contoh dijana
Mock.persist('vendor', MockVendor, ['cooperatives', 'activeModules', 'classes', 'categories', 'products', 'stock', 'movements',
    'suppliers', 'purchaseOrders', 'stockTakes', 'dashboard']);
