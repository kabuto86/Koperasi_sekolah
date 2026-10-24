// Satu-satunya pintu data untuk semua halaman.
// Setiap panggilan memulangkan Promise { ok: true, data } atau { ok: false, error, errors }.
//
// Peringkat 1 (sekarang): data dari assets/js/mock/*.js dengan kelewatan 200ms.
// Peringkat 2: hanya fungsi Api.request() ditukar kepada fetch('/api/v1/...').
//              Halaman TIDAK perlu ditulis semula.

var Api = {
    delayMs: 200,

    // Api.get('vendor/cooperatives/list', { page: 1, q: 'melati' })
    get: function (path, params) {
        return Api.request('GET', path, params || {});
    },

    // Api.post('vendor/products/save', { name: '...' })
    post: function (path, body) {
        return Api.request('POST', path, body || {});
    },

    request: function (method, path, payload) {
        return new Promise(function (resolve) {
            setTimeout(function () {
                resolve(Mock.handle(method, path, payload));
            }, Api.delayMs);
        });
    }
};
