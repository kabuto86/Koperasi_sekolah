// Jadual terjemahan: kunci × 4 bahasa, sunting terus dalam jadual

var LANGUAGES = ['ms', 'en', 'zh', 'ta'];
var translationList = { page: 1, per_page: 20, q: '', group: '', missing: '' };
var groupsLoaded = false;

function loadTranslations() {
    tableLoading('translations-body', LANGUAGES.length + 2);
    Api.get('owner/translations/list', translationList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        if (!groupsLoaded) {
            fillSelect('tr-group', res.data.groups.map(function (group) { return { group: group }; }), 'group', function (item) {
                return item.group;
            }, true);
            groupsLoaded = true;
        }
        renderMissingSummary(res.data.missing_counts);
        renderRows(res.data.items);
        renderPagination('translations-pagination', res.data, function (page) {
            translationList.page = page;
            loadTranslations();
        });
    });
}

function renderMissingSummary(counts) {
    var box = document.getElementById('missing-summary');
    box.replaceChildren(createEl('span', 'small text-body-secondary align-self-center', T('translation.missing_summary')));
    LANGUAGES.forEach(function (code) {
        var badge = createEl('span', 'badge ' + (counts[code] ? 'text-bg-warning' : 'text-bg-success'),
            T('lang.' + code) + ': ' + counts[code]);
        badge.setAttribute('lang', code);
        box.appendChild(badge);
    });
}

// Senarai {placeholder} dalam teks, disusun
function placeholdersOf(text) {
    return (String(text || '').match(/\{[a-z_]+\}/g) || []).sort().join(',');
}

function renderRows(items) {
    var body = document.getElementById('translations-body');
    if (items.length === 0) {
        // Baris "tiada data" merentas semua lajur (kunci + 4 bahasa + tindakan)
        renderTable(body, [], new Array(LANGUAGES.length + 2).fill({}));
        return;
    }
    body.replaceChildren();
    items.forEach(function (item) {
        body.appendChild(translationRow(item));
    });
}

function translationRow(item) {
    var row = createEl('tr');
    var keyCell = createEl('td');
    keyCell.appendChild(createEl('code', 'small text-break', item.key));
    var warning = createEl('div', 'small text-warning-emphasis d-none js-placeholder-warning');
    warning.appendChild(createIcon('bi-exclamation-triangle'));
    warning.appendChild(document.createTextNode(' ' + T('translation.placeholder_mismatch')));
    keyCell.appendChild(warning);
    row.appendChild(keyCell);

    var inputs = {};
    LANGUAGES.forEach(function (code) {
        var cell = createEl('td');
        var input = createEl('textarea', 'form-control form-control-sm');
        input.rows = 1;
        input.value = item.values[code] || '';
        input.setAttribute('lang', code);
        input.setAttribute('aria-label', item.key + ' — ' + T('lang.' + code));
        input.required = code === 'ms';
        input.addEventListener('input', function () {
            markRow(row, inputs, true);
        });
        inputs[code] = input;
        cell.appendChild(input);
        row.appendChild(cell);
    });

    var actionCell = createEl('td', 'text-end');
    var saveButton = createButton('', 'btn-brand js-save', 'bi-check-lg', function () {
        saveRow(item.key, row, inputs);
    }, T('btn.save'));
    saveButton.disabled = true;
    actionCell.appendChild(saveButton);
    row.appendChild(actionCell);

    markRow(row, inputs, false);
    return row;
}

// Serlahkan sel kosong & amaran placeholder tidak sama dengan Bahasa Melayu
function markRow(row, inputs, changed) {
    var reference = placeholdersOf(inputs.ms.value);
    var mismatch = false;
    LANGUAGES.forEach(function (code) {
        var value = inputs[code].value.trim();
        inputs[code].classList.toggle('app-missing', value === '');
        if (value !== '' && code !== 'ms' && placeholdersOf(value) !== reference) {
            mismatch = true;
        }
    });
    row.querySelector('.js-placeholder-warning').classList.toggle('d-none', !mismatch);
    if (changed) {
        row.querySelector('.js-save').disabled = false;
    }
}

function saveRow(key, row, inputs) {
    if (!inputs.ms.value.trim()) {
        inputs.ms.classList.add('is-invalid');
        showToast(T('translation.ms_required'), 'danger');
        return;
    }
    inputs.ms.classList.remove('is-invalid');

    var values = {};
    LANGUAGES.forEach(function (code) {
        values[code] = inputs[code].value.trim();
    });
    Api.post('owner/translations/save', { key: key, values: values }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        row.querySelector('.js-save').disabled = true;
        showToast(T('common.saved'), 'success');
    });
}

bindListFilters('translation-filters', translationList, loadTranslations);
loadTranslations();
