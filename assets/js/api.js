// Satu-satunya pintu data untuk semua halaman.
// Setiap panggilan memulangkan Promise { ok: true, data } atau { ok: false, error, errors } (tidak pernah reject).
//
// Sejak B14 semua laluan sebenar: fetch('/api/v1/{laluan}.php'). Mock Peringkat 1 (assets/js/mock/) sudah dibuang.

var Api = {
    // Api.get('vendor/cooperatives/list', { page: 1, q: 'melati' })
    get: function (path, params) {
        return Api.fetchJson('GET', path, params || {});
    },

    // Api.post('vendor/products/save', { name: '...' })
    post: function (path, body) {
        return Api.fetchJson('POST', path, body || {});
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
