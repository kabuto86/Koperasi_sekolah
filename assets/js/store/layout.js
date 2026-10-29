// Layout store: muat sekolah (slug) + sesi ibu bapa, isi header, kemudian panggil halaman (onStoreReady)

var LAST_SCHOOL_KEY = 'store.last_school';

// Sekolah terakhir dilawati — kemudahan pelayar sahaja (bukan data penting)
function rememberSchool(school) {
    try {
        localStorage.setItem(LAST_SCHOOL_KEY, JSON.stringify({ slug: school.slug, school_name: school.school_name, state: school.state }));
    } catch (error) {
        // localStorage tidak tersedia — abaikan
    }
}

function lastSchool() {
    try {
        return JSON.parse(localStorage.getItem(LAST_SCHOOL_KEY) || 'null');
    } catch (error) {
        return null;
    }
}

function fillSchool(school) {
    document.querySelectorAll('[data-school-name]').forEach(function (el) {
        el.textContent = school.school_name;
    });
    document.querySelectorAll('[data-module]').forEach(function (el) {
        el.classList.toggle('d-none', school.modules.indexOf(el.getAttribute('data-module')) === -1);
    });
    var contact = document.querySelector('[data-school-contact]');
    if (contact) {
        contact.textContent = [school.school_name, school.address, school.phone].filter(Boolean).join(' · ');
        contact.classList.remove('d-none');
    }
    document.title = document.title.replace(T('app.name'), school.school_name);
    fillNotices(school.notices || {});
    rememberSchool(school);
}

// Banner modul add-on (jika template menyediakan #store-closure / #store-preorder)
function fillNotices(notices) {
    var closureBox = document.getElementById('store-closure');
    var closure = notices.closure;
    if (closureBox && closure) {
        closureBox.classList.remove('d-none');
        closureBox.classList.add(closure.is_today ? 'alert-danger' : 'alert-warning');
        document.getElementById('store-closure-title').textContent = closure.is_today
            ? T('store.closed.today_title', { title: closure.title })
            : T('store.closed.upcoming_title', { title: closure.title });
        document.getElementById('store-closure-text').textContent = closure.is_today
            ? T('store.closed.today_text', { date: formatDate(closure.reopens_on) })
            : T('store.closed.upcoming_text', { from: formatDate(closure.starts_on), to: formatDate(closure.ends_on) });
    }
    var preorderBox = document.getElementById('store-preorder');
    var preorder = notices.preorder;
    // Musim pre-order: dipapar di halaman utama & kit sahaja (checkout memapar pilihan slot)
    if (preorderBox && preorder && ['home', 'kit'].indexOf(Store.page) !== -1) {
        preorderBox.classList.remove('d-none');
        document.getElementById('store-preorder-title').textContent = T('store.preorder.title', { year: preorder.school_year });
        document.getElementById('store-preorder-text').textContent = [
            T('store.preorder.text', { closes: formatDate(preorder.closes_at), pickup: formatDate(preorder.pickup_starts_on) }),
            preorder.has_slots ? T('store.preorder.slot_note') : '',
            preorder.note
        ].filter(Boolean).join(' ');
    }
}

function fillAccount(session) {
    var loggedIn = Boolean(session.customer);
    document.querySelectorAll('[data-auth-guest]').forEach(function (el) {
        el.classList.toggle('d-none', loggedIn);
    });
    document.querySelectorAll('[data-auth-user]').forEach(function (el) {
        el.classList.toggle('d-none', !loggedIn);
    });
    document.querySelectorAll('[data-customer-name]').forEach(function (el) {
        el.textContent = loggedIn ? session.customer.full_name : '';
    });
    setCartCount(session.cart_count);
}

function bindLogout() {
    document.querySelectorAll('[data-logout]').forEach(function (button) {
        button.addEventListener('click', function () {
            Api.post('store/auth/logout').then(function () {
                storeGo('home');
            });
        });
    });
}

function loadStoreLayout() {
    var requests = [Api.get('store/session', { slug: Store.slug })];
    if (Store.slug) {
        requests.push(Api.get('store/school', { slug: Store.slug }));
    }
    Promise.all(requests).then(function (results) {
        Store.session = results[0].ok ? results[0].data : { customer: null, cart_count: 0, schools: [] };
        Store.customer = Store.session.customer;
        fillAccount(Store.session);

        if (Store.slug) {
            if (!results[1].ok) {
                storeShowError(results[1].error, results[1].school_closed);
                return;
            }
            Store.school = results[1].data;
            fillSchool(Store.school);
        }

        Store.isReady = true;
        Store.readyCallbacks.forEach(function (fn) {
            fn(Store);
        });
    });
}

// Pautan dari e-mel pengesahan: ?verify_token=... (token sekali guna, 24 jam).
// Disahkan dahulu, kemudian sesi dimuat supaya banner "e-mel belum disahkan" sudah betul
function handleVerifyLink() {
    var token = queryParam('verify_token');
    if (!token) {
        return Promise.resolve();
    }
    return Api.post('store/auth/verify-email', { token: token }).then(function (res) {
        showToast(res.ok ? T('store.verify.done') : res.error, res.ok ? 'success' : 'danger');
    });
}

bindLogout();
handleVerifyLink().then(loadStoreLayout);
