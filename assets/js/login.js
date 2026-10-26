// Log masuk owner/vendor. Langkah 1: e-mel + kata laluan. Langkah 2: kod 2FA (owner wajib; vendor jika disediakan).
// Owner tanpa 2FA: QR + kunci manual dipapar di langkah 2; selepas disahkan, kod pemulihan dipapar sekali.
// Bergantung pada assets/lib/qrcode/qrcode.js (QR dijana dalam pelayar — rahsia tidak keluar)

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

function showSetup(setup) {
    var qr = qrcode(0, 'M');
    qr.addData(setup.otpauth_url);
    qr.make();
    document.getElementById('login-setup-qr').src = qr.createDataURL(5, 2);
    document.getElementById('login-setup-secret').value = setup.secret.replace(/(.{4})/g, '$1 ').trim();
    document.getElementById('login-setup').classList.remove('d-none');
    document.getElementById('login-otp-help').textContent = T('auth.otp_help');
}

function goToOtpStep(setup) {
    loginStep = 'otp';
    loginForm.classList.remove('was-validated');
    document.getElementById('login-step-password').classList.add('d-none');
    document.getElementById('login-step-otp').classList.remove('d-none');
    document.getElementById('login-otp').required = true;
    document.getElementById('login-submit-text').textContent = T('btn.confirm');
    if (setup) {
        showSetup(setup);
    }
    document.getElementById('login-otp').focus();
}

// Pelayan memilih halaman pertama yang dibenarkan untuk peranan pengguna
function finishLogin() {
    showToast(T('auth.login_success'), 'success');
    window.location.href = loginForm.getAttribute('data-success-url');
}

function showRecoveryCodes(codes) {
    var list = document.getElementById('login-recovery-list');
    list.replaceChildren();
    codes.forEach(function (code) {
        var col = createEl('div', 'col');
        col.appendChild(createEl('div', 'border rounded py-1', code));
        list.appendChild(col);
    });
    loginForm.classList.add('d-none');
    document.getElementById('login-recovery').classList.remove('d-none');
    document.getElementById('login-continue').focus();
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
            goToOtpStep(res.data.setup);
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
        if (res.data.recovery_codes) {
            showRecoveryCodes(res.data.recovery_codes);
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

document.getElementById('login-continue').addEventListener('click', finishLogin);
