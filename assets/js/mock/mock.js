// Pendaftaran laluan mock (Peringkat 1 sahaja — dibuang dalam Peringkat 2).
// Fail mock/owner.js, vendor.js, store.js mendaftar laluan dengan Mock.register().

var Mock = {
    routes: {},
    stores: [],
    storageVersion: 'v9',

    // handler(payload, method) mesti memulangkan Mock.ok(...) atau Mock.fail(...)
    register: function (path, handler) {
        Mock.routes[path] = handler;
    },

    // Peralihan Peringkat 2 (B2+): fn(res) dipanggil selepas laluan SEBENAR (fetch) menjawab,
    // supaya data mock yang belum disambung boleh ikut sesi sebenar. Contoh: mock/store.js
    liveHooks: {},

    onLive: function (path, fn) {
        Mock.liveHooks[path] = fn;
    },

    afterLive: function (path, res) {
        if (Mock.liveHooks[path]) {
            Mock.liveHooks[path](res);
            Mock.saveStores();
        }
    },

    // fn() memulangkan Promise — dijalankan SEKALI sebelum jawapan mock pertama halaman ini
    // (cth. selaraskan sesi mock dengan sesi sebenar). Api.request menunggu Mock.ready()
    syncs: [],
    syncPromise: null,

    beforeFirst: function (fn) {
        Mock.syncs.push(fn);
    },

    ready: function () {
        if (!Mock.syncPromise) {
            Mock.syncPromise = Promise.all(Mock.syncs.map(function (fn) { return fn(); }));
        }
        return Mock.syncPromise;
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
        if (method === 'POST' && response.ok) {
            Mock.saveStores();
        }
        return JSON.parse(JSON.stringify(response));
    },

    // Simpan data mock dalam sessionStorage supaya perubahan kekal bila halaman bertukar
    // (seperti pangkalan data sebenar). Hilang bila tab ditutup. Peringkat 2: tiada.
    persist: function (name, object, keys) {
        Mock.stores.push({ name: name, object: object, keys: keys });
        try {
            var saved = sessionStorage.getItem('mock.' + Mock.storageVersion + '.' + name);
            if (saved) {
                var data = JSON.parse(saved);
                keys.forEach(function (key) {
                    if (data[key] !== undefined) {
                        object[key] = data[key];
                    }
                });
            }
        } catch (error) {
            // sessionStorage tidak tersedia — guna data asal
        }
    },

    saveStores: function () {
        Mock.stores.forEach(function (store) {
            var data = {};
            store.keys.forEach(function (key) {
                data[key] = store.object[key];
            });
            try {
                sessionStorage.setItem('mock.' + Mock.storageVersion + '.' + store.name, JSON.stringify(data));
            } catch (error) {
                // abaikan
            }
        });
    },

    // Kembali ke data contoh asal (cth. dari konsol: Mock.resetStores())
    resetStores: function () {
        Mock.stores.forEach(function (store) {
            try {
                sessionStorage.removeItem('mock.' + Mock.storageVersion + '.' + store.name);
            } catch (error) {
                // abaikan
            }
        });
    },

    findById: function (rows, id) {
        return rows.filter(function (row) {
            return row.id === Number(id);
        })[0] || null;
    },

    nextId: function (rows) {
        return rows.reduce(function (max, row) {
            return Math.max(max, row.id);
        }, 0) + 1;
    },

    // Masa semasa dalam format ISO 8601 zon Asia/Kuala_Lumpur
    now: function () {
        var local = new Date(Date.now() + 8 * 3600 * 1000).toISOString();
        return local.slice(0, 19) + '+08:00';
    },

    // Semak medan wajib → { medan: 'Ruangan ini wajib diisi.' }
    requireFields: function (body, fields) {
        var errors = {};
        fields.forEach(function (field) {
            var value = getPath(body, field);
            if (value === undefined || value === null || String(value).trim() === '') {
                errors[field] = T('error.required');
            }
        });
        return errors;
    },

    hasErrors: function (errors) {
        return Object.keys(errors).length > 0;
    },

    // Tapis baris di mana row[field] === params[field] (jika params[field] diisi)
    filterBy: function (rows, params, fields) {
        return rows.filter(function (row) {
            return fields.every(function (field) {
                var wanted = params[field];
                return wanted === undefined || wanted === null || wanted === '' || String(row[field]) === String(wanted);
            });
        });
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
