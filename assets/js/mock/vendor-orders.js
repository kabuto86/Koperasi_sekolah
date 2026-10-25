// Data mock vendor bahagian 2 (U3b): kit sekolah, pesanan, serah barang, ibu bapa & murid.
// Bergantung pada mock/vendor.js (MockVendor, mockRequireCoop, mockCoopRows, mockFindProduct …).

var MockOrders = {
    // Ibu bapa ialah akaun GLOBAL (tiada vendor_id). Vendor hanya melihat ibu bapa yang ada anak di koperasinya,
    // dan hanya anak di koperasi itu.
    customers: [
        { id: 1, full_name: 'Rosnah binti Ali', phone: '012-3344556', email: 'rosnah@contoh.my', relationship: 'mother' },
        { id: 2, full_name: 'Tan Mei Ling', phone: '016-7788990', email: 'meiling.tan@contoh.my', relationship: 'mother' },
        { id: 3, full_name: 'Kumar a/l Suppiah', phone: '019-2233445', email: 'kumar.s@contoh.my', relationship: 'father' },
        { id: 4, full_name: 'Nurul Izzah binti Hassan', phone: '013-5566778', email: 'izzah@contoh.my', relationship: 'mother' },
        { id: 5, full_name: 'Lim Wei Siang', phone: '012-9988776', email: 'weisiang@contoh.my', relationship: 'father' },
        { id: 6, full_name: 'Faridah binti Hamid', phone: '017-1122334', email: 'faridah.h@contoh.my', relationship: 'guardian' },
        { id: 7, full_name: 'Siti Khadijah binti Omar', phone: '011-23344556', email: 'khadijah@contoh.my', relationship: 'mother' },
        { id: 8, full_name: 'Ravi Chandran', phone: '014-6677889', email: 'ravi.c@contoh.my', relationship: 'father' },
        { id: 9, full_name: 'Azlina binti Yusof', phone: '018-3344221', email: 'azlina@contoh.my', relationship: 'mother' },
        { id: 10, full_name: 'Wong Kah Mun', phone: '012-5544332', email: 'kahmun@contoh.my', relationship: 'mother' },
        { id: 11, full_name: 'Hafiz bin Rahim', phone: '019-8877665', email: 'hafiz.r@contoh.my', relationship: 'father' },
        { id: 12, full_name: 'Mariam binti Osman', phone: '016-4455667', email: 'mariam.o@contoh.my', relationship: 'mother' },
        { id: 13, full_name: 'Chong Siew Lan', phone: '017-9988112', email: 'siewlan@contoh.my', relationship: 'mother' },
        { id: 14, full_name: 'Zulkifli bin Ahmad', phone: '013-2211009', email: 'zul.ahmad@contoh.my', relationship: 'father' },
        { id: 15, full_name: 'Priya Devi', phone: '011-55667788', email: 'priya.d@contoh.my', relationship: 'mother' }
    ],

    // children.vendor_id = salinan untuk tapisan vendor
    children: [
        { id: 1, customer_id: 1, cooperative_id: 1, full_name: 'Aisyah binti Rahman', grade_level_code: 'D4', class_name: 'Bestari' },
        { id: 2, customer_id: 1, cooperative_id: 1, full_name: 'Adam bin Rahman', grade_level_code: 'D1', class_name: 'Cemerlang' },
        { id: 3, customer_id: 2, cooperative_id: 2, full_name: 'Tan Jia Hui', grade_level_code: 'D3', class_name: 'A' },
        { id: 4, customer_id: 2, cooperative_id: 1, full_name: 'Tan Jia Wei', grade_level_code: 'D6', class_name: 'Dinamik' },
        { id: 5, customer_id: 3, cooperative_id: 1, full_name: 'Kavitha a/p Kumar', grade_level_code: 'D2', class_name: 'Bestari' },
        { id: 6, customer_id: 4, cooperative_id: 1, full_name: 'Muhammad Danish', grade_level_code: 'D4', class_name: 'Cemerlang' },
        { id: 7, customer_id: 4, cooperative_id: 1, full_name: 'Nur Aleya', grade_level_code: 'PRA', class_name: 'Tadika Ceria' },
        { id: 8, customer_id: 5, cooperative_id: 2, full_name: 'Lim Zi Xuan', grade_level_code: 'D1', class_name: 'B' },
        { id: 9, customer_id: 6, cooperative_id: 1, full_name: 'Haziq bin Faizal', grade_level_code: 'D5', class_name: 'Bestari' },
        { id: 10, customer_id: 7, cooperative_id: 1, full_name: 'Irfan bin Zainal', grade_level_code: 'D3', class_name: 'Dinamik' },
        { id: 11, customer_id: 7, cooperative_id: 1, full_name: 'Insyirah binti Zainal', grade_level_code: 'D1', class_name: 'Bestari' },
        { id: 12, customer_id: 8, cooperative_id: 1, full_name: 'Divya a/p Ravi', grade_level_code: 'D4', class_name: 'Bestari' },
        { id: 13, customer_id: 9, cooperative_id: 3, full_name: 'Amir bin Kamal', grade_level_code: 'T2', class_name: 'Amanah' },
        { id: 14, customer_id: 10, cooperative_id: 1, full_name: 'Wong Yi Ting', grade_level_code: 'D2', class_name: 'Cemerlang' },
        { id: 15, customer_id: 11, cooperative_id: 1, full_name: 'Hana binti Hafiz', grade_level_code: 'D6', class_name: 'Bestari' },
        { id: 16, customer_id: 11, cooperative_id: 3, full_name: 'Harith bin Hafiz', grade_level_code: 'T1', class_name: 'Bestari' },
        { id: 17, customer_id: 12, cooperative_id: 1, full_name: 'Zara binti Iskandar', grade_level_code: 'D3', class_name: 'Bestari' },
        { id: 18, customer_id: 13, cooperative_id: 2, full_name: 'Chong Kai Le', grade_level_code: 'D5', class_name: 'A' },
        { id: 19, customer_id: 14, cooperative_id: 1, full_name: 'Aqil bin Zulkifli', grade_level_code: 'D4', class_name: 'Dinamik' },
        { id: 20, customer_id: 15, cooperative_id: 1, full_name: 'Arjun a/l Kumar', grade_level_code: 'D1', class_name: 'Cemerlang' }
    ],

    // Nota vendor tentang ibu bapa (per vendor)
    customerNotes: [
        { vendor_id: 1, customer_id: 6, note: 'Penjaga — nenek Haziq. Lebih suka dihubungi waktu petang.' }
    ],

    // Tetapan cara ambil per koperasi (PELAN 7). code_required_above dalam sen (0 = tidak wajib ikut nilai)
    pickupSettings: {
        1: { methods: ['A', 'B', 'C', 'D'], code_required_above: 10000 },
        2: { methods: ['A', 'B', 'C', 'D', 'E'], code_required_above: 0 },
        3: { methods: ['A', 'B', 'C'], code_required_above: 0 }
    },

    kits: [],
    orders: [],
    pickups: []
};

