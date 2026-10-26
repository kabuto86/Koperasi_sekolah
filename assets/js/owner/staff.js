// Kakitangan owner

var staffForm = document.getElementById('staff-form');

function loadStaff() {
    tableLoading('staff-body', 6);
    Api.get('owner/staff/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('staff-body', res.data, [
            { render: function (row) { return createStack(row.full_name, row.email); } },
            { render: function (row) { return T('role.' + row.role); } },
            { render: function (row) {
                return createEl('span', 'badge ' + (row.totp_enabled ? 'text-bg-success' : 'text-bg-warning'),
                    T(row.totp_enabled ? 'twofa.enabled' : 'twofa.not_enabled'));
            } },
            { render: function (row) { return row.last_login_at ? formatDate(row.last_login_at, true) : '—'; } },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end text-nowrap', render: staffActions }
        ]);
    });
}

function staffActions(person) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
        openStaffForm(person);
    }, T('btn.edit')));
    // Kebenaran: owner_staff sahaja — owner sentiasa semua (PELAN 4.1)
    if (person.role === 'owner_staff') {
        wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-shield-lock', function () {
            PermissionEditor.open('owner', person, loadStaff);
        }, T('permission.title')));
    }
    if (person.totp_enabled) {
        wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-shield-x', function () {
            staffAction('owner/staff/reset-2fa', { id: person.id }, T('staff.reset_2fa_confirm', { name: person.full_name }), T('staff.reset_2fa'));
        }, T('staff.reset_2fa')));
    }
    var toActive = !person.is_active;
    wrap.appendChild(createButton('', toActive ? 'btn-outline-success' : 'btn-outline-danger', toActive ? 'bi-person-check' : 'bi-person-slash', function () {
        staffAction('owner/staff/set-active', { id: person.id, is_active: toActive },
            T(toActive ? 'staff.activate_confirm' : 'staff.deactivate_confirm', { name: person.full_name }),
            T(toActive ? 'staff.activate' : 'staff.deactivate'));
    }, T(toActive ? 'staff.activate' : 'staff.deactivate')));
    return wrap;
}

function staffAction(path, body, message, okText) {
    confirmDialog(message, { okText: okText, okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post(path, body).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('common.saved'), 'success');
            loadStaff();
        });
    });
}

function openStaffForm(person) {
    document.getElementById('staff-modal-title').textContent = person ? T('staff.edit') : T('staff.add');
    fillForm(staffForm, person || { role: 'owner_staff' });
    document.getElementById('staff-invite-note').classList.toggle('d-none', Boolean(person));
    showModal('staff-modal');
}

document.getElementById('btn-add-staff').addEventListener('click', function () {
    openStaffForm(null);
});

bindForm(staffForm, 'owner/staff/save', function () {
    hideModal('staff-modal');
    showToast(T('common.saved'), 'success');
    loadStaff();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return payload;
});

loadStaff();
