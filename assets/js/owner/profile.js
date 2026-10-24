// Profil owner semasa

var profileForm = document.getElementById('profile-form');
var passwordForm = document.getElementById('password-form');
var confirmInput = document.getElementById('pw-confirm');

Api.get('owner/profile/get').then(function (res) {
    if (!res.ok) {
        showToast(res.error, 'danger');
        return;
    }
    fillForm(profileForm, res.data);
    var status = document.getElementById('profile-2fa-status');
    status.textContent = T(res.data.totp_enabled ? 'twofa.enabled' : 'twofa.not_enabled');
    status.className = 'small ' + (res.data.totp_enabled ? 'text-success' : 'text-danger');
});

bindForm(profileForm, 'owner/profile/save', function () {
    showToast(T('common.saved'), 'success');
});

// Sahkan kata laluan sama sebelum dihantar (pengesahan sebenar di pelayan)
function checkPasswordMatch() {
    var same = confirmInput.value === document.getElementById('pw-new').value;
    confirmInput.setCustomValidity(same ? '' : T('profile.password_mismatch'));
}

document.getElementById('pw-new').addEventListener('input', checkPasswordMatch);
confirmInput.addEventListener('input', checkPasswordMatch);

bindForm(passwordForm, 'owner/profile/change-password', function () {
    resetForm(passwordForm);
    showToast(T('profile.password_changed'), 'success');
}, function (payload) {
    delete payload.confirm_password;
    return payload;
});