// ---------- Data contoh: kit sekolah ----------
(function seedKits() {
    // [koperasi, darjah, tahun, terbit?, [[produk, varian|null, kuantiti, wajib, nota]]]
    var rows = [
        [1, 'D1', 2026, true, [[1, null, 5, true, ''], [3, null, 3, true, ''], [6, null, 1, true, ''], [7, null, 2, false, ''], [9, null, 1, false, ''],
            [11, null, 2, true, 'Pilih saiz anak'], [12, null, 2, false, 'Lelaki'], [13, null, 2, false, 'Perempuan'], [15, null, 1, true, ''], [16, null, 1, false, '']]],
        [1, 'D2', 2026, true, [[1, null, 6, true, ''], [3, null, 3, true, ''], [6, null, 1, true, ''], [7, null, 2, false, ''], [11, null, 2, true, ''], [15, null, 1, true, '']]],
        [1, 'D3', 2026, true, [[1, null, 6, true, ''], [2, null, 2, true, ''], [3, null, 2, true, ''], [6, null, 1, true, ''], [8, null, 1, false, ''], [11, null, 2, true, '']]],
        [1, 'D4', 2026, true, [[1, null, 4, true, ''], [2, null, 4, true, ''], [4, null, 1, true, 'Edisi KSSR semakan'], [5, null, 1, true, ''], [6, null, 1, true, ''],
            [8, null, 1, false, ''], [10, null, 1, false, ''], [14, null, 1, true, 'Mula Darjah 4'], [11, null, 2, true, '']]],
        [1, 'D5', 2026, false, [[1, null, 4, true, ''], [2, null, 4, true, ''], [6, null, 1, true, ''], [14, null, 1, true, '']]],
        [1, 'D6', 2026, true, [[1, null, 4, true, ''], [2, null, 4, true, ''], [6, null, 1, true, ''], [14, null, 1, true, ''], [17, null, 1, false, '']]],
        [1, 'D1', 2027, false, [[1, null, 5, true, ''], [3, null, 3, true, ''], [6, null, 1, true, ''], [11, null, 2, true, 'Pilih saiz anak']]],
        [2, 'D1', 2026, true, [[19, null, 1, true, ''], [20, null, 2, true, ''], [21, null, 6, true, ''], [22, null, 2, true, '']]],
        [3, 'T1', 2026, true, [[23, null, 2, true, ''], [24, null, 1, true, '']]]
    ];
    rows.forEach(function (row, index) {
        MockOrders.kits.push({
            id: index + 1, vendor_id: row[0] === 3 ? 2 : 1, cooperative_id: row[0], grade_level_code: row[1], school_year: row[2],
            name: '', note: row[2] === 2027 ? 'Senarai sementara — menunggu pengesahan guru besar.' : '', is_published: row[3],
            items: row[4].map(function (item, itemIndex) {
                return { id: index * 100 + itemIndex + 1, product_id: item[0], variant_id: item[1], qty: item[2], is_mandatory: item[3], note: item[4], sort_order: itemIndex + 1 };
            })
        });
    });
})();

