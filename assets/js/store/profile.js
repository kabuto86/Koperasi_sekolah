// Profil ibu bapa

function bindProfileForm() {
    var form = document.getElementById('profile-form');
    var before = null;
    storeGet('store/profile/get').then(function (res) {
        if (!res.ok) {
            return;
        }
        before = res.data;
        fillForm(form, res.data);
        document.getElementById('profile-verify').classList.toggle('d-none', res.data.email_verified);
    });
    bindForm(form, 'store/profile/save', function (data) {
        showToast(T('common.saved'));
        // Bahasa baharu → muat semula dalam bahasa itu
        if (data.language !== document.documentElement.lang) {
            storeGo('profile', { lang: data.language });
        }
        before = data;
    });
}

function bindPasswordForm() {
    var form = document.getElementById('password-form');
    form.password_confirm.addEventListener('input', function () {
        form.password_confirm.setCustomValidity(form.password_confirm.value === form.password.value ? '' : T('store.auth.password_mismatch'));
    });
    bindForm(form, 'store/profile/password', function () {
        resetForm(form);
        showToast(T('store.profile.password_changed'));
    });
}

function bindResend() {
    document.getElementById('profile-resend').addEventListener('click', function () {
        storePost('store/auth/resend-verification').then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            if (res.data.verified) {
                document.getElementById('profile-verify').classList.add('d-none');
                return;
            }
            showToast(T('store.verify.sent_text', { email: res.data.email || '' }));
        });
    });
}

function bindDeleteForm() {
    var form = document.getElementById('delete-form');
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!validateForm(form)) {
            return;
        }
        confirmDialog(T('store.profile.delete_final'), { okClass: 'btn-danger', okText: T('store.profile.delete_button') }).then(function (yes) {
            if (!yes) {
                return;
            }
            submitForm(form, 'store/profile/delete', function () {
                showToast(T('store.profile.deleted'));
                storeGo('home', null, '');
            });
        });
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    bindProfileForm();
    bindPasswordForm();
    bindResend();
    bindDeleteForm();
});
