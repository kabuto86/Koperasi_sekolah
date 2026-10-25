// Bayaran: gateway koperasi + kaedah tunai/online

var paymentState = { drivers: [], gateway: null, stored: {} };
var gatewayForm = document.getElementById('gateway-form');
var methodsForm = document.getElementById('methods-form');
var driverSelect = document.getElementById('gw-driver');

function loadPaymentSettings() {
    Api.get('vendor/payments/settings', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        paymentState.drivers = res.data.drivers;
        paymentState.gateway = res.data.gateway;
        paymentState.stored = {};
        if (res.data.gateway) {
            paymentState.stored[res.data.gateway.gateway_code] = res.data.gateway;
        }
        fillForm(methodsForm, res.data);
        document.getElementById('callback-url').value = res.data.callback_url;
        renderGatewayForm();
    });
}

function renderGatewayForm() {
    resetForm(gatewayForm);
    fillSelect(driverSelect, paymentState.drivers, 'code', function (driver) {
        return driver.name + (driver.supports_refund ? ' — ' + T('payment.refund_api') : '');
    }, false);
    var gateway = paymentState.gateway;
    driverSelect.value = gateway ? gateway.gateway_code : (paymentState.drivers[0] ? paymentState.drivers[0].code : '');
    document.getElementById(gateway && gateway.mode === 'live' ? 'gw-mode-live' : 'gw-mode-sandbox').checked = true;
    renderGatewayStatus();
    renderCredentialFields();
}

function renderGatewayStatus() {
    var badge = document.getElementById('gateway-status');
    var gateway = paymentState.gateway;
    var driver = gateway ? findDriver(gateway.gateway_code) : null;
    badge.className = 'badge ' + (gateway ? (gateway.mode === 'live' ? 'text-bg-success' : 'text-bg-warning') : 'text-bg-light border');
    badge.textContent = gateway
        ? T('payment.active_gateway', { name: driver ? driver.name : gateway.gateway_code, mode: T('payment.mode.' + gateway.mode) })
        : T('payment.no_gateway');
}

function findDriver(code) {
    return paymentState.drivers.filter(function (driver) { return driver.code === code; })[0] || null;
}

// Medan ikut config_fields driver. Rahsia tidak pernah dipaparkan — kosong = kekal nilai lama
function renderCredentialFields() {
    var driver = findDriver(driverSelect.value);
    var container = document.getElementById('gw-credentials');
    container.replaceChildren();
    if (!driver) {
        return;
    }
    var stored = paymentState.stored[driver.code] || { values: {}, secrets_set: [] };

    driver.fields.forEach(function (field) {
        var id = 'gw-' + field.name;
        var group = createEl('div', 'mb-3');
        var label = createEl('label', 'form-label', T('gateway.field.' + field.name));
        label.htmlFor = id;
        group.appendChild(label);

        var input = createEl('input', 'form-control font-monospace');
        input.id = id;
        input.name = 'credentials.' + field.name;
        input.autocomplete = 'off';
        var isStored = stored.secrets_set.indexOf(field.name) !== -1;
        if (field.secret) {
            input.type = 'password';
            input.required = !isStored;
            input.placeholder = isStored ? T('gateway.secret_stored') : '';
        } else {
            input.type = 'text';
            input.required = true;
            input.value = stored.values[field.name] || '';
        }
        group.appendChild(input);
        group.appendChild(createEl('div', 'invalid-feedback', T('error.required')));
        container.appendChild(group);
    });
}

// Tukar driver: ambil nilai tersimpan driver itu (jika pernah diisi) supaya tidak perlu taip semula
driverSelect.addEventListener('change', function () {
    var code = driverSelect.value;
    if (paymentState.stored[code] !== undefined) {
        renderCredentialFields();
        return;
    }
    Api.get('vendor/payments/gateway', withCoop({ gateway_code: code })).then(function (res) {
        paymentState.stored[code] = res.ok ? res.data : null;
        renderCredentialFields();
    });
});

document.getElementById('btn-test-gateway').addEventListener('click', function () {
    Api.post('vendor/payments/test-gateway', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var driver = findDriver(res.data.gateway_code);
        showToast(T('payment.test_ok', { name: driver ? driver.name : res.data.gateway_code, mode: T('payment.mode.' + res.data.mode) }), 'success');
    });
});

// Tukar ke mod live → minta pengesahan (bayaran sebenar ibu bapa akan diterima)
function confirmLiveMode(payload) {
    var gateway = paymentState.gateway;
    var wasLive = gateway && gateway.mode === 'live' && gateway.gateway_code === payload.gateway_code;
    if (payload.mode !== 'live' || wasLive) {
        return Promise.resolve(true);
    }
    return confirmDialog(T('payment.live_confirm'), { okClass: 'btn-success' });
}

gatewayForm.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!validateForm(gatewayForm)) {
        return;
    }
    var payload = withCoop(formToObject(gatewayForm));
    confirmLiveMode(payload).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/payments/save-gateway', payload).then(function (res) {
            if (!res.ok) {
                showFormErrors(gatewayForm, res.errors);
                showToast(res.error, 'danger');
                return;
            }
            paymentState.gateway = res.data;
            paymentState.stored[res.data.gateway_code] = res.data;
            renderGatewayForm();
            showToast(T('common.saved'), 'success');
        });
    });
});

bindForm(methodsForm, 'vendor/payments/save-methods', function () {
    showToast(T('common.saved'), 'success');
}, withCoop);

onCooperativeReady(loadPaymentSettings);