// ---------- Data contoh: pesanan ----------
(function seedOrders() {
    var seed = 7;
    function random(max) {
        seed = (seed * 9301 + 49297) % 233280;
        return Math.floor(seed / 233280 * max);
    }

    // Kumpulan barang ikut darjah (koperasi 1). [produk, varian]
    var pools = {
        PRA: [[9, 0], [7, 0], [11, 1]],
        D1: [[1, 0], [3, 0], [6, 0], [11, 1], [12, 5], [15, 0], [16, 11]],
        D2: [[1, 0], [3, 0], [6, 0], [11, 2], [13, 8]],
        D3: [[1, 0], [2, 0], [8, 0], [11, 2], [12, 6]],
        D4: [[2, 0], [4, 0], [5, 0], [14, 0], [11, 2], [13, 9], [10, 0]],
        D5: [[2, 0], [14, 0], [11, 3], [17, 0]],
        D6: [[2, 0], [14, 0], [11, 4], [12, 7], [17, 0]]
    };
    // Taburan status setiap koperasi (indeks pesanan dalam koperasi itu)
    var statuses = ['ready', 'ready', 'preparing', 'paid', 'collected', 'ready', 'unpaid_cash', 'preparing', 'partially_collected', 'pending_payment',
        'ready', 'paid', 'cancelled', 'preparing', 'ready', 'collected', 'unpaid_cash', 'ready', 'expired', 'paid', 'preparing', 'ready', 'collected',
        'ready', 'unpaid_cash', 'preparing', 'ready', 'collected', 'ready', 'paid'];
    var coopIndex = { 1: 0, 2: 0, 3: 0 };
    var codes = { 1: 100, 2: 500, 3: 700 };
    var orderId = 1;

    function itemsFor(child, count) {
        var pool = child.cooperative_id === 1 ? pools[child.grade_level_code] : (child.cooperative_id === 2 ? [[19, 0], [21, 0], [22, 14], [20, 0]] : [[23, 16], [24, 0]]);
        var picked = [];
        for (var n = 0; n < count && picked.length < pool.length; n++) {
            var choice = pool[(random(pool.length) + n) % pool.length];
            if (picked.every(function (p) { return p[0] !== choice[0] || p[1] !== choice[1]; })) {
                picked.push(choice);
            }
        }
        return picked.map(function (choice) {
            var product = Mock.findById(MockVendor.products, choice[0]);
            var variant = choice[1] ? product.variants.filter(function (v) { return v.id === choice[1]; })[0] : null;
            var qty = product.unit === 'buah' && product.price < 300 ? 2 + random(4) : 1 + random(2);
            var price = product.price + (variant ? variant.price_delta : 0);
            return {
                product_id: product.id, variant_id: choice[1], product_name: product.name, variant_label: variant ? variant.label : null,
                sku: variant ? variant.sku : product.sku, unit_price: price, qty: qty, line_total: price * qty, source: random(3) === 0 ? 'ala_carte' : 'kit',
                child_id: child.id, child_name: child.full_name, grade_level_code: child.grade_level_code, class_name: child.class_name, collected_qty: 0
            };
        });
    }

    // Satu atau dua pesanan bagi setiap anak; ibu bapa dua anak kadang-kadang satu pesanan untuk kedua-duanya
    var groups = [];
    MockOrders.customers.forEach(function (customer) {
        var kids = MockOrders.children.filter(function (child) { return child.customer_id === customer.id; });
        var byCoop = {};
        kids.forEach(function (kid) {
            (byCoop[kid.cooperative_id] = byCoop[kid.cooperative_id] || []).push(kid);
        });
        Object.keys(byCoop).forEach(function (coopId) {
            groups.push({ customer: customer, coopId: Number(coopId), kids: byCoop[coopId] });
            if (byCoop[coopId].length === 1 && customer.id % 2 === 1) {
                groups.push({ customer: customer, coopId: Number(coopId), kids: byCoop[coopId] });
            }
        });
    });

    // Koperasi 1 (demo utama): dua pesanan bagi setiap kumpulan
    var expanded = [];
    groups.forEach(function (group) {
        expanded.push(group);
        if (group.coopId === 1) {
            expanded.push(group);
        }
    });

    expanded.forEach(function (group) {
        var index = coopIndex[group.coopId]++;
        var status = statuses[index % statuses.length];
        var items = [];
        group.kids.forEach(function (kid) {
            items = items.concat(itemsFor(kid, 2 + random(3)));
        });
        // Tunai: unpaid_cash, dan sebahagian pesanan yang sudah disediakan / sedia (bayar semasa ambil)
        var acceptsCash = Mock.findById(MockVendor.cooperatives, group.coopId).accept_cash;
        var isCash = acceptsCash && (status === 'unpaid_cash' || (index % 3 === 0 && ['preparing', 'ready', 'collected', 'partially_collected'].indexOf(status) !== -1));
        if (!acceptsCash && status === 'unpaid_cash') {
            status = 'paid';
        }
        var subtotal = items.reduce(function (sum, item) { return sum + item.line_total; }, 0);
        var day = 10 + (index % 15);
        var created = '2026-10-' + String(day).padStart(2, '0') + 'T' + String(8 + index % 12).padStart(2, '0') + ':' + (10 + index % 49) + ':00+08:00';
        var paid = !isCash && ['pending_payment', 'cancelled', 'expired'].indexOf(status) === -1;
        var cashPaid = isCash && ['collected', 'partially_collected'].indexOf(status) !== -1;
        codes[group.coopId] += 7;

        if (status === 'collected') {
            items.forEach(function (item) { item.collected_qty = item.qty; });
        }
        if (status === 'partially_collected') {
            items.forEach(function (item, i) { item.collected_qty = i === 0 ? 0 : item.qty; });
        }

        var order = {
            id: orderId, vendor_id: group.coopId === 3 ? 2 : 1, cooperative_id: group.coopId, customer_id: group.customer.id,
            order_no: (group.coopId === 1 ? 'TM' : (group.coopId === 2 ? 'CH' : 'BI')) + '-2610-' + String(orderId).padStart(4, '0'),
            short_code: String.fromCharCode(65 + (orderId % 8)) + String(codes[group.coopId]).slice(-3),
            channel: 'online', payment_method: isCash ? 'cash' : 'online', status: isCash && status === 'paid' ? 'unpaid_cash' : status,
            payment_status: paid || cashPaid ? 'paid' : (status === 'pending_payment' ? 'pending' : (['cancelled', 'expired'].indexOf(status) !== -1 ? 'none' : 'unpaid')),
            subtotal: subtotal, discount: 0, total: subtotal,
            pickup_by_type: index % 3 === 0 ? 'parent' : (index % 7 === 0 ? 'proxy' : 'student'),
            pickup_by_name: index % 3 === 0 ? group.customer.full_name : (index % 7 === 0 ? 'Pak Long Rahim' : group.kids[0].full_name),
            pickup_code: String(1000 + (orderId * 739) % 9000), pickup_attempts: 0, pickup_locked: false,
            pickup_qr_token: 'KPK-' + (orderId * 7919).toString(36).toUpperCase().padStart(6, '0'),
            customer_name: group.customer.full_name, customer_phone: group.customer.phone, customer_email: group.customer.email,
            expires_at: isCash ? '2026-10-' + String(Math.min(day + 3, 31)).padStart(2, '0') + 'T23:59:59+08:00' : null,
            paid_at: paid ? created : null, collected_at: status === 'collected' ? '2026-10-24T10:15:00+08:00' : null,
            created_at: created, items: items, logs: []
        };
        items.forEach(function (item, i) { item.id = orderId * 100 + i + 1; });
        order.logs = mockSeedLogs(order);
        MockOrders.orders.push(order);
        if (status === 'collected' || status === 'partially_collected') {
            MockOrders.pickups.push({
                id: MockOrders.pickups.length + 1, vendor_id: order.vendor_id, order_id: order.id, method: 'A',
                picked_by_type: order.pickup_by_type, picked_by_name: order.pickup_by_name, user_name: 'Siti (juruwang)',
                created_at: '2026-10-24T10:15:00+08:00',
                items: items.filter(function (item) { return item.collected_qty > 0; }).map(function (item) { return { order_item_id: item.id, qty: item.collected_qty }; })
            });
        }
        orderId++;
    });
})();

