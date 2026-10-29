// Satu-satunya pintu data untuk semua halaman.
// Setiap panggilan memulangkan Promise { ok: true, data } atau { ok: false, error, errors } (tidak pernah reject).
//
// Peringkat 2 (sejak B2): laluan dalam Api.livePrefixes → fetch('/api/v1/{laluan}.php') sebenar.
// Laluan lain masih dari assets/js/mock/*.js sehingga session B masing-masing (lihat PELAN 18.14).
// Bila semua siap: config use_mock = false → mock tidak dimuat dan semua laluan guna fetch().
// Halaman TIDAK perlu ditulis semula.

var Api = {
    delayMs: 200,

    // Tambah awalan laluan di sini bila session B menyiapkan modul itu
    livePrefixes: [
        'owner/auth/', 'owner/profile/', 'owner/staff/', 'owner/two-factor/',
        'owner/dashboard/', 'owner/vendors/', 'owner/cooperatives/', 'owner/plans/', 'owner/modules/', 'owner/templates/',
        'owner/gateways/', 'owner/grades/', 'owner/email-templates/', 'owner/announcements/', 'owner/audit-logs/', 'owner/settings/',
        'vendor/auth/', 'vendor/session/', 'vendor/staff/',
        'vendor/cooperatives/', 'vendor/templates/', 'vendor/modules/', 'vendor/grades/', 'vendor/classes/', 'vendor/categories/',
        'vendor/products/', 'vendor/inventory/', 'vendor/suppliers/', 'vendor/purchase-orders/', 'vendor/stock-takes/', 'vendor/dashboard/',
        'vendor/kits/', 'vendor/payments/', 'vendor/orders/', 'vendor/handover/', 'vendor/customers/', 'vendor/settings/',
        'vendor/pos/', 'vendor/promotions/', 'vendor/refunds/', 'vendor/reports/',
        'store/auth/', 'store/profile/', 'store/session',
        'store/schools/', 'store/school', 'store/classes', 'store/children/', 'store/catalog', 'store/product', 'store/kit',
        'store/cart/', 'store/news/', 'store/clearance/', 'store/checkout/', 'store/orders/'
    ],

    // Api.get('vendor/cooperatives/list', { page: 1, q: 'melati' })
    get: function (path, params) {
        return Api.request('GET', path, params || {});
    },

    // Api.post('vendor/products/save', { name: '...' })
    post: function (path, body) {
        return Api.request('POST', path, body || {});
    },

    isLive: function (path) {
        if (typeof Mock === 'undefined') {
            return true;
        }
        return Api.livePrefixes.some(function (prefix) {
            return path.indexOf(prefix) === 0;
        });
    },

    request: function (method, path, payload) {
        if (Api.isLive(path)) {
            return Api.fetchJson(method, path, payload);
        }
        return new Promise(function (resolve) {
            setTimeout(function () {
                resolve(Mock.handle(method, path, payload));
            }, Api.delayMs);
        });
    },

    url: function (path, params) {
        var url = (document.body.getAttribute('data-base-url') || '') + '/api/v1/' + path + '.php';
        var query = new URLSearchParams();
        Object.keys(params || {}).forEach(function (key) {
            var value = params[key];
            if (value !== null && value !== undefined && value !== '') {
                query.set(key, value);
            }
        });
        var text = query.toString();
        return text ? url + '?' + text : url;
    },

    // POST dihantar sebagai JSON + token CSRF dari <body data-csrf> (PELAN 10.3)
    fetchJson: function (method, path, payload) {
        var options = { method: method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        var url = Api.url(path, method === 'GET' ? payload : null);
        if (method === 'POST') {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-Token'] = document.body.getAttribute('data-csrf') || '';
            options.body = JSON.stringify(payload);
        }
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () {
                return { ok: false, error: T('error.server'), errors: {} };
            });
        }).then(function (res) {
            Api.handleSessionExpired(res);
            return res;
        }).catch(function () {
            return { ok: false, error: T('error.network'), errors: {} };
        });
    },

    // Panel owner/vendor: sesi tamat → kembali ke halaman log masuk. Store mengendalikan auth_required sendiri
    handleSessionExpired: function (res) {
        var loginUrl = document.body.getAttribute('data-login-url');
        if (res && !res.ok && res.auth_required && loginUrl) {
            window.location.href = loginUrl;
        }
    }
};
