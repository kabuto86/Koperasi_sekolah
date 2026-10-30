// Modul notifikasi WhatsApp/SMS — tetapan, templat & log mesej koperasi semasa (B13: sebenar — mesej dihantar oleh cron/send-messages.php)

var msgForm = document.getElementById('msg-form');
var msgLog = { page: 1, per_page: 10, status: '' };
var msgLastTemplate = null;

// Contoh nilai untuk pratonton templat
function sampleValues() {
    var coop = currentCooperative();
    return {
        parent_name: 'Rosnah binti Ali', child_name: 'Aisyah binti Rahman', short_code: 'A123',
        school_name: coop ? coop.school_name : '', picked_by: T('pickup.by.student'),
        time: formatDate(new Date().toISOString(), true), expires_on: formatDate(new Date(Date.now() + 3 * 86400000).toISOString())
    };
}

function renderPreviews() {
    var values = sampleValues();
    document.querySelectorAll('.js-msg-preview').forEach(function (span) {
        var text = document.getElementById('msg-tpl-' + span.getAttribute('data-event')).value;
        span.textContent = text.replace(/\{([a-z_]+)\}/g, function (tag, name) {
            return values[name] !== undefined ? values[name] : tag;
        });
    });
}

// Cip pemegang tempat: klik → masukkan ke templat terakhir difokus
function renderPlaceholders(tags) {
    var box = document.getElementById('msg-placeholders');
    box.replaceChildren();
    tags.forEach(function (tag) {
        var chip = createButton('{' + tag + '}', 'btn-outline-secondary font-monospace', null, function () {
            var area = msgLastTemplate || document.querySelector('.js-msg-template');
            var start = area.selectionStart || area.value.length;
            area.value = area.value.slice(0, start) + '{' + tag + '}' + area.value.slice(area.selectionEnd || start);
            area.focus();
            renderPreviews();
        });
        chip.title = T('messaging.insert_placeholder');
        box.appendChild(chip);
    });
}

function renderSettings(data) {
    fillForm(msgForm, data);
    document.getElementById('msg-key').value = '';
    document.getElementById('msg-key-help').textContent = data.api_key_set ? T('messaging.key_set') : T('messaging.key_not_set');
    renderPlaceholders(data.placeholders || []);
    renderPreviews();
}

function loadSettings() {
    Api.get('vendor/messaging/get', withCoop()).then(function (res) {
        if (moduleResponse(res)) {
            renderSettings(res.data);
        }
    });
}

function loadLog() {
    var list = document.getElementById('msg-log');
    Api.get('vendor/messaging/log', withCoop(msgLog)).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        list.replaceChildren();
        if (res.data.items.length === 0) {
            list.appendChild(createEl('li', 'list-group-item text-body-secondary', T('messaging.log_empty')));
        }
        res.data.items.forEach(function (row) {
            var item = createEl('li', 'list-group-item');
            var head = createEl('div', 'd-flex flex-wrap align-items-center gap-2 mb-1');
            head.appendChild(createIcon(row.channel === 'whatsapp' ? 'bi-whatsapp text-success' : 'bi-chat-left-text text-primary'));
            head.appendChild(createEl('span', 'font-monospace', row.recipient));
            head.appendChild(createEl('span', 'badge text-bg-light border', T('messaging.event.' + row.event)));
            head.appendChild(statusBadge(row.status, 'messaging.status'));
            head.appendChild(createEl('span', 'ms-auto text-body-secondary', formatDate(row.created_at, true)));
            item.appendChild(head);
            item.appendChild(createEl('div', 'text-body-secondary', row.message));
            if (row.status === 'failed') {
                item.appendChild(createEl('div', 'text-danger', T('messaging.attempts', { n: row.attempts })));
            }
            list.appendChild(item);
        });
        renderPagination('msg-log-pagination', res.data, function (page) {
            msgLog.page = page;
            loadLog();
        });
    });
}

document.querySelectorAll('.js-msg-template').forEach(function (area) {
    area.addEventListener('focus', function () {
        msgLastTemplate = area;
    });
    area.addEventListener('input', renderPreviews);
});

bindForm(msgForm, 'vendor/messaging/save', function (data) {
    showToast(T('common.saved'), 'success');
    data.placeholders = Array.prototype.map.call(document.querySelectorAll('#msg-placeholders button'), function (chip) {
        return chip.textContent.replace(/[{}]/g, '');
    });
    renderSettings(data);
}, function (payload) {
    return withCoop(payload);
});

bindForm('msg-test-form', 'vendor/messaging/test', function () {
    showToast(T('messaging.test_queued'), 'success');
    msgLog.page = 1;
    loadLog();
}, function (payload) {
    return withCoop(payload);
});

bindListFilters('msg-log-filters', msgLog, loadLog);

onModuleReady(function () {
    msgLog.page = 1;
    loadSettings();
    loadLog();
});
