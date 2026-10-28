// Data mock vendor bahagian 2 (U3b): pesanan, serah barang, ibu bapa & murid.
// Sejak B9: vendor/orders/*, vendor/handover/* & vendor/customers/* sebenar (api/v1/). Data di sini kekal untuk mock
// POS (B10), refund & laporan (B11) sehingga session itu.
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

    orders: [],
    pickups: []
};

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

function mockLogStatus(order, to, note, actorName) {
    order.logs.push({ from_status: order.status, to_status: to, actor: 'vendor_user', actor_name: actorName || MockVendor.sessionUserName, note: note || '', created_at: Mock.now() });
    order.status = to;
}

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

Mock.persist('vendor-orders', MockOrders, ['orders', 'pickups', 'customers', 'children']);
