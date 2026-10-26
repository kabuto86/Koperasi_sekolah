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

    // Jualan & pesanan untuk papan pemuka (diisi sepenuhnya dalam U3b/U4)
    dashboard: {
        1: { sales_today: 184250, sales_today_count: 37, orders_to_prepare: 12, orders_ready: 8,
             sales_7days: [98500, 120300, 87600, 143000, 165400, 59000, 184250] },
        2: { sales_today: 45600, sales_today_count: 9, orders_to_prepare: 3, orders_ready: 1,
             sales_7days: [22000, 31500, 18000, 40500, 37800, 0, 45600] },
        3: { sales_today: 76000, sales_today_count: 15, orders_to_prepare: 5, orders_ready: 4,
             sales_7days: [50000, 61000, 43000, 70000, 66000, 0, 76000] }
    },

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

// Laluan katalog, inventori, pembekal, PO, stock take, koperasi & papan pemuka: sebenar sejak B4 (api/v1/vendor/*).
// Data di atas kekal sementara untuk mock modul lain (kit B5, pesanan B9, POS B10, add-on B13, store B6).

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

// Kekalkan perubahan antara halaman (sessionStorage) — mesti selepas data contoh dijana
Mock.persist('vendor', MockVendor, ['cooperatives', 'activeModules', 'classes', 'categories', 'products', 'stock', 'movements',
    'dashboard']);