// Sejarah status contoh ikut aliran PELAN 6
function mockSeedLogs(order) {
    var flow = {
        pending_payment: ['pending_payment'],
        paid: ['pending_payment', 'paid'],
        unpaid_cash: ['unpaid_cash'],
        preparing: [order.payment_method === 'cash' ? 'unpaid_cash' : 'paid', 'preparing'],
        ready: [order.payment_method === 'cash' ? 'unpaid_cash' : 'paid', 'preparing', 'ready'],
        partially_collected: [order.payment_method === 'cash' ? 'unpaid_cash' : 'paid', 'preparing', 'ready', 'partially_collected'],
        collected: [order.payment_method === 'cash' ? 'unpaid_cash' : 'paid', 'preparing', 'ready', 'collected'],
        cancelled: ['pending_payment', 'cancelled'],
        expired: ['unpaid_cash', 'expired']
    }[order.status] || [order.status];
    var from = null;
    return flow.map(function (to, index) {
        var log = { from_status: from, to_status: to, actor: index === 0 ? 'customer' : (to === 'paid' ? 'system' : 'vendor_user'),
            actor_name: index === 0 ? order.customer_name : (to === 'paid' ? 'ToyyibPay' : 'Encik Ahmad'), note: '',
            created_at: order.created_at.slice(0, 11) + String(8 + index * 2).padStart(2, '0') + ':00:00+08:00' };
        from = to;
        return log;
    });
}

// ---------- Pembantu ----------

// Baki belum diambil (item yang dibatalkan melalui refund tidak diserah lagi — U3c)
function mockItemRemaining(item) {
    return item.qty - item.collected_qty - (item.cancelled_qty || 0);
}

function mockFindOrder(id, cooperativeId) {
    return mockCoopRows(MockOrders.orders, cooperativeId).filter(function (order) {
        return order.id === Number(id);
    })[0] || null;
}

function mockChildrenOf(order) {
    var children = [];
    order.items.forEach(function (item) {
        var child = children.filter(function (c) { return c.child_id === item.child_id; })[0];
        if (!child) {
            child = { child_id: item.child_id, child_name: item.child_name, grade_level_code: item.grade_level_code, class_name: item.class_name, items: [] };
            children.push(child);
        }
        child.items.push(item);
    });
    return children;
}

// Kod 4 digit wajib jika cara E aktif atau jumlah melebihi had koperasi
function mockRequiresCode(order) {
    var settings = MockOrders.pickupSettings[order.cooperative_id];
    return settings.methods.indexOf('E') !== -1 || (settings.code_required_above > 0 && order.total > settings.code_required_above);
}

// Bentuk awam pesanan: kod ambil & token tidak pernah dipulangkan kecuali yang perlu
function mockOrderSummary(order) {
    var children = mockChildrenOf(order);
    return {
        id: order.id, order_no: order.order_no, short_code: order.short_code, channel: order.channel, status: order.status, payment_method: order.payment_method,
        payment_status: order.payment_status, total: order.total, customer_name: order.customer_name, customer_phone: order.customer_phone,
        children: children.map(function (child) {
            return { child_name: child.child_name, grade_level_code: child.grade_level_code, class_name: child.class_name };
        }),
        item_count: order.items.reduce(function (sum, item) { return sum + item.qty; }, 0),
        pickup_slot: addonOrderSlot(order),
        created_at: order.created_at, expires_at: order.expires_at
    };
}

function mockOrderDetail(order) {
    var data = mockOrderSummary(order);
    data.subtotal = order.subtotal;
    data.discount = order.discount;
    data.tax_rate = order.tax_rate || 0;
    data.tax = order.tax || 0;
    data.refunded_amount = order.refunded_amount || 0;
    data.customer_email = order.customer_email;
    data.pickup_by_type = order.pickup_by_type;
    data.pickup_by_name = order.pickup_by_name;
    data.requires_code = mockRequiresCode(order);
    data.pickup_locked = order.pickup_locked;
    data.paid_at = order.paid_at;
    data.collected_at = order.collected_at;
    data.groups = mockChildrenOf(order).map(function (child) {
        child.items = child.items.map(function (item) {
            return { id: item.id, product_name: item.product_name, variant_label: item.variant_label, sku: item.sku, unit_price: item.unit_price,
                qty: item.qty, line_total: item.line_total, collected_qty: item.collected_qty, cancelled_qty: item.cancelled_qty || 0,
                returned_qty: item.returned_qty || 0, source: item.source };
        });
        return child;
    });
    data.logs = order.logs;
    data.pickups = MockOrders.pickups.filter(function (pickup) { return pickup.order_id === order.id; }).map(function (pickup) {
        return { method: pickup.method, picked_by_type: pickup.picked_by_type, picked_by_name: pickup.picked_by_name, user_name: pickup.user_name,
            created_at: pickup.created_at, items: pickup.items.map(function (row) {
                var item = order.items.filter(function (i) { return i.id === row.order_item_id; })[0];
                return { product_name: item.product_name, variant_label: item.variant_label, child_name: item.child_name, qty: row.qty };
            }) };
    });
    return data;
}

function mockOrderMatches(order, params) {
    var q = String(params.q || '').trim().toLowerCase();
    var day = order.created_at.slice(0, 10);
    var statusList = params.status ? String(params.status).split(',') : null;
    var childMatch = order.items.some(function (item) {
        return (!params.grade || item.grade_level_code === params.grade) && (!params.class_name || item.class_name === params.class_name);
    });
    var textMatch = !q || [order.order_no, order.short_code, order.customer_name, order.customer_phone, order.pickup_qr_token]
        .concat(order.items.map(function (item) { return item.child_name; }))
        .some(function (text) { return String(text).toLowerCase().indexOf(q) !== -1; });
    return (!statusList || statusList.indexOf(order.status) !== -1) && childMatch && textMatch
        && (!params.payment_method || order.payment_method === params.payment_method)
        && (!params.pickup_slot_id || order.pickup_slot_id === Number(params.pickup_slot_id))
        && (!params.date_from || day >= params.date_from) && (!params.date_to || day <= params.date_to);
}

