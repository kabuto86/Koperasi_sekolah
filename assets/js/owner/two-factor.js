// Pengesahan dua faktor (TOTP). Bergantung pada assets/lib/qrcode/qrcode.js

function showSection(id, visible) {
    document.getElementById(id).classList.toggle('d-none', !visible);
}

function loadSetup(forceSetup) {
    Api.get('owner/two-factor/setup').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var showSetup = forceSetup || !res.data.enabled;
        showSection('twofa-enabled', !showSetup);
        showSection('twofa-setup', showSetup);
        if (showSetup) {
            renderQr(res.data.otpauth_url);
            document.getElementById('twofa-secret').value = res.data.secret.replace(/(.{4})/g, '$1 ').trim();
        }
    });
}

// QR dijana dalam pelayar sebagai data URL (tiada perkhidmatan luar — rahsia tidak keluar)
function renderQr(text) {
    var qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    document.getElementById('twofa-qr').src = qr.createDataURL(5, 2);
}

function showRecoveryCodes(codes) {
    var list = document.getElementById('recovery-list');
    list.replaceChildren();
    codes.forEach(function (code) {
        var col = createEl('div', 'col');
        col.appendChild(createEl('div', 'border rounded py-1', code));
        list.appendChild(col);
    });
    showSection('recovery-card', true);
}

bindForm('enable-form', 'owner/two-factor/enable', function (data) {
    showToast(T('twofa.enabled_toast'), 'success');
    showSection('twofa-setup', false);
    showSection('twofa-enabled', true);
    showRecoveryCodes(data.recovery_codes);
});

bindForm('regenerate-form', 'owner/two-factor/regenerate-codes', function (data) {
    resetForm(document.getElementById('regenerate-form'));
    showRecoveryCodes(data.recovery_codes);
});

document.getElementById('btn-new-device').addEventListener('click', function () {
    loadSetup(true);
});

document.getElementById('btn-print-codes').addEventListener('click', function () {
    window.print();
});

loadSetup(false);
