// Data mock panel vendor (Peringkat 1). Ditambah dalam U3a–U3c.
// Pengguna demo log masuk sebagai VENDOR01. VENDOR02 wujud untuk ujian kebocoran kelak.

var MockVendor = {
    vendors: [
        { id: 1, code: 'VENDOR01', company_name: 'Niaga Sekolah Sdn Bhd' },
        { id: 2, code: 'VENDOR02', company_name: 'Koperasi Bestari Enterprise' }
    ],

    cooperatives: [
        { id: 1, vendor_id: 1, slug: 'sk-taman-melati', school_name: 'SK Taman Melati', school_code: 'BBA1234', school_type: 'SK', state: 'Selangor', district: 'Gombak', status: 'active' },
        { id: 2, vendor_id: 1, slug: 'sjkc-chung-hwa', school_name: 'SJKC Chung Hwa', school_code: 'BBC5678', school_type: 'SJKC', state: 'Selangor', district: 'Petaling', status: 'active' },
        { id: 3, vendor_id: 2, slug: 'smk-bukit-indah', school_name: 'SMK Bukit Indah', school_code: 'JEA9012', school_type: 'SMK', state: 'Johor', district: 'Johor Bahru', status: 'active' }
    ],

    // Modul add-on aktif ikut cooperative_id
    activeModules: {
        1: ['news', 'school_opening'],
        2: [],
        3: ['clearance']
    },

    // vendor_id datang dari "sesi" — bukan dari parameter
    sessionVendorId: 1,

    ownCooperatives: function () {
        return MockVendor.cooperatives.filter(function (coop) {
            return coop.vendor_id === MockVendor.sessionVendorId;
        });
    },

    findOwnCooperative: function (id) {
        return MockVendor.ownCooperatives().filter(function (coop) {
            return coop.id === Number(id);
        })[0] || null;
    }
};

Mock.register('vendor/auth/login', function (body) {
    if (body.password === 'salah') {
        return Mock.fail(T('auth.invalid_credentials'));
    }
    return Mock.ok({ user: { id: 10, name: 'Encik Ahmad', role: 'vendor_admin' }, requires_2fa: false });
});

// Senarai koperasi vendor semasa — format senarai standard
Mock.register('vendor/cooperatives/list', function (params) {
    var data = Mock.paginate(MockVendor.ownCooperatives(), params, ['school_name', 'school_code', 'slug']);
    return Mock.ok(data);
});

// Pilih koperasi aktif (Peringkat 2: disimpan dalam sesi selepas ValidateCooperativeJob)
Mock.register('vendor/session/cooperative', function (body) {
    var coop = MockVendor.findOwnCooperative(body.cooperative_id);
    if (!coop) {
        return Mock.fail(T('error.not_found'));
    }
    return Mock.ok({ cooperative: coop });
});

// Kod modul add-on yang dilanggan oleh koperasi itu
Mock.register('vendor/modules/active', function (params) {
    var coop = MockVendor.findOwnCooperative(params.cooperative_id);
    if (!coop) {
        return Mock.fail(T('error.not_found'));
    }
    return Mock.ok({ cooperative_id: coop.id, modules: MockVendor.activeModules[coop.id] || [] });
});