function mockLogStatus(order, to, note, actorName) {
    order.logs.push({ from_status: order.status, to_status: to, actor: 'vendor_user', actor_name: actorName || MockVendor.sessionUserName, note: note || '', created_at: Mock.now() });
    order.status = to;
}

function mockKitSummary(kit) {
    var total = 0;
    var mandatory = 0;
    kit.items.forEach(function (item) {
        var product = Mock.findById(MockVendor.products, item.product_id);
        var variant = item.variant_id ? product.variants.filter(function (v) { return v.id === item.variant_id; })[0] : null;
        var amount = (product.price + (variant ? variant.price_delta : 0)) * item.qty;
        total += amount;
        if (item.is_mandatory) {
            mandatory += amount;
        }
    });
    return { id: kit.id, grade_level_code: kit.grade_level_code, school_year: kit.school_year, name: kit.name, note: kit.note,
        is_published: kit.is_published, item_count: kit.items.length, total_price: total, mandatory_price: mandatory };
}

function mockFindKit(id, cooperativeId) {
    return mockCoopRows(MockOrders.kits, cooperativeId).filter(function (kit) { return kit.id === Number(id); })[0] || null;
}

// ---------- Kit keperluan sekolah ----------

Mock.register('vendor/kits/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var rows = mockCoopRows(MockOrders.kits, coop.id).filter(function (kit) {
        return kit.school_year === Number(params.school_year);
    }).map(mockKitSummary);
    return Mock.ok({ school_year: Number(params.school_year), grades: mockGradesFor(coop.school_type), kits: rows,
        previous_year_count: mockCoopRows(MockOrders.kits, coop.id).filter(function (kit) { return kit.school_year === Number(params.school_year) - 1; }).length });
});

Mock.register('vendor/kits/get', function (params) {
    var kit = mockFindKit(params.id, params.cooperative_id);
    if (!kit) {
        return mockCoopError();
    }
    var data = mockKitSummary(kit);
    data.items = kit.items.map(function (item) {
        var product = Mock.findById(MockVendor.products, item.product_id);
        var variant = item.variant_id ? product.variants.filter(function (v) { return v.id === item.variant_id; })[0] : null;
        return { id: item.id, product_id: item.product_id, variant_id: item.variant_id, product_name: product.name, variant_label: variant ? variant.label : null,
            has_variants: product.has_variants, unit_price: product.price + (variant ? variant.price_delta : 0), qty: item.qty,
            is_mandatory: item.is_mandatory, note: item.note, sort_order: item.sort_order, is_active: product.is_active };
    });
    return Mock.ok(data);
});

// items = [{ product_id, variant_id (null = ibu bapa pilih varian), qty, is_mandatory, note }] mengikut susunan
Mock.register('vendor/kits/save', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var errors = Mock.requireFields(body, ['grade_level_code', 'school_year']);
    var items = body.items || [];
    if (items.length === 0) {
        errors.items = T('kit.items_required');
    }
    if (mockGradesFor(coop.school_type).indexOf(body.grade_level_code) === -1) {
        errors.grade_level_code = T('error.invalid');
    }
    var invalid = items.some(function (item) {
        var product = mockFindProduct(item.product_id, coop.id);
        return !product || !(item.qty > 0) || (item.variant_id && !mockFindVariant(product, item.variant_id));
    });
    if (invalid) {
        errors.items = T('error.invalid');
    }
    var clash = mockCoopRows(MockOrders.kits, coop.id).some(function (kit) {
        return kit.grade_level_code === body.grade_level_code && kit.school_year === Number(body.school_year) && kit.id !== Number(body.id);
    });
    if (clash) {
        errors.grade_level_code = T('kit.duplicate');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(Object.keys(errors).length === 1 && errors.items ? errors.items : T('error.check_form'), errors);
    }

    var kit = body.id ? mockFindKit(body.id, coop.id) : null;
    if (body.id && !kit) {
        return mockCoopError();
    }
    if (!kit) {
        kit = { id: Mock.nextId(MockOrders.kits), vendor_id: MockVendor.sessionVendorId, cooperative_id: coop.id, is_published: false };
        MockOrders.kits.push(kit);
    }
    kit.grade_level_code = body.grade_level_code;
    kit.school_year = Number(body.school_year);
    kit.name = body.name || '';
    kit.note = body.note || '';
    kit.items = items.map(function (item, index) {
        return { id: kit.id * 100 + index + 1, product_id: Number(item.product_id), variant_id: item.variant_id ? Number(item.variant_id) : null,
            qty: item.qty, is_mandatory: Boolean(item.is_mandatory), note: item.note || '', sort_order: index + 1 };
    });
    if (body.publish !== undefined) {
        kit.is_published = Boolean(body.publish);
    }
    return Mock.ok(mockKitSummary(kit));
});

Mock.register('vendor/kits/set-published', function (body) {
    var kit = mockFindKit(body.id, body.cooperative_id);
    if (!kit) {
        return mockCoopError();
    }
    kit.is_published = Boolean(body.is_published);
    return Mock.ok(mockKitSummary(kit));
});

Mock.register('vendor/kits/delete', function (body) {
    var kit = mockFindKit(body.id, body.cooperative_id);
    if (!kit) {
        return mockCoopError();
    }
    if (kit.is_published) {
        return Mock.fail(T('kit.delete_published'));
    }
    MockOrders.kits = MockOrders.kits.filter(function (row) { return row.id !== kit.id; });
    return Mock.ok({ id: kit.id });
});

