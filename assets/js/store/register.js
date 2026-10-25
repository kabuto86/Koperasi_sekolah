// Daftar akaun ibu bapa → log masuk terus → pengesahan e-mel

var registerToken = null;

function showRegisterDone(data) {
    registerToken = data.demo_token;
    document.getElementById('register-card').classList.add('d-none');
    document.getElementById('register-done-text').textContent = T('store.verify.sent_text', { email: data.customer.email });
    document.getElementById('register-done').classList.remove('d-none');
    window.scrollTo(0, 0);
}

function bindVerifyDemo() {
    var button = document.getElementById('register-verify-demo');
    button.addEventListener('click', function () {
        Api.post('store/auth/verify-email', { token: registerToken }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            button.disabled = true;
            showToast(T('store.verify.done'));
        });
    });
}

onStoreReady(function (store) {
    if (store.customer) {
        storeGo('home');
        return;
    }
    var form = document.getElementById('register-form');
    bindForm(form, 'store/auth/register', showRegisterDone);
    // Sahkan kata laluan sama sebelum dihantar (pelayan semak semula)
    form.password_confirm.addEventListener('input', function () {
        form.password_confirm.setCustomValidity(form.password_confirm.value === form.password.value ? '' : T('store.auth.password_mismatch'));
    });
    bindVerifyDemo();
});
