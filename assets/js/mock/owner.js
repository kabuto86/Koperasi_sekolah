// Data mock panel owner (Peringkat 1). Bentuk objek = kontrak API (PELAN 18).
// Sejak B12 hanya terjemahan (B14) kekal di sini — langganan & invois kini sebenar (api/v1/owner/subscriptions|invoices/).

var MockOwner = {
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

    // Log audit sebenar sejak B3 (audit_logs); tindakan terjemahan mock tidak direkod
    audit: function () {}
};

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
Mock.persist('owner', MockOwner, ['translations']);
