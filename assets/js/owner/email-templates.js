// Templat e-mel

var LANGUAGES = ['ms', 'en', 'zh', 'ta'];
var emailForm = document.getElementById('email-form');
var emailState = { key: null, placeholders: [] };

// Nilai contoh untuk pratonton sahaja
var SAMPLE_VALUES = {
    '{order_no}': 'A123', '{school_name}': 'SK Taman Melati', '{customer_name}': 'Rosnah Ali', '{total}': 'RM 125.50',
    '{order_url}': '#', '{items_table}': '<ul><li>Buku latihan × 5</li><li>Baju sekolah (M) × 2</li></ul>',
    '{pickup_code}': '4821', '{child_name}': 'Aisyah', '{child_class}': '4 Bestari', '{opening_hours}': '7:30 pagi – 1:00 petang',
    '{collected_by}': 'Aisyah (4 Bestari)', '{collected_at}': '24/10/2026 10:15', '{invoice_no}': 'INV-2026-0048',
    '{vendor_name}': 'Niaga Sekolah Sdn Bhd', '{due_date}': '27/10/2026', '{pay_url}': '#', '{name}': 'Ahmad',
    '{reset_url}': '#', '{expires_minutes}': '30'
};

function loadTemplateList(selectKey) {
    Api.get('owner/email-templates/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var list = document.getElementById('template-list');
        list.replaceChildren();
        res.data.forEach(function (item) {
            list.appendChild(templateListItem(item));
        });
        if (selectKey || res.data.length) {
            selectTemplate(selectKey || res.data[0].template_key, selectedLanguage());
        }
    });
}

function templateListItem(item) {
    var button = createEl('button', 'list-group-item list-group-item-action');
    button.type = 'button';
    button.setAttribute('data-key', item.template_key);
    button.appendChild(createEl('div', 'fw-semibold', T('email_template.' + item.template_key)));
    var badges = createEl('div', 'd-flex gap-1 mt-1');
    LANGUAGES.forEach(function (code) {
        var exists = Boolean(item.languages[code]);
        var badge = createEl('span', 'badge text-uppercase ' + (exists ? 'text-bg-success' : 'text-bg-light border'), code);
        badge.title = exists ? T('translation.complete') : T('translation.incomplete');
        badges.appendChild(badge);
    });
    button.appendChild(badges);
    button.addEventListener('click', function () {
        selectTemplate(item.template_key, selectedLanguage());
    });
    return button;
}

function selectedLanguage() {
    var checked = emailForm.querySelector('[name="language_code"]:checked');
    return checked ? checked.value : 'ms';
}

function selectTemplate(key, languageCode) {
    emailState.key = key;
    document.querySelectorAll('#template-list [data-key]').forEach(function (button) {
        button.classList.toggle('active', button.getAttribute('data-key') === key);
    });
    Api.get('owner/email-templates/get', { template_key: key, language_code: languageCode }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        fillForm(emailForm, res.data);
        emailState.placeholders = res.data.placeholders;
        document.getElementById('editor-title').textContent = T('email_template.' + key);
        document.getElementById('email-missing').classList.toggle('d-none', res.data.updated_at !== null);
        renderPlaceholders();
    });
}

// Tekan cip → masukkan {placeholder} di kedudukan kursor dalam kandungan
function renderPlaceholders() {
    var box = document.getElementById('placeholder-list');
    box.replaceChildren();
    emailState.placeholders.forEach(function (placeholder) {
        box.appendChild(createButton(placeholder, 'btn-outline-secondary font-monospace', null, function () {
            insertAtCursor(document.getElementById('email-body'), placeholder);
        }));
    });
}

function insertAtCursor(textarea, text) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
}

function fillSamples(text) {
    return Object.keys(SAMPLE_VALUES).reduce(function (result, placeholder) {
        return result.split(placeholder).join(SAMPLE_VALUES[placeholder]);
    }, text);
}

document.getElementById('btn-preview').addEventListener('click', function () {
    document.getElementById('email-preview-title').textContent = fillSamples(document.getElementById('email-subject').value);
    // iframe sandbox tanpa kebenaran: tiada skrip, tiada akses ke halaman ini
    document.getElementById('email-preview-frame').srcdoc = fillSamples(document.getElementById('email-body').value);
    showModal('email-preview-modal');
});

emailForm.querySelectorAll('[name="language_code"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        if (emailState.key) {
            selectTemplate(emailState.key, radio.value);
        }
    });
});

bindForm(emailForm, 'owner/email-templates/save', function () {
    showToast(T('common.saved'), 'success');
    loadTemplateList(emailState.key);
});

loadTemplateList(null);
