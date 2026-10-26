// Daftar akaun ibu bapa → log masuk terus → pautan pengesahan dihantar ke e-mel (email_queue)

function showRegisterDone(data) {
    document.getElementById('register-card').classList.add('d-none');
    document.getElementById('register-done-text').textContent = T('store.verify.sent_text', { email: data.customer.email });
    document.getElementById('register-done').classList.remove('d-none');
    window.scrollTo(0, 0);
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
});
