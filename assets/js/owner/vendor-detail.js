// Butiran vendor

var vendorId = Number(queryParam('id'));
var vendorData = null;

function loadVendor() {
    Api.get('owner/vendors/get', { id: vendorId }).then(function (res) {
        if (!res.ok) {
            var errorBox = document.getElementById('vendor-error');
            errorBox.textContent = res.error;
            errorBox.classList.remove('d-none');
            document.getElementById('vendor-detail').classList.add('d-none');
            return;
        }
        vendorData = res.data;
        renderVendor(res.data);
    });
}

function setText(id, value) {
    document.getElementById(id).textContent = (value === null || value === undefined || value === '') ? '—' : value;
}

function renderVendor(vendor) {
    document.getElementById('page-title').textContent = vendor.company_name;
    document.getElementById('vendor-status').replaceChildren(statusBadge(vendor.status, 'vendor.status'));
    setText('vendor-code', vendor.code);
    setText('vendor-registration', vendor.registration_no);
    setText('vendor-contact', vendor.contact_name);
    setText('vendor-email', vendor.email);
    setText('vendor-phone', vendor.phone);
    setText('vendor-address', vendor.address);
    setText('vendor-created', formatDate(vendor.created_at));

    var sub = vendor.subscription;
    if (sub) {
        setText('sub-plan', sub.plan_name);
        setText('sub-cycle', T('billing.cycle.' + sub.billing_cycle));
        document.getElementById('sub-status').replaceChildren(statusBadge(sub.status, 'vendor.status'));
        setText('sub-period', formatDate(sub.current_period_start) + ' – ' + formatDate(sub.current_period_end));
    }

    // Gantung: bukan suspended/cancelled. Aktifkan: trial (lulus), past_due, suspended.
    // Butang hanya wujud untuk peranan owner
    var canSuspend = ['suspended', 'cancelled'].indexOf(vendor.status) === -1;
    var canActivate = ['trial', 'past_due', 'suspended'].indexOf(vendor.status) !== -1;
    toggleAction('btn-suspend', canSuspend);
    toggleAction('btn-activate', canActivate);

    renderTable('coops-body', vendor.cooperatives, [
        { render: function (row) { return createStack(row.school_name, row.school_type + ' · ' + row.school_code); } },
        { render: function (row) { return createStack(row.district, row.state); } },
        { key: 'template_name' },
        { render: function (row) { return moduleBadges(row.modules); } },
        { render: function (row) { return statusBadge(row.status, 'coop.status'); } }
    ]);

    renderTable('invoices-body', vendor.invoices, [
        { render: function (row) { return createLink(row.invoice_no, pageUrl('owner/invoice.php?id=' + row.id), 'fw-semibold'); } },
        { render: function (row) { return formatDate(row.period_start) + ' – ' + formatDate(row.period_end); } },
        { className: 'text-end', render: function (row) { return formatRM(row.total); } },
        { render: function (row) { return statusBadge(row.status, 'invoice.status'); } }
    ]);

    renderNotes(vendor.notes);
}

function toggleAction(id, show) {
    var button = document.getElementById(id);
    if (button) {
        button.classList.toggle('d-none', !show);
    }
}

function moduleBadges(codes) {
    var wrap = createEl('div', 'd-flex flex-wrap gap-1');
    if (codes.length === 0) {
        wrap.appendChild(createEl('span', 'text-body-secondary', '—'));
    }
    codes.forEach(function (code) {
        wrap.appendChild(createEl('span', 'badge text-bg-light border', T('module.' + code)));
    });
    return wrap;
}

function renderNotes(notes) {
    var list = document.getElementById('notes-list');
    list.replaceChildren();
    if (notes.length === 0) {
        list.appendChild(createEl('li', 'text-body-secondary small', T('common.no_data')));
    }
    notes.forEach(function (note) {
        var item = createEl('li', 'border-start border-3 ps-3 mb-3');
        item.appendChild(createEl('div', '', note.body));
        item.appendChild(createEl('div', 'small text-body-secondary', note.created_by + ' · ' + formatDate(note.created_at, true)));
        list.appendChild(item);
    });
}

function activateVendor() {
    confirmDialog(T('vendor.activate_confirm', { name: vendorData.company_name }), { okText: T('vendor.activate'), okClass: 'btn-success' })
        .then(function (confirmed) {
            if (!confirmed) {
                return;
            }
            Api.post('owner/vendors/set-status', { id: vendorId, status: 'active' }).then(function (res) {
                if (!res.ok) {
                    showToast(res.error, 'danger');
                    return;
                }
                showToast(T('vendor.activated'), 'success');
                loadVendor();
            });
        });
}

if (document.getElementById('btn-activate')) {
    document.getElementById('btn-activate').addEventListener('click', activateVendor);
    document.getElementById('btn-suspend').addEventListener('click', function () {
        resetForm(document.getElementById('suspend-form'));
        showModal('suspend-modal');
    });
}

bindForm('suspend-form', 'owner/vendors/set-status', function () {
    hideModal('suspend-modal');
    showToast(T('vendor.suspended'), 'warning');
    loadVendor();
}, function (payload) {
    payload.id = vendorId;
    payload.status = 'suspended';
    return payload;
});

bindForm('note-form', 'owner/vendors/add-note', function () {
    resetForm(document.getElementById('note-form'));
    showToast(T('common.saved'), 'success');
    loadVendor();
}, function (payload) {
    payload.id = vendorId;
    return payload;
});

loadVendor();
