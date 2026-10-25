// Tetapan koperasi semasa

var settingsForm = document.getElementById('settings-form');

function loadSettings(coop) {
    document.getElementById('settings-coop').textContent = T('common.current_cooperative', { name: coop.school_name });
    Api.get('vendor/settings/get', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        fillForm(settingsForm, res.data);
        updateReceiptPreview();
    });
}

function updateReceiptPreview() {
    var coop = currentCooperative();
    document.getElementById('rp-header').textContent = document.getElementById('set-header').value || (coop ? coop.school_name : '');
    document.getElementById('rp-school').textContent = coop ? coop.school_name : '';
    document.getElementById('rp-footer').textContent = document.getElementById('set-footer').value;
}

['set-header', 'set-footer'].forEach(function (id) {
    document.getElementById(id).addEventListener('input', updateReceiptPreview);
});

bindForm(settingsForm, 'vendor/settings/save', function (data) {
    fillForm(settingsForm, data);
    updateReceiptPreview();
    showToast(T('common.saved'), 'success');
}, function (payload) {
    payload.pickup_code_required_above = payload.pickup_code_required_above || 0;
    return withCoop(payload);
});

onCooperativeReady(loadSettings);