// Salin semua kit tahun lepas sebagai draf. Darjah yang sudah ada kit tahun ini dilangkau.
// Produk yang sudah tidak aktif dibuang dari salinan.
Mock.register('vendor/kits/copy-year', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var target = Number(body.school_year);
    var existing = mockCoopRows(MockOrders.kits, coop.id).filter(function (kit) { return kit.school_year === target; });
    var copied = 0;
    var skipped = 0;
    var removedItems = 0;
    mockCoopRows(MockOrders.kits, coop.id).filter(function (kit) { return kit.school_year === target - 1; }).forEach(function (kit) {
        if (existing.some(function (row) { return row.grade_level_code === kit.grade_level_code; })) {
            skipped++;
            return;
        }
        var id = Mock.nextId(MockOrders.kits);
        var items = kit.items.filter(function (item) {
            var keep = Mock.findById(MockVendor.products, item.product_id).is_active;
            if (!keep) {
                removedItems++;
            }
            return keep;
        });
        MockOrders.kits.push({ id: id, vendor_id: kit.vendor_id, cooperative_id: kit.cooperative_id, grade_level_code: kit.grade_level_code,
            school_year: target, name: kit.name, note: kit.note, is_published: false,
            items: items.map(function (item, index) { return Object.assign({}, item, { id: id * 100 + index + 1 }); }) });
        copied++;
    });
    return Mock.ok({ copied: copied, skipped: skipped, removed_items: removedItems });
});

// Pilihan produk untuk kit: produk bervarian boleh dipilih tanpa varian (ibu bapa pilih saiz)
Mock.register('vendor/kits/product-options', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var options = [];
    mockCoopRows(MockVendor.products, coop.id).filter(function (product) { return product.is_active; }).forEach(function (product) {
        if (product.has_variants) {
            options.push({ product_id: product.id, variant_id: null, name: product.name, variant_label: null, price: product.price, has_variants: true });
            product.variants.forEach(function (variant) {
                options.push({ product_id: product.id, variant_id: variant.id, name: product.name, variant_label: variant.label, price: product.price + variant.price_delta, has_variants: true });
            });
        } else {
            options.push({ product_id: product.id, variant_id: null, name: product.name, variant_label: null, price: product.price, has_variants: false });
        }
    });
    return Mock.ok(options);
});

// ---------- Pesanan online ----------

Mock.register('vendor/orders/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    // Jualan POS kaunter (channel 'pos') tidak disenaraikan di sini — lihat POS, rekod bayaran & laporan
    var online = mockCoopRows(MockOrders.orders, coop.id).filter(function (order) {
        return order.channel !== 'pos';
    });
    var rows = online.filter(function (order) {
        return mockOrderMatches(order, params);
    }).sort(function (a, b) {
        return a.created_at < b.created_at ? 1 : -1;
    }).map(mockOrderSummary);
    var data = Mock.paginate(rows, { page: params.page, per_page: params.per_page }, []);
    data.status_counts = {};
    online.forEach(function (order) {
        data.status_counts[order.status] = (data.status_counts[order.status] || 0) + 1;
    });
    return Mock.ok(data);
});

Mock.register('vendor/orders/get', function (params) {
    var order = mockFindOrder(params.id, params.cooperative_id);
    if (!order) {
        return mockCoopError();
    }
    return Mock.ok(mockOrderDetail(order));
});

// Peralihan status yang dibenarkan dari panel (ambil barang hanya melalui skrin serah)
var MOCK_STATUS_FLOW = {
    pending_payment: ['cancelled'],
    paid: ['preparing'],
    unpaid_cash: ['preparing', 'cancelled'],
    preparing: ['ready'],
    ready: ['preparing']
};

Mock.register('vendor/orders/set-status', function (body) {
    var order = mockFindOrder(body.id, body.cooperative_id);
    if (!order) {
        return mockCoopError();
    }
    var allowed = MOCK_STATUS_FLOW[order.status] || [];
    if (allowed.indexOf(body.status) === -1) {
        return Mock.fail(T('order.status_not_allowed'));
    }
    if (body.status === 'cancelled' && !String(body.note || '').trim()) {
        return Mock.fail(T('error.required'), { note: T('error.required') });
    }
    if (body.status === 'cancelled') {
        addonReleaseSlot(order);
    }
    mockLogStatus(order, body.status, body.note);
    return Mock.ok(mockOrderDetail(order));
});

// Tukar status beberapa pesanan sekali gus (cth. semua "Sedang disediakan" → "Sedia diambil")
Mock.register('vendor/orders/bulk-status', function (body) {
    var coop = mockRequireCoop(body);
    if (!coop) {
        return mockCoopError();
    }
    var changed = 0;
    var skipped = 0;
    (body.ids || []).forEach(function (id) {
        var order = mockFindOrder(id, coop.id);
        if (order && (MOCK_STATUS_FLOW[order.status] || []).indexOf(body.status) !== -1 && body.status !== 'cancelled') {
            mockLogStatus(order, body.status, '');
            changed++;
        } else {
            skipped++;
        }
    });
    return Mock.ok({ changed: changed, skipped: skipped });
});

// Senarai ambil (picking list) ikut kelas + jumlah keseluruhan per produk
Mock.register('vendor/orders/picking-list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var filters = Object.assign({ status: 'paid,unpaid_cash,preparing' }, params);
    var orders = mockCoopRows(MockOrders.orders, coop.id).filter(function (order) { return mockOrderMatches(order, filters); });
    var grades = mockGradesFor(coop.school_type);
    var groups = [];
    var totals = {};

    orders.forEach(function (order) {
        mockChildrenOf(order).forEach(function (child) {
            if ((params.grade && child.grade_level_code !== params.grade) || (params.class_name && child.class_name !== params.class_name)) {
                return;
            }
            var group = groups.filter(function (g) { return g.grade_level_code === child.grade_level_code && g.class_name === child.class_name; })[0];
            if (!group) {
                group = { grade_level_code: child.grade_level_code, class_name: child.class_name, children: [] };
                groups.push(group);
            }
            group.children.push({ child_name: child.child_name, order_no: order.order_no, short_code: order.short_code, status: order.status,
                items: child.items.map(function (item) {
                    var key = item.product_id + ':' + item.variant_id;
                    totals[key] = totals[key] || { product_name: item.product_name, variant_label: item.variant_label, sku: item.sku, qty: 0 };
                    totals[key].qty += mockItemRemaining(item);
                    return { product_name: item.product_name, variant_label: item.variant_label, qty: mockItemRemaining(item) };
                }) });
        });
    });
    groups.sort(function (a, b) {
        return grades.indexOf(a.grade_level_code) - grades.indexOf(b.grade_level_code) || a.class_name.localeCompare(b.class_name);
    });
    groups.forEach(function (group) {
        group.children.sort(function (a, b) { return a.child_name.localeCompare(b.child_name); });
    });
    return Mock.ok({ school_name: coop.school_name, generated_at: Mock.now(), order_count: orders.length, groups: groups,
        totals: Object.keys(totals).map(function (key) { return totals[key]; }).sort(function (a, b) { return a.product_name.localeCompare(b.product_name); }) });
});

