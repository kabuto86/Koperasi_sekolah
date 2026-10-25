// Kakitangan vendor (peringkat vendor — bukan ikut koperasi semasa)

var staffList = { page: 1, per_page: 20, q: '', role: '', status: '' };
var staffForm = document.getElementById('staff-form');
var ROLE_BADGES = { vendor_admin: 'text-bg-dark', coop_manager: 'text-bg-primary', cashier: 'text-bg-info' };

function loadStaff() {
    tableLoading('staff-body', 7);
    Api.get('vendor/staff/list', staffList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderLimit(res.data.limit);
        renderTable('staff-body', res.data.items, [
            { render: function (row) {
                var stack = createStack(row.full_name + (row.is_self ? ' ' + T('staff_v.you') : ''), row.email);
                if (row.phone) {
                    stack.appendChild(createEl('div', 'small text-body-secondary', row.phone));
                }
                return stack;
            } },
            { render: function (row) { return createEl('span', 'badge ' + ROLE_BADGES[row.role], T('role.' + row.role)); } },
            { className: 'small', render: function (row) {
                return row.role === 'vendor_admin' ? T('staff_v.all_coops') : row.cooperative_names.join(', ');
            } },
            { className: 'small', render: function (row) {
                var wrap = createEl('div', 'd-flex flex-column');
                wrap.appendChild(createEl('span', row.totp_enabled ? 'text-success' : 'text-body-secondary', T(row.totp_enabled ? 'twofa.enabled' : 'twofa.not_enabled')));
                if (row.role === 'cashier') {
                    wrap.appendChild(createEl('span', row.pin_set ? 'text-success' : 'text-body-secondary', T(row.pin_set ? 'staff_v.pin_set' : 'staff_v.pin_not_set')));
                }
                return wrap;
            } },
            { className: 'small text-nowrap', render: function (row) { return row.last_login_at ? formatDate(row.last_login_at, true) : '—'; } },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end text-nowrap', render: staffActions }
        ]);
        renderPagination('staff-pagination', res.data, function (page) {
            staffList.page = page;
            loadStaff();
        });
    });
}

function renderLimit(limit) {
    var text = limit.max === null ? T('staff_v.limit_unlimited', { used: limit.used }) : T('staff_v.limit_line', { used: limit.used, max: limit.max });
    document.getElementById('staff-limit-text').textContent = text;
    document.getElementById('staff-limit').classList.toggle('alert-warning', limit.max !== null && limit.used >= limit.max);
}

function staffActions(row) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
        openStaffForm(row);
    }, T('btn.edit')));
    wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-envelope', function () {
        sendReset(row);
    }, T('staff_v.send_reset')));
    if (!row.is_self) {
        wrap.appendChild(createButton('', row.is_active ? 'btn-outline-danger' : 'btn-outline-success', row.is_active ? 'bi-person-slash' : 'bi-person-check', function () {
            setActive(row, !row.is_active);
        }, T(row.is_active ? 'staff.deactivate' : 'staff.activate')));
    }
    return wrap;
}

function setActive(row, isActive) {
    var message = T(isActive ? 'staff.activate_confirm' : 'staff.deactivate_confirm', { name: row.full_name });
    confirmDialog(message, { okClass: isActive ? 'btn-success' : 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/staff/set-active', { id: row.id, is_active: isActive }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('common.saved'), 'success');
            loadStaff();
        });
    });
}

function sendReset(row) {
    confirmDialog(T('staff_v.send_reset_confirm', { email: row.email })).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/staff/send-reset', { id: row.id }).then(function (res) {
            showToast(res.ok ? T('staff_v.reset_sent', { email: res.data.email }) : res.error, res.ok ? 'success' : 'danger');
        });
    });
}

// ---------- Borang ----------

function renderCoopChecks() {
    var box = document.getElementById('st-coops');
    box.replaceChildren();
    VendorLayout.cooperatives.forEach(function (coop) {
        var wrap = createEl('div', 'form-check');
        var input = createEl('input', 'form-check-input');
        input.type = 'checkbox';
        input.id = 'st-coop-' + coop.id;
        input.name = 'cooperative_ids';
        input.value = coop.id;
        input.setAttribute('data-multi', '');
        input.setAttribute('data-type', 'int');
        var label = createEl('label', 'form-check-label', coop.school_name);
        label.htmlFor = input.id;
        wrap.appendChild(input);
        wrap.appendChild(label);
        box.appendChild(wrap);
    });
}

// Pentadbir: semua koperasi (tiada pilihan). Juruwang: PIN untuk POS
function updateRoleFields() {
    var role = (staffForm.querySelector('[name="role"]:checked') || {}).value;
    document.getElementById('st-coops-box').classList.toggle('d-none', role === 'vendor_admin');
    document.getElementById('st-pin-box').classList.toggle('d-none', role !== 'cashier');
}

function openStaffForm(row) {
    renderCoopChecks();
    document.getElementById('staff-title').textContent = row ? T('staff.edit') : T('staff.add');
    fillForm(staffForm, row || { role: 'cashier', cooperative_ids: currentCooperativeId() ? [currentCooperativeId()] : [] });
    document.getElementById('st-pin').placeholder = row && row.pin_set ? T('staff_v.pin_stored') : '';
    staffForm.querySelectorAll('[name="role"]').forEach(function (input) {
        input.disabled = Boolean(row && row.is_self);
    });
    updateRoleFields();
    showModal('staff-modal');
}

staffForm.querySelectorAll('[name="role"]').forEach(function (input) {
    input.addEventListener('change', updateRoleFields);
});

document.getElementById('btn-add-staff').addEventListener('click', function () {
    openStaffForm(null);
});

bindForm(staffForm, 'vendor/staff/save', function () {
    hideModal('staff-modal');
    showToast(T('common.saved'), 'success');
    loadStaff();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    // Peranan sendiri tidak boleh ditukar (radio dimatikan → tidak dihantar); pelayan tetap menyemak
    if (!payload.role) {
        payload.role = (staffForm.querySelector('[name="role"]:checked') || {}).value;
    }
    if (payload.role === 'vendor_admin') {
        payload.cooperative_ids = [];
    }
    if (payload.role !== 'cashier') {
        delete payload.pin;
    }
    return payload;
});

bindListFilters('staff-filters', staffList, loadStaff);
loadStaff();
