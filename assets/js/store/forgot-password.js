// Lupa kata laluan: e-mel → pautan dengan token → kata laluan baharu

function showResetStep(token) {
    document.getElementById('forgot-step-email').classList.add('d-none');
    document.getElementById('forgot-step-reset').classList.remove('d-none');
    document.getElementById('reset-token').value = token;
    var form = document.getElementById('reset-form');
    form.password_confirm.addEventListener('input', function () {
        form.password_confirm.setCustomValidity(form.password_confirm.value === form.password.value ? '' : T('store.auth.password_mismatch'));
    });
    bindForm(form, 'store/auth/reset', function () {
        showToast(T('store.forgot.reset_done'));
        storeGo('login');
    });
}

function bindForgot() {
    bindForm('forgot-form', 'store/auth/forgot', function (data) {
        document.getElementById('forgot-sent').classList.remove('d-none');
        // Peringkat 1 sahaja: pautan dari "e-mel" dipapar terus untuk ujian
        if (data.demo_token) {
            document.getElementById('forgot-demo-link').href = storeUrl('forgot-password', { token: data.demo_token });
            document.getElementById('forgot-demo').classList.remove('d-none');
        }
    });
}

onStoreReady(function () {
    var token = queryParam('token');
    if (token) {
        showResetStep(token);
    } else {
        bindForgot();
    }
});
