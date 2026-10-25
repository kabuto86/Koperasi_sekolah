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
    rememberSchool(school);
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
                storeShowError(results[1].error);
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

bindLogout();
loadStoreLayout();
