// Pendaftaran laluan mock (Peringkat 1 sahaja — dibuang dalam Peringkat 2).
// Fail mock/owner.js, vendor.js, store.js mendaftar laluan dengan Mock.register().

var Mock = {
    routes: {},

    // handler(payload, method) mesti memulangkan Mock.ok(...) atau Mock.fail(...)
    register: function (path, handler) {
        Mock.routes[path] = handler;
    },

    ok: function (data) {
        return { ok: true, data: data };
    },

    fail: function (message, errors) {
        return { ok: false, error: message, errors: errors || {} };
    },

    handle: function (method, path, payload) {
        var handler = Mock.routes[path];
        if (!handler) {
            return Mock.fail(T('error.not_found'));
        }

        // Salinan dalam supaya halaman tidak mengubah data mock secara tidak sengaja
        var response = handler(payload, method);
        return JSON.parse(JSON.stringify(response));
    },

    // Format senarai standard (PELAN 10.3 / 18):
    // { items, total, page, per_page }  — tapis q pada medan yang diberi
    paginate: function (rows, params, searchFields) {
        var page = Math.max(1, parseInt(params.page, 10) || 1);
        var perPage = Math.min(100, Math.max(1, parseInt(params.per_page, 10) || 20));
        var q = String(params.q || '').trim().toLowerCase();

        var filtered = rows.filter(function (row) {
            if (q === '') {
                return true;
            }
            return (searchFields || []).some(function (field) {
                return String(row[field] || '').toLowerCase().indexOf(q) !== -1;
            });
        });

        var start = (page - 1) * perPage;
        return {
            items: filtered.slice(start, start + perPage),
            total: filtered.length,
            page: page,
            per_page: perPage
        };
    }
};
