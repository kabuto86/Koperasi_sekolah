// Log masuk owner/vendor. Langkah 1: e-mel + kata laluan. Langkah 2 (owner): kod 2FA.

var loginForm = document.getElementById('login-form');
var loginArea = loginForm.getAttribute('data-area');
var loginStep = 'password';

function setLoginBusy(busy) {
    document.getElementById('login-submit').disabled = busy;
    document.getElementById('login-spinner').classList.toggle('d-none', !busy);
}

function showLoginError(message) {
    var box = document.getElementById('login-error');
    box.textContent = message || '';
    box.classList.toggle('d-none', !message);
}

function goToOtpStep() {
    loginStep = 'otp';
    loginForm.classList.remove('was-validated');
    document.getElementById('login-step-password').classList.add('d-none');
    document.getElementById('login-step-otp').classList.remove('d-none');
    document.getElementById('login-otp').required = true;
    document.getElementById('login-submit-text').textContent = T('btn.confirm');
    document.getElementById('login-otp').focus();
}

function finishLogin() {
    showToast(T('auth.login_success'), 'success');
    window.location.href = loginForm.getAttribute('data-success-url');
}

function submitPassword() {
    var body = {
        email: document.getElementById('login-email').value.trim(),
        password: document.getElementById('login-password').value
    };

    return Api.post(loginArea + '/auth/login', body).then(function (res) {
        if (!res.ok) {
            showLoginError(res.error);
            return;
        }
        if (res.data.requires_2fa) {
            goToOtpStep();
            return;
        }
        finishLogin();
    });
}

function submitOtp() {
    var body = { code: document.getElementById('login-otp').value.trim() };

    return Api.post(loginArea + '/auth/verify-2fa', body).then(function (res) {
        if (!res.ok) {
            showLoginError(res.error);
            return;
        }
        finishLogin();
    });
}

loginForm.addEventListener('submit', function (event) {
    event.preventDefault();
    showLoginError('');

    if (!loginForm.checkValidity()) {
        loginForm.classList.add('was-validated');
        return;
    }

    setLoginBusy(true);
    var request = (loginStep === 'otp') ? submitOtp() : submitPassword();
    request.then(function () {
        setLoginBusy(false);
    });
});
