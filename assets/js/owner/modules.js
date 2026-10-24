// Katalog modul add-on

var moduleForm = document.getElementById('module-form');
var LANGUAGES = ['ms', 'en', 'zh', 'ta'];

function loadModules() {
    tableLoading('modules-body', 7);
    Api.get('owner/modules/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('modules-body', res.data, [
            { render: function (row) { return createStack(localName(row.names), row.code); } },
            { className: 'text-end', render: function (row) { return formatRM(row.price_monthly); } },
            { className: 'text-end', render: function (row) { return formatRM(row.price_yearly); } },
            { key: 'cooperative_count', className: 'text-center' },
            { render: function (row) { return translationBadges(row); } },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end', render: function (row) {
                return createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                    openModuleForm(row);
                }, T('btn.edit'));
            } }
        ]);
    });
}

// Nama dalam bahasa semasa, jatuh balik ke Melayu
function localName(names) {
    return names[currentLang()] || names.ms;
}

// Lencana bahasa: hijau = nama & keterangan lengkap, kuning = belum lengkap
function translationBadges(module) {
    var wrap = createEl('div', 'd-flex gap-1');
    LANGUAGES.forEach(function (code) {
        var complete = Boolean(module.names[code]) && Boolean(module.descriptions[code]);
        var badge = createEl('span', 'badge text-uppercase ' + (complete ? 'text-bg-success' : 'text-bg-warning'), code);
        badge.title = complete ? T('translation.complete') : T('translation.incomplete');
        wrap.appendChild(badge);
    });
    return wrap;
}

function openModuleForm(module) {
    document.getElementById('module-modal-title').textContent = module ? T('module.edit') : T('module.add');
    fillForm(moduleForm, module || { is_active: true, sort_order: 0 });
    // Kod modul = nama folder modules/{code}/ — tidak boleh ditukar selepas dicipta
    document.getElementById('module-code').readOnly = Boolean(module);
    bootstrap.Tab.getOrCreateInstance(document.getElementById('module-tab-ms')).show();
    showModal('module-modal');
}

document.getElementById('btn-add-module').addEventListener('click', function () {
    openModuleForm(null);
});

bindForm(moduleForm, 'owner/modules/save', function () {
    hideModal('module-modal');
    showToast(T('common.saved'), 'success');
    loadModules();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return payload;
});

loadModules();
