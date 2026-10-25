// Kit sekolah — grid darjah untuk tahun dipilih

var kitYearSelect = document.getElementById('kit-year');
var kitState = { previousCount: 0 };

function loadKits() {
    Api.get('vendor/kits/list', withCoop({ school_year: kitYearSelect.value })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        kitState.previousCount = res.data.previous_year_count;
        document.getElementById('btn-copy-year').disabled = res.data.previous_year_count === 0;
        var grid = document.getElementById('kit-grid');
        grid.replaceChildren();
        res.data.grades.forEach(function (code) {
            var kit = res.data.kits.filter(function (row) { return row.grade_level_code === code; })[0];
            grid.appendChild(kit ? kitCard(kit) : emptyKitCard(code));
        });
    });
}

function kitCard(kit) {
    var col = createEl('div', 'col-12 col-sm-6 col-xl-4');
    var card = createEl('div', 'card border-0 shadow-sm h-100');
    var body = createEl('div', 'card-body');
    var head = createEl('div', 'd-flex justify-content-between align-items-start mb-2');
    head.appendChild(createEl('h2', 'h6 mb-0', T('grade.' + kit.grade_level_code)));
    head.appendChild(createEl('span', 'badge ' + (kit.is_published ? 'text-bg-success' : 'text-bg-light border'),
        T(kit.is_published ? 'kit.published' : 'kit.draft')));
    body.appendChild(head);
    if (kit.name) {
        body.appendChild(createEl('div', 'small text-body-secondary mb-1', kit.name));
    }
    body.appendChild(createEl('div', 'small', T('kit.item_count', { n: kit.item_count })));
    body.appendChild(createEl('div', 'fw-semibold', formatRM(kit.total_price)));
    body.appendChild(createEl('div', 'small text-body-secondary', T('kit.mandatory_total', { amount: formatRM(kit.mandatory_price) })));
    card.appendChild(body);
    var footer = createEl('div', 'card-footer bg-body');
    footer.appendChild(createLink(T('btn.edit'), pageUrl('vendor/school-kit.php?id=' + kit.id), 'btn btn-sm btn-outline-secondary'));
    card.appendChild(footer);
    col.appendChild(card);
    return col;
}

function emptyKitCard(code) {
    var col = createEl('div', 'col-12 col-sm-6 col-xl-4');
    var card = createEl('div', 'card h-100 border-2 app-dashed-card');
    var body = createEl('div', 'card-body d-flex flex-column justify-content-center align-items-center text-center');
    body.appendChild(createEl('h2', 'h6', T('grade.' + code)));
    body.appendChild(createEl('div', 'small text-body-secondary mb-2', T('kit.none_yet')));
    body.appendChild(createLink(T('kit.create'), pageUrl('vendor/school-kit.php?grade=' + code + '&year=' + kitYearSelect.value), 'btn btn-sm btn-brand'));
    card.appendChild(body);
    col.appendChild(card);
    return col;
}

document.getElementById('btn-copy-year').addEventListener('click', function () {
    var year = Number(kitYearSelect.value);
    confirmDialog(T('kit.copy_confirm', { from: year - 1, to: year, n: kitState.previousCount }), { okText: T('kit.copy_previous') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/kits/copy-year', withCoop({ school_year: year })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('kit.copied', { copied: res.data.copied, skipped: res.data.skipped, removed: res.data.removed_items }), 'success');
            loadKits();
        });
    });
});

kitYearSelect.addEventListener('change', loadKits);
onCooperativeReady(loadKits);