// Label beg: satu label setiap anak setiap pesanan. qr_token hanya untuk dicetak pada label/slip
Mock.register('vendor/orders/labels', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var filters = Object.assign({ status: 'paid,unpaid_cash,preparing,ready' }, params);
    var orders = params.order_id
        ? [mockFindOrder(params.order_id, coop.id)].filter(Boolean)
        : mockCoopRows(MockOrders.orders, coop.id).filter(function (order) { return mockOrderMatches(order, filters); });
    var labels = [];
    orders.forEach(function (order) {
        mockChildrenOf(order).forEach(function (child) {
            if ((params.grade && child.grade_level_code !== params.grade) || (params.class_name && child.class_name !== params.class_name)) {
                return;
            }
            labels.push({ order_id: order.id, order_no: order.order_no, short_code: order.short_code, child_name: child.child_name,
                grade_level_code: child.grade_level_code, class_name: child.class_name,
                item_count: child.items.reduce(function (sum, item) { return sum + item.qty; }, 0),
                payment_status: order.payment_status, pickup_slot: addonOrderSlot(order), qr_token: order.pickup_qr_token });
        });
    });
    return Mock.ok({ school_name: coop.school_name, labels: labels });
});

// Senarai kelas sebenar koperasi (untuk tapisan)
Mock.register('vendor/orders/class-options', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    return Mock.ok(mockCoopRows(MockVendor.classes, coop.id).map(function (row) {
        return { grade_level_code: row.grade_level_code, class_name: row.class_name };
    }));
});

// ---------- Serah barang (PELAN 7) ----------

// Cari ikut nama murid / nama ibu bapa / no. pendek / no. pesanan / token QR. Hanya pesanan yang aktif
Mock.register('vendor/handover/search', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var q = String(params.q || '').trim();
    if (q === '' && !params.grade && !params.class_name) {
        return Mock.ok({ items: [], settings: MockOrders.pickupSettings[coop.id] });
    }
    var exact = mockCoopRows(MockOrders.orders, coop.id).filter(function (order) {
        return order.pickup_qr_token.toLowerCase() === q.toLowerCase() || order.short_code.toLowerCase() === q.toLowerCase();
    });
    var rows = exact.length ? exact : mockCoopRows(MockOrders.orders, coop.id).filter(function (order) {
        return ['paid', 'unpaid_cash', 'preparing', 'ready', 'partially_collected'].indexOf(order.status) !== -1 && mockOrderMatches(order, params);
    });
    rows.sort(function (a, b) {
        var rank = { ready: 0, partially_collected: 1, preparing: 2, paid: 3, unpaid_cash: 3 };
        return (rank[a.status] === undefined ? 9 : rank[a.status]) - (rank[b.status] === undefined ? 9 : rank[b.status]);
    });
    return Mock.ok({ items: rows.slice(0, 20).map(mockOrderDetail), settings: MockOrders.pickupSettings[coop.id] });
});

// Terima tunai sebelum serah. Jumlah diterima ≥ jumlah pesanan → baki dipulangkan
Mock.register('vendor/handover/receive-cash', function (body) {
    var order = mockFindOrder(body.id, body.cooperative_id);
    if (!order) {
        return mockCoopError();
    }
    if (order.payment_status === 'paid') {
        return Mock.fail(T('handover.already_paid'));
    }
    if (order.payment_method !== 'cash') {
        return Mock.fail(T('error.invalid'));
    }
    if (!(body.amount_received >= order.total)) {
        return Mock.fail(T('handover.cash_not_enough'), { amount_received: T('handover.cash_not_enough') });
    }
    order.payment_status = 'paid';
    order.paid_at = Mock.now();
    order.logs.push({ from_status: order.status, to_status: order.status, actor: 'vendor_user', actor_name: MockVendor.sessionUserName,
        note: T('handover.cash_log', { amount: formatRM(order.total) }), created_at: Mock.now() });
    return Mock.ok({ change: body.amount_received - order.total, order: mockOrderDetail(order) });
});

