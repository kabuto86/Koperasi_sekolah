// Tetapan platform

var settingsForm = document.getElementById('settings-form');

function renderSettings(settings) {
    fillForm(settingsForm, settings);
    document.getElementById('smtp-pass-help').textContent = settings.smtp_password_set ? T('settings.password_stored') : '';
}

Api.get('owner/settings/get').then(function (res) {
    if (!res.ok) {
        showToast(res.error, 'danger');
        return;
    }
    renderSettings(res.data);
});

bindForm(settingsForm, 'owner/settings/save', function (data) {
    renderSettings(data);
    showToast(T('common.saved'), 'success');
});

document.getElementById('btn-test-email').addEventListener('click', function () {
    var input = document.getElementById('test-email-to');
    if (!input.checkValidity() || !input.value) {
        input.classList.add('is-invalid');
        return;
    }
    input.classList.remove('is-invalid');
    Api.post('owner/settings/test-email', { to: input.value }).then(function (res) {
        showToast(res.ok ? T('settings.test_email_queued', { email: input.value }) : res.error, res.ok ? 'success' : 'danger');
    });
});
