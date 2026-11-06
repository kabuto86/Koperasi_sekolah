// Profil kakitangan vendor semasa

var profileForm = document.getElementById('profile-form');
var passwordForm = document.getElementById('password-form');
var confirmInput = document.getElementById('pw-confirm');

function renderProfile(data) {
    fillForm(profileForm, data);
    document.getElementById('profile-company').textContent = data.company_name;
    document.getElementById('profile-role').textContent = T('role.' + data.role);
    var status = document.getElementById('profile-2fa-status');
    status.textContent = T(data.totp_enabled ? 'twofa.enabled' : 'twofa.not_enabled');
    status.className = 'small ' + (data.totp_enabled ? 'text-success' : 'text-body-secondary');
}

Api.get('vendor/profile/get').then(function (res) {
    if (!res.ok) {
        showToast(res.error, 'danger');
        return;
    }
    renderProfile(res.data);
});

bindForm(profileForm, 'vendor/profile/save', function (data) {
    renderProfile(data);
    showToast(T('common.saved'), 'success');
});

// Sahkan kata laluan sama sebelum dihantar (pengesahan sebenar di pelayan)
function checkPasswordMatch() {
    var same = confirmInput.value === document.getElementById('pw-new').value;
    confirmInput.setCustomValidity(same ? '' : T('profile.password_mismatch'));
}

document.getElementById('pw-new').addEventListener('input', checkPasswordMatch);
confirmInput.addEventListener('input', checkPasswordMatch);

bindForm(passwordForm, 'vendor/profile/change-password', function () {
    resetForm(passwordForm);
    showToast(T('profile.password_changed'), 'success');
}, function (payload) {
    delete payload.confirm_password;
    return payload;
});