// Serah: items = [{ item_id, qty }]; kod 4 digit jika wajib (5 cubaan salah → dikunci, perlu pengurus)
Mock.register('vendor/handover/collect', function (body) {
    var order = mockFindOrder(body.id, body.cooperative_id);
    if (!order) {
        return mockCoopError();
    }
    if (['ready', 'partially_collected'].indexOf(order.status) === -1) {
        return Mock.fail(T('handover.not_ready'));
    }
    if (order.payment_status !== 'paid') {
        return Mock.fail(T('handover.pay_first'));
    }
    if (order.pickup_locked) {
        return Mock.fail(T('handover.locked'));
    }
    var errors = Mock.requireFields(body, ['picked_by_type', 'picked_by_name']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    if (mockRequiresCode(order) && body.code !== order.pickup_code) {
        order.pickup_attempts += 1;
        if (order.pickup_attempts >= 5) {
            order.pickup_locked = true;
            return Mock.fail(T('handover.locked'), { code: T('handover.locked') });
        }
        var message = T('handover.code_wrong', { left: 5 - order.pickup_attempts });
        return Mock.fail(message, { code: message });
    }
    var lines = (body.items || []).filter(function (line) { return line.qty > 0; });
    if (lines.length === 0) {
        return Mock.fail(T('handover.no_items'));
    }
    var invalid = lines.some(function (line) {
        var item = order.items.filter(function (i) { return i.id === Number(line.item_id); })[0];
        return !item || line.qty > mockItemRemaining(item);
    });
    if (invalid) {
        return Mock.fail(T('handover.too_many'));
    }

    lines.forEach(function (line) {
        var item = order.items.filter(function (i) { return i.id === Number(line.item_id); })[0];
        item.collected_qty += line.qty;
        var level = mockStockLevel(order.cooperative_id, item.product_id, item.variant_id);
        if (level) {
            level.qty_reserved = Math.max(0, level.qty_reserved - line.qty);
            mockMoveStock(level, 'collect', -line.qty, 'order', order.id, order.short_code);
        }
    });
    var settings = MockOrders.pickupSettings[order.cooperative_id];
    MockOrders.pickups.push({ id: Mock.nextId(MockOrders.pickups), vendor_id: order.vendor_id, order_id: order.id,
        method: mockRequiresCode(order) ? 'E' : (body.method && settings.methods.indexOf(body.method) !== -1 ? body.method : 'A'),
        picked_by_type: body.picked_by_type, picked_by_name: body.picked_by_name, user_name: MockVendor.sessionUserName, created_at: Mock.now(),
        items: lines.map(function (line) { return { order_item_id: Number(line.item_id), qty: line.qty }; }) });

    var complete = order.items.every(function (item) { return mockItemRemaining(item) <= 0; });
    mockLogStatus(order, complete ? 'collected' : 'partially_collected', body.picked_by_name);
    if (complete) {
        order.collected_at = Mock.now();
    }
    // Peringkat 2: e-mel/notifikasi "telah diambil" dimasukkan ke email_queue
    return Mock.ok({ status: order.status, parent_notified: true, order: mockOrderDetail(order) });
});

// ---------- Ibu bapa & murid (baca sahaja + nota) ----------

Mock.register('vendor/customers/list', function (params) {
    var coop = mockRequireCoop(params);
    if (!coop) {
        return mockCoopError();
    }
    var q = String(params.q || '').trim().toLowerCase();
    var rows = MockOrders.customers.map(function (customer) {
        var kids = MockOrders.children.filter(function (child) {
            return child.customer_id === customer.id && child.cooperative_id === coop.id && child.is_active !== false
                && (!params.grade || child.grade_level_code === params.grade) && (!params.class_name || child.class_name === params.class_name);
        });
        if (kids.length === 0) {
            return null;
        }
        var orders = mockCoopRows(MockOrders.orders, coop.id).filter(function (order) {
            return order.customer_id === customer.id && ['cancelled', 'expired'].indexOf(order.status) === -1;
        });
        var note = MockOrders.customerNotes.filter(function (row) {
            return row.vendor_id === MockVendor.sessionVendorId && row.customer_id === customer.id;
        })[0];
        return {
            id: customer.id, full_name: customer.full_name, phone: customer.phone, email: customer.email, relationship: customer.relationship,
            children: kids.map(function (child) { return { id: child.id, full_name: child.full_name, grade_level_code: child.grade_level_code, class_name: child.class_name }; }),
            order_count: orders.length, total_spent: orders.reduce(function (sum, order) { return sum + order.total; }, 0),
            last_order_at: orders.length ? orders.map(function (order) { return order.created_at; }).sort().pop() : null,
            note: note ? note.note : ''
        };
    }).filter(function (row) {
        return row && (!q || [row.full_name, row.phone, row.email].concat(row.children.map(function (c) { return c.full_name; }))
            .some(function (text) { return text.toLowerCase().indexOf(q) !== -1; }));
    });
    var data = Mock.paginate(rows, { page: params.page, per_page: params.per_page }, []);
    data.child_count = rows.reduce(function (sum, row) { return sum + row.children.length; }, 0);
    return Mock.ok(data);
});

// Nota vendor tentang ibu bapa — hanya untuk ibu bapa yang ada anak di koperasi vendor ini
Mock.register('vendor/customers/save-note', function (body) {
    var coop = mockRequireCoop(body);
    var linked = coop && MockOrders.children.some(function (child) {
        return child.customer_id === Number(body.customer_id) && child.cooperative_id === coop.id;
    });
    if (!linked) {
        return mockCoopError();
    }
    var row = MockOrders.customerNotes.filter(function (item) {
        return item.vendor_id === MockVendor.sessionVendorId && item.customer_id === Number(body.customer_id);
    })[0];
    if (!row) {
        row = { vendor_id: MockVendor.sessionVendorId, customer_id: Number(body.customer_id) };
        MockOrders.customerNotes.push(row);
    }
    row.note = String(body.note || '').trim();
    return Mock.ok(row);
});

// ---------- Papan pemuka: kiraan sebenar dari pesanan ----------
(function syncDashboardCounts() {
    [1, 2, 3].forEach(function (coopId) {
        var orders = MockOrders.orders.filter(function (order) { return order.cooperative_id === coopId; });
        MockVendor.dashboard[coopId].orders_to_prepare = orders.filter(function (o) { return ['paid', 'unpaid_cash', 'preparing'].indexOf(o.status) !== -1; }).length;
        MockVendor.dashboard[coopId].orders_ready = orders.filter(function (o) { return ['ready', 'partially_collected'].indexOf(o.status) !== -1; }).length;
    });
})();

// Medan akaun ibu bapa & anak yang digunakan oleh store (U5)
MockOrders.customers.forEach(function (customer) {
    customer.language = customer.language || 'ms';
    customer.email_verified = true;
    customer.mock_password = 'rahsia123'; // Peringkat 1 sahaja — B2: password_hash Argon2id
    customer.is_active = true;
    customer.deleted_at = null;
});
MockOrders.children.forEach(function (child) {
    child.vendor_id = child.cooperative_id === 3 ? 2 : 1;
    child.school_year = 2026;
    child.is_active = true;
});

Mock.persist('vendor-orders', MockOrders, ['kits', 'orders', 'pickups', 'customerNotes', 'customers', 'children']);
