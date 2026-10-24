// Data mock store ibu bapa (Peringkat 1). Ditambah dalam U5.

var MockStore = {
    schools: [
        { id: 1, slug: 'sk-taman-melati', school_name: 'SK Taman Melati', school_type: 'SK', state: 'Selangor', district: 'Gombak', payment_methods: ['cash', 'online'], default_language: 'ms' },
        { id: 2, slug: 'sjkc-chung-hwa', school_name: 'SJKC Chung Hwa', school_type: 'SJKC', state: 'Selangor', district: 'Petaling', payment_methods: ['online'], default_language: 'zh' }
    ],

    cartItemCount: 3
};

// Maklumat awam sekolah ikut slug
Mock.register('store/school', function (params) {
    var school = MockStore.schools.filter(function (row) {
        return row.slug === params.slug;
    })[0];

    if (!school) {
        return Mock.fail(T('store.school_not_found'));
    }
    return Mock.ok(school);
});

Mock.register('store/cart/summary', function () {
    return Mock.ok({ item_count: MockStore.cartItemCount });
});
