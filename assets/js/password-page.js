// Lupa / tetapkan kata laluan panel (partials/password-page.php). Kawasan dari URL halaman (owner / vendor)

var passwordArea = window.location.pathname.indexOf('/vendor/') !== -1 ? 'vendor' : 'owner';
var forgotForm = document.getElementById('forgot-form');
var resetForm = document.getElementById('reset-form');

if (forgotForm) {
    bindForm(forgotForm, passwordArea + '/auth/forgot', function () {
        forgotForm.classList.add('d-none');
        document.getElementById('forgot-sent').classList.remove('d-none');
    });
}

if (resetForm) {
    // Sahkan kata laluan sama sebelum dihantar (pelayan semak semula)
    resetForm.password_confirm.addEventListener('input', function () {
        resetForm.password_confirm.setCustomValidity(resetForm.password_confirm.value === resetForm.password.value ? '' : T('profile.password_mismatch'));
    });
    bindForm(resetForm, passwordArea + '/auth/reset', function () {
        showToast(T('store.forgot.reset_done'), 'success');
        window.location.href = document.getElementById('password-login-link').href;
    });
}
