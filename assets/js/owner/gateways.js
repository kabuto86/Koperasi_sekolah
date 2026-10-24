// Gateway bayaran

var gatewayState = { drivers: [], owner: null };
var ownerForm = document.getElementById('owner-gateway-form');

function loadGateways() {
    Api.get('owner/gateways/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        gatewayState.drivers = res.data.drivers;
        gatewayState.owner = res.data.owner_gateway;
        renderDrivers();
        renderOwnerForm();
    });
}

function renderDrivers() {
    renderTable('drivers-body', gatewayState.drivers, [
        { render: function (row) { return createStack(row.name, row.code); } },
        { className: 'text-center', render: function (row) {
            return createIcon(row.supports_refund ? 'bi-check-circle-fill text-success' : 'bi-dash-circle text-body-secondary');
        } },
        { key: 'vendor_count', className: 'text-center' },
        { className: 'text-end', render: driverSwitch }
    ]);
}

function driverSwitch(driver) {
    var wrap = createEl('div', 'form-check form-switch d-inline-block mb-0');
    var input = createEl('input', 'form-check-input');
    input.type = 'checkbox';
    input.setAttribute('role', 'switch');
    input.checked = driver.allowed_for_vendors;
    input.setAttribute('aria-label', T('gateway.allowed') + ': ' + driver.name);
    input.addEventListener('change', function () {
        toggleDriver(driver, input);
    });
    wrap.appendChild(input);
    return wrap;
}

function toggleDriver(driver, input) {
    var allowed = input.checked;
    var ask = (!allowed && driver.vendor_count > 0)
        ? confirmDialog(T('gateway.disable_confirm', { name: driver.name, n: driver.vendor_count }), { okClass: 'btn-danger' })
        : Promise.resolve(true);

    ask.then(function (confirmed) {
        if (!confirmed) {
            input.checked = !allowed;
            return;
        }
        Api.post('owner/gateways/toggle', { code: driver.code, allowed: allowed }).then(function (res) {
            if (!res.ok) {
                input.checked = !allowed;
                showToast(res.error, 'danger');
                return;
            }
            driver.allowed_for_vendors = allowed;
            showToast(T('common.saved'), 'success');
        });
    });
}

function renderOwnerForm() {
    fillSelect('owner-driver', gatewayState.drivers, 'code', function (driver) { return driver.name; }, false);
    document.getElementById('owner-driver').value = gatewayState.owner.driver_code;
    document.getElementById('owner-sandbox').checked = gatewayState.owner.sandbox;
    renderCredentialFields();
}

// Medan kunci API ikut driver. Rahsia tidak pernah dipaparkan — kosong = kekal nilai lama
function renderCredentialFields() {
    var code = document.getElementById('owner-driver').value;
    var driver = gatewayState.drivers.filter(function (item) { return item.code === code; })[0];
    var sameDriver = code === gatewayState.owner.driver_code;
    var container = document.getElementById('owner-credentials');
    container.replaceChildren();

    driver.fields.forEach(function (field) {
        var id = 'cred-' + field.name;
        var group = createEl('div', 'mb-3');
        var label = createEl('label', 'form-label', T('gateway.field.' + field.name));
        label.htmlFor = id;
        group.appendChild(label);

        var input = createEl('input', 'form-control font-monospace');
        input.id = id;
        input.name = 'credentials.' + field.name;
        input.autocomplete = 'off';
        var isStored = sameDriver && gatewayState.owner.secrets_set.indexOf(field.name) !== -1;

        if (field.secret) {
            input.type = 'password';
            input.required = !isStored;
            input.placeholder = isStored ? T('gateway.secret_stored') : '';
        } else {
            input.type = 'text';
            input.required = true;
            input.value = sameDriver ? (gatewayState.owner.values[field.name] || '') : '';
        }
        group.appendChild(input);
        group.appendChild(createEl('div', 'invalid-feedback', T('error.required')));
        container.appendChild(group);
    });
}

document.getElementById('owner-driver').addEventListener('change', renderCredentialFields);

bindForm(ownerForm, 'owner/gateways/save-owner', function (data) {
    gatewayState.owner = data;
    ownerForm.classList.remove('was-validated');
    renderCredentialFields();
    showToast(T('common.saved'), 'success');
});

loadGateways();
