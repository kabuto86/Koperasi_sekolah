// Modal kebenaran kakitangan (PELAN 4.1) — owner & vendor.
//   PermissionEditor.open('vendor', { id, full_name }, function () { ... })
// Kotak semak = kebenaran sebenar; lencana "Lalai" = termasuk dalam lalai peranan; had & pentadbir dinyahaktifkan.
// Pelayan menyimpan beza dengan lalai sahaja.

var PermissionEditor = {
    panel: '',
    userId: 0,
    onSaved: null,

    open: function (panel, person, onSaved) {
        PermissionEditor.panel = panel;
        PermissionEditor.userId = person.id;
        PermissionEditor.onSaved = onSaved;
        document.getElementById('permission-user').textContent = person.full_name;
        document.getElementById('permission-groups').replaceChildren();
        Api.get(panel + '/staff/permissions', { id: person.id }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            PermissionEditor.render(res.data);
            showModal('permission-modal');
        });
    },

    render: function (data) {
        var panel = PermissionEditor.panel;
        var container = document.getElementById('permission-groups');
        container.replaceChildren();
        document.getElementById('permission-user').textContent = data.user.full_name + ' · ' + T('role.' + data.user.role);

        data.groups.forEach(function (group) {
            var col = createEl('div', 'col-md-6');
            var fieldset = createEl('fieldset', 'border rounded p-3 h-100');
            fieldset.appendChild(createEl('legend', 'fs-6 fw-semibold float-none w-auto px-1 mb-2', T('permission.group.' + panel + '.' + group.key)));
            group.items.forEach(function (item) {
                fieldset.appendChild(PermissionEditor.checkbox(item, data.editable));
            });
            col.appendChild(fieldset);
            container.appendChild(col);
        });

        var readonly = document.getElementById('permission-readonly');
        readonly.textContent = data.editable ? '' : T(PermissionEditor.isFullRole(data.user.role) ? 'permission.full_role_note' : 'permission.self_note');
        readonly.classList.toggle('d-none', data.editable);
        document.getElementById('permission-save').classList.toggle('d-none', !data.editable);
        document.getElementById('permission-reset').classList.toggle('d-none', !data.editable);
    },

    checkbox: function (item, editable) {
        var id = 'perm-' + item.code.replace('.', '-');
        var wrap = createEl('div', 'form-check');
        var input = createEl('input', 'form-check-input');
        input.type = 'checkbox';
        input.id = id;
        input.value = item.code;
        input.checked = item.allowed;
        input.disabled = !editable || item.locked;
        var label = createEl('label', 'form-check-label', T('permission.' + PermissionEditor.panel + '.' + item.code));
        label.htmlFor = id;
        if (item.default) {
            label.appendChild(document.createTextNode(' '));
            label.appendChild(createEl('span', 'badge text-bg-light border', T('permission.default_badge')));
        }
        if (item.locked && editable) {
            label.appendChild(createEl('div', 'small text-body-secondary', T('permission.locked_note')));
        }
        wrap.appendChild(input);
        wrap.appendChild(label);
        return wrap;
    },

    isFullRole: function (role) {
        return role === 'vendor_admin' || role === 'owner';
    },

    selected: function () {
        return Array.prototype.slice.call(document.querySelectorAll('#permission-groups input:checked')).map(function (input) {
            return input.value;
        });
    },

    save: function (body) {
        body.id = PermissionEditor.userId;
        Api.post(PermissionEditor.panel + '/staff/save-permissions', body).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            hideModal('permission-modal');
            showToast(T('permission.saved'), 'success');
            if (PermissionEditor.onSaved) {
                PermissionEditor.onSaved();
            }
        });
    }
};

document.getElementById('permission-form').addEventListener('submit', function (event) {
    event.preventDefault();
    PermissionEditor.save({ permissions: PermissionEditor.selected() });
});

document.getElementById('permission-reset').addEventListener('click', function () {
    confirmDialog(T('permission.reset_confirm'), { okText: T('permission.reset') }).then(function (confirmed) {
        if (confirmed) {
            PermissionEditor.save({ reset: true });
        }
    });
});
