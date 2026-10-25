// Log masuk ibu bapa (e-mel atau no. telefon)

// Selepas log masuk: kembali ke halaman asal (nama halaman sahaja), ikut bahasa pilihan ibu bapa
var LOGIN_NEXT_PAGES = ['catalog', 'product', 'kit', 'cart', 'checkout', 'orders', 'order-detail', 'order-success', 'children', 'profile', 'news', 'clearance'];

function afterLogin(customer) {
    var next = queryParam('next');
    var params = {};
    if (customer.language && customer.language !== document.documentElement.lang) {
        params.lang = customer.language;
    }
    if (LOGIN_NEXT_PAGES.indexOf(next) === -1) {
        next = 'home';
    } else if (queryParam('next_id')) {
        params[next === 'order-success' ? 'order' : 'id'] = queryParam('next_id');
    }
    storeGo(next, params);
}

bindForm('login-form', 'store/auth/login', function (data) {
    showToast(T('auth.login_success'));
    afterLogin(data.customer);
});
document.getElementById('login-register-link').href = storeUrl('register', { next: queryParam('next') });
document.getElementById('login-id').focus();

onStoreReady(function (store) {
    if (store.customer) {
        afterLogin(store.customer);
    }
});
