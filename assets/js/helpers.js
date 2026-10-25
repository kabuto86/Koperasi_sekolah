// Fungsi bantu umum untuk semua halaman.
// Bergantung pada: bootstrap.bundle.min.js, lang.js

// 1250 → "RM 12.50" (wang sentiasa dalam sen)
function formatRM(sen) {
    var amount = Number(sen || 0) / 100;
    var text = Math.abs(amount).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return (amount < 0 ? '-RM ' : 'RM ') + text;
}

// "2026-10-24T10:15:00+08:00" → "24/10/2026" atau "24/10/2026 10:15"
function formatDate(iso, withTime) {
    if (!iso) {
        return '';
    }

    var date = new Date(iso);
    if (isNaN(date.getTime())) {
        return '';
    }

    var options = { timeZone: 'Asia/Kuala_Lumpur', day: '2-digit', month: '2-digit', year: 'numeric' };
    if (withTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
        options.hour12 = false;
    }

    return new Intl.DateTimeFormat('en-GB', options).format(date).replace(',', '');
}

// Cipta elemen dengan kelas & teks (teks selamat — tiada innerHTML)
function createEl(tag, className, text) {
    var el = document.createElement(tag);
    if (className) {
        el.className = className;
    }
    if (text !== undefined && text !== null) {
        el.textContent = text;
    }
    return el;
}

// Mesej ringkas di penjuru skrin. type: success | danger | warning | info
function showToast(message, type) {
    var container = document.getElementById('app-toasts');
    if (!container) {
        return;
    }

    var toast = createEl('div', 'toast align-items-center border-0 text-bg-' + (type || 'success'));
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');

    var row = createEl('div', 'd-flex');
    row.appendChild(createEl('div', 'toast-body', message));

    var close = createEl('button', 'btn-close btn-close-white me-2 m-auto');
    close.type = 'button';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', T('btn.close'));
    row.appendChild(close);

    toast.appendChild(row);
    container.appendChild(toast);

    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).show();
}

// Dialog pengesahan. Memulangkan Promise<boolean>.
// options: { title, okText, okClass }  cth. okClass: 'btn-danger'
function confirmDialog(message, options) {
    options = options || {};

    var modalEl = document.getElementById('app-confirm');
    var okButton = document.getElementById('app-confirm-ok');
    document.getElementById('app-confirm-title').textContent = options.title || T('common.confirm_title');
    document.getElementById('app-confirm-message').textContent = message || T('common.confirm_default');
    okButton.textContent = options.okText || T('btn.confirm');
    okButton.className = 'btn ' + (options.okClass || 'btn-primary');

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    return new Promise(function (resolve) {
        var confirmed = false;

        function onOk() {
            confirmed = true;
            modal.hide();
        }

        function onHidden() {
            okButton.removeEventListener('click', onOk);
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            resolve(confirmed);
        }

        okButton.addEventListener('click', onOk);
        modalEl.addEventListener('hidden.bs.modal', onHidden);
        modal.show();
    });
}

// Isi <tbody> dengan baris.
// columns: [{ key: 'name', className: 'text-end', render: function (item) { return 'teks' atau Node; } }]
function renderTable(tbody, items, columns) {
    if (typeof tbody === 'string') {
        tbody = document.getElementById(tbody);
    }
    tbody.replaceChildren();

    if (!items || items.length === 0) {
        var emptyRow = createEl('tr');
        var emptyCell = createEl('td', 'text-center text-body-secondary py-4', T('common.no_data'));
        emptyCell.colSpan = columns.length;
        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);
        return;
    }

    items.forEach(function (item) {
        var row = createEl('tr');
        columns.forEach(function (column) {
            row.appendChild(renderTableCell(item, column));
        });
        tbody.appendChild(row);
    });
}

function renderTableCell(item, column) {
    var cell = createEl('td', column.className || '');
    var value = column.render ? column.render(item) : item[column.key];

    if (value instanceof Node) {
        cell.appendChild(value);
    } else if (value !== undefined && value !== null) {
        cell.textContent = String(value);
    }

    return cell;
}

// ---------- Lencana status ----------

// Warna lencana ikut kumpulan + status. Label dari T(kumpulan + '.' + status)
var BADGE_COLORS = {
    'order.status.pending_payment': 'text-bg-warning',
    'order.status.paid': 'text-bg-info',
    'order.status.unpaid_cash': 'text-bg-warning',
    'order.status.preparing': 'text-bg-primary',
    'order.status.ready': 'text-bg-success',
    'order.status.partially_collected': 'text-bg-secondary',
    'order.status.collected': 'text-bg-dark',
    'order.status.cancelled': 'text-bg-light',
    'order.status.expired': 'text-bg-light',
    'order.status.refunded': 'text-bg-danger',
    'vendor.status.trial': 'text-bg-info',
    'vendor.status.active': 'text-bg-success',
    'vendor.status.past_due': 'text-bg-warning',
    'vendor.status.suspended': 'text-bg-danger',
    'vendor.status.cancelled': 'text-bg-secondary',
    'invoice.status.draft': 'text-bg-light',
    'invoice.status.issued': 'text-bg-warning',
    'invoice.status.paid': 'text-bg-success',
    'invoice.status.void': 'text-bg-secondary',
    'coop.status.active': 'text-bg-success',
    'coop.status.suspended': 'text-bg-danger',
    'coop.status.closed': 'text-bg-secondary',
    'po.status.draft': 'text-bg-light border',
    'po.status.ordered': 'text-bg-primary',
    'po.status.partial': 'text-bg-warning',
    'po.status.received': 'text-bg-success',
    'po.status.cancelled': 'text-bg-secondary',
    'promo.state.active': 'text-bg-success',
    'promo.state.scheduled': 'text-bg-info',
    'promo.state.expired': 'text-bg-secondary',
    'promo.state.inactive': 'text-bg-light border',
    'promo.state.used_up': 'text-bg-warning',
    'payment.record.paid': 'text-bg-success',
    'payment.record.pending': 'text-bg-warning',
    'payment.record.failed': 'text-bg-danger',
    'payment.record.refunded': 'text-bg-secondary',
    'addon.status.active': 'text-bg-success',
    'addon.status.cancelling': 'text-bg-warning',
    'addon.status.available': 'text-bg-light border',
    'addon.status.unavailable': 'text-bg-secondary',
    'news.state.published': 'text-bg-success',
    'news.state.scheduled': 'text-bg-info',
    'news.state.draft': 'text-bg-light border',
    'clearance.state.active': 'text-bg-success',
    'clearance.state.scheduled': 'text-bg-info',
    'clearance.state.ended': 'text-bg-secondary',
    'clearance.state.inactive': 'text-bg-light border',
    'opening.state.open': 'text-bg-success',
    'opening.state.upcoming': 'text-bg-info',
    'opening.state.closed': 'text-bg-secondary',
    'messaging.status.sent': 'text-bg-success',
    'messaging.status.pending': 'text-bg-warning',
    'messaging.status.failed': 'text-bg-danger'
};

// statusBadge('ready') → status pesanan; statusBadge('trial', 'vendor.status')
function statusBadge(status, group) {
    var key = (group || 'order.status') + '.' + status;
    return createEl('span', 'badge ' + (BADGE_COLORS[key] || 'text-bg-secondary'), T(key));
}

// Lencana Aktif / Tidak aktif
function activeBadge(isActive) {
    return createEl('span', 'badge ' + (isActive ? 'text-bg-success' : 'text-bg-secondary'),
        T(isActive ? 'common.active' : 'common.inactive'));
}

// ---------- Elemen kecil ----------

function createIcon(iconClass) {
    var icon = createEl('i', 'bi ' + iconClass);
    icon.setAttribute('aria-hidden', 'true');
    return icon;
}

// Butang kecil untuk lajur tindakan jadual. label = teks untuk pembaca skrin jika tiada teks
function createButton(text, className, iconClass, onClick, label) {
    var button = createEl('button', 'btn btn-sm ' + (className || 'btn-outline-secondary'));
    button.type = 'button';
    if (iconClass) {
        button.appendChild(createIcon(iconClass));
        button.appendChild(document.createTextNode(text ? ' ' + text : ''));
    } else {
        button.textContent = text;
    }
    if (label) {
        button.setAttribute('aria-label', label);
        button.title = label;
    }
    button.addEventListener('click', onClick);
    return button;
}

function createLink(text, href, className) {
    var link = createEl('a', className || '', text);
    link.href = href;
    return link;
}

// Pautan butang dengan ikon sahaja (label untuk pembaca skrin)
function createIconLink(href, iconClass, label, className) {
    var link = createEl('a', 'btn btn-sm ' + (className || 'btn-outline-secondary'));
    link.href = href;
    link.setAttribute('aria-label', label);
    link.title = label;
    link.appendChild(createIcon(iconClass));
    return link;
}

// Pautan butang dengan ikon + teks
function createIconLinkText(href, iconClass, text, className) {
    var link = createEl('a', 'btn btn-sm ' + (className || 'btn-outline-secondary'));
    link.href = href;
    link.appendChild(createIcon(iconClass));
    link.appendChild(document.createTextNode(' ' + text));
    return link;
}

// Muat turun CSV (Excel: BOM UTF-8 supaya aksara Cina/Tamil betul). rows = [[sel, ...], ...]
function downloadCsv(filename, rows) {
    var text = rows.map(function (row) {
        return row.map(function (cell) {
            var value = cell === null || cell === undefined ? '' : String(cell);
            return /[",\n\r]/.test(value) ? '"' + value.replace(/"/g, '""') + '"' : value;
        }).join(',');
    }).join('\r\n');
    var blob = new Blob(['\ufeff' + text], { type: 'text/csv;charset=utf-8' });
    var link = createEl('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    setTimeout(function () {
        URL.revokeObjectURL(link.href);
        link.remove();
    }, 0);
}

// Teks utama + teks kecil di bawah (cth. nama + kod)
function createStack(main, sub) {
    var wrap = createEl('div');
    wrap.appendChild(main instanceof Node ? main : createEl('div', 'fw-semibold', main));
    if (sub) {
        wrap.appendChild(createEl('div', 'small text-body-secondary', sub));
    }
    return wrap;
}

// URL halaman dalam aplikasi, cth. pageUrl('owner/vendor.php?id=1')
function pageUrl(path) {
    return (document.body.getAttribute('data-base-url') || '') + '/' + path;
}

function queryParam(name) {
    return new URLSearchParams(window.location.search).get(name);
}

function debounce(fn, ms) {
    var timer = null;
    return function () {
        var args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(null, args);
        }, ms || 300);
    };
}

function showModal(id) {
    bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
}

function hideModal(id) {
    bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
}

// ---------- Jadual & penomboran ----------

function tableLoading(tbody, colspan) {
    if (typeof tbody === 'string') {
        tbody = document.getElementById(tbody);
    }
    var row = createEl('tr');
    var cell = createEl('td', 'text-center text-body-secondary py-4');
    cell.colSpan = colspan;
    cell.appendChild(createEl('span', 'spinner-border spinner-border-sm me-2'));
    cell.appendChild(document.createTextNode(T('common.loading')));
    row.appendChild(cell);
    tbody.replaceChildren(row);
}

// data = { total, page, per_page } dari format senarai. onPage(nombor) dipanggil bila ditekan
function renderPagination(container, data, onPage) {
    if (typeof container === 'string') {
        container = document.getElementById(container);
    }
    container.replaceChildren();

    var pages = Math.max(1, Math.ceil(data.total / data.per_page));
    var wrap = createEl('div', 'd-flex flex-wrap justify-content-between align-items-center gap-2');
    wrap.appendChild(createEl('div', 'small text-body-secondary', T('common.total_items', { total: data.total })));

    if (pages > 1) {
        var list = createEl('ul', 'pagination pagination-sm mb-0');
        list.appendChild(pageItem('‹', data.page - 1, data.page <= 1, false, onPage));
        pageNumbers(data.page, pages).forEach(function (number) {
            list.appendChild(pageItem(String(number), number, false, number === data.page, onPage));
        });
        list.appendChild(pageItem('›', data.page + 1, data.page >= pages, false, onPage));

        var nav = createEl('nav');
        nav.setAttribute('aria-label', T('common.page_of', { page: data.page, pages: pages }));
        nav.appendChild(list);
        wrap.appendChild(nav);
    }

    container.appendChild(wrap);
}

// Halaman semasa ±2, ditambah halaman pertama & terakhir
function pageNumbers(current, pages) {
    var numbers = [];
    for (var n = 1; n <= pages; n++) {
        if (n === 1 || n === pages || Math.abs(n - current) <= 2) {
            numbers.push(n);
        }
    }
    return numbers;
}

function pageItem(label, page, disabled, active, onPage) {
    var item = createEl('li', 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : ''));
    var button = createEl('button', 'page-link', label);
    button.type = 'button';
    button.disabled = disabled;
    button.addEventListener('click', function () {
        onPage(page);
    });
    item.appendChild(button);
    return item;
}

// ---------- Borang ----------
// Konvensyen atribut pada medan:
//   name="names.ms"         → { names: { ms: ... } }
//   data-type="money"       → input RM (12.50), dihantar sebagai sen (1250)
//   data-type="int"         → nombor bulat ('' → null)
//   data-type="lines"       → textarea, satu item setiap baris → array
//   checkbox                → true/false; checkbox dengan data-multi → array nilai yang ditanda
//   select[multiple]        → array

// "12.50" → 1250
function parseRM(text) {
    var amount = parseFloat(String(text).replace(/[^0-9.\-]/g, ''));
    return isNaN(amount) ? 0 : Math.round(amount * 100);
}

// 1250 → "12.50" (untuk nilai input)
function senToInput(sen) {
    return (Number(sen || 0) / 100).toFixed(2);
}

function convertFieldValue(raw, type) {
    if (type === 'money') {
        return raw === '' ? null : parseRM(raw);
    }
    if (type === 'int') {
        return raw === '' ? null : parseInt(raw, 10);
    }
    if (type === 'lines') {
        return raw.split('\n').map(function (line) {
            return line.trim();
        }).filter(function (line) {
            return line !== '';
        });
    }
    return raw;
}

function getPath(object, path) {
    return path.split('.').reduce(function (current, part) {
        return (current === undefined || current === null) ? undefined : current[part];
    }, object);
}

function setPath(object, path, value) {
    var parts = path.split('.');
    var current = object;
    parts.slice(0, -1).forEach(function (part) {
        if (typeof current[part] !== 'object' || current[part] === null) {
            current[part] = {};
        }
        current = current[part];
    });
    current[parts[parts.length - 1]] = value;
}

function formToObject(form) {
    var result = {};

    Array.prototype.forEach.call(form.elements, function (field) {
        if (!field.name || field.disabled || field.type === 'submit' || field.type === 'button') {
            return;
        }
        var type = field.getAttribute('data-type');

        if (field.type === 'checkbox' && field.hasAttribute('data-multi')) {
            var list = getPath(result, field.name) || [];
            if (field.checked) {
                list.push(convertFieldValue(field.value, type));
            }
            setPath(result, field.name, list);
        } else if (field.type === 'checkbox') {
            setPath(result, field.name, field.checked);
        } else if (field.type === 'radio') {
            if (field.checked) {
                setPath(result, field.name, convertFieldValue(field.value, type));
            }
        } else if (field.multiple) {
            var selected = Array.prototype.filter.call(field.options, function (option) {
                return option.selected;
            }).map(function (option) {
                return convertFieldValue(option.value, type);
            });
            setPath(result, field.name, selected);
        } else {
            setPath(result, field.name, convertFieldValue(field.value.trim(), type));
        }
    });

    return result;
}

function fillForm(form, data) {
    resetForm(form);

    Array.prototype.forEach.call(form.elements, function (field) {
        if (!field.name) {
            return;
        }
        var value = getPath(data, field.name);
        if (value === undefined) {
            return;
        }
        var type = field.getAttribute('data-type');

        if (field.type === 'checkbox' && field.hasAttribute('data-multi')) {
            field.checked = (value || []).map(String).indexOf(field.value) !== -1;
        } else if (field.type === 'checkbox') {
            field.checked = Boolean(value);
        } else if (field.type === 'radio') {
            field.checked = String(value) === field.value;
        } else if (field.multiple) {
            var values = (value || []).map(String);
            Array.prototype.forEach.call(field.options, function (option) {
                option.selected = values.indexOf(option.value) !== -1;
            });
        } else if (type === 'money') {
            field.value = (value === null) ? '' : senToInput(value);
        } else if (type === 'lines') {
            field.value = (value || []).join('\n');
        } else {
            field.value = (value === null) ? '' : value;
        }
    });
}

function resetForm(form) {
    form.reset();
    clearFormErrors(form);
    form.classList.remove('was-validated');
}

function clearFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(function (field) {
        field.classList.remove('is-invalid');
    });
}

// Pengesahan Bootstrap sisi klien. Pengesahan sebenar tetap di pelayan.
function validateForm(form) {
    clearFormErrors(form);
    var valid = form.checkValidity();
    form.classList.add('was-validated');
    if (!valid) {
        focusField(form.querySelector(':invalid'));
    }
    return valid;
}

// Fokus medan; jika ia dalam tab yang tersembunyi, buka tab itu dahulu
function focusField(field) {
    if (!field) {
        return;
    }
    var pane = field.closest('.tab-pane');
    if (pane && !pane.classList.contains('active')) {
        var trigger = document.querySelector('[data-bs-target="#' + pane.id + '"]');
        if (trigger) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }
    setTimeout(function () {
        field.focus();
    }, 200);
}

// errors = { field: 'mesej' } dari jawapan { ok: false }
function showFormErrors(form, errors) {
    form.classList.remove('was-validated');
    Object.keys(errors || {}).forEach(function (name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) {
            return;
        }
        field.classList.add('is-invalid');
        var feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = createEl('div', 'invalid-feedback');
            field.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = errors[name];
    });
    focusField(form.querySelector('.is-invalid'));
}

function formSubmitButtons(form) {
    var inside = Array.prototype.slice.call(form.querySelectorAll('[type="submit"]'));
    var outside = form.id ? Array.prototype.slice.call(document.querySelectorAll('[type="submit"][form="' + form.id + '"]')) : [];
    return inside.concat(outside);
}

// Sahkan → baca → Api.post → papar ralat atau panggil onSuccess(data).
// buildPayload(obj) pilihan untuk ubah data sebelum dihantar.
function submitForm(form, path, onSuccess, buildPayload) {
    if (!validateForm(form)) {
        return Promise.resolve(null);
    }

    var payload = formToObject(form);
    if (buildPayload) {
        payload = buildPayload(payload);
    }

    var buttons = formSubmitButtons(form);
    buttons.forEach(function (button) {
        button.disabled = true;
    });

    return Api.post(path, payload).then(function (res) {
        buttons.forEach(function (button) {
            button.disabled = false;
        });
        if (!res.ok) {
            showFormErrors(form, res.errors);
            showToast(res.error, 'danger');
            return null;
        }
        onSuccess(res.data);
        return res.data;
    });
}

// Pasang submitForm() pada acara submit borang
function bindForm(form, path, onSuccess, buildPayload) {
    if (typeof form === 'string') {
        form = document.getElementById(form);
    }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitForm(form, path, onSuccess, buildPayload);
    });
}

// ---------- Carta bar ringkas (SVG, tanpa pustaka) ----------
// items = [{ label: 'Mei', value: 12345 }], formatValue = fungsi untuk teks nilai
function renderBarChart(container, items, formatValue) {
    if (typeof container === 'string') {
        container = document.getElementById(container);
    }
    container.replaceChildren();

    var ns = 'http://www.w3.org/2000/svg';
    var width = 600;
    var height = 240;
    var top = 24;
    var bottom = 28;
    var max = Math.max.apply(null, items.map(function (item) { return item.value; }).concat([1]));
    var slot = width / Math.max(items.length, 1);
    var barWidth = slot * 0.6;

    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
    svg.setAttribute('class', 'app-chart');
    svg.setAttribute('role', 'img');

    // Ringkasan untuk pembaca skrin: "Mei: RM 1.00, Jun: RM 2.00"
    var summary = document.createElementNS(ns, 'title');
    summary.textContent = items.map(function (item) {
        return item.label + ': ' + (formatValue ? formatValue(item.value) : String(item.value));
    }).join(', ');
    svg.appendChild(summary);

    items.forEach(function (item, index) {
        var barHeight = (height - top - bottom) * (item.value / max);
        var x = index * slot + (slot - barWidth) / 2;
        var y = height - bottom - barHeight;
        var text = formatValue ? formatValue(item.value) : String(item.value);

        var bar = document.createElementNS(ns, 'rect');
        bar.setAttribute('x', x);
        bar.setAttribute('y', y);
        bar.setAttribute('width', barWidth);
        bar.setAttribute('height', Math.max(barHeight, 1));
        bar.setAttribute('rx', 4);
        bar.setAttribute('class', 'app-chart-bar');
        var tooltip = document.createElementNS(ns, 'title');
        tooltip.textContent = item.label + ': ' + text;
        bar.appendChild(tooltip);
        svg.appendChild(bar);

        svg.appendChild(chartText(ns, x + barWidth / 2, y - 6, text, 'app-chart-value'));
        svg.appendChild(chartText(ns, x + barWidth / 2, height - 8, item.label, 'app-chart-label'));
    });

    container.appendChild(svg);
}

function chartText(ns, x, y, text, className) {
    var el = document.createElementNS(ns, 'text');
    el.setAttribute('x', x);
    el.setAttribute('y', y);
    el.setAttribute('text-anchor', 'middle');
    el.setAttribute('class', className);
    el.textContent = text;
    return el;
}

// "2026-10" → "Okt" / "Oct" / "10月" ikut bahasa semasa
function formatMonth(yearMonth) {
    var locales = { ms: 'ms-MY', en: 'en-MY', zh: 'zh-CN', ta: 'ta-MY' };
    var date = new Date(yearMonth + '-01T00:00:00+08:00');
    return new Intl.DateTimeFormat(locales[currentLang()] || 'ms-MY', { month: 'short', timeZone: 'Asia/Kuala_Lumpur' }).format(date);
}

// Borang tapisan senarai: nilai medan disalin ke state, page = 1, kemudian onChange().
// Kotak carian ditunda 300ms; select/tarikh terus.
function bindListFilters(form, state, onChange) {
    if (typeof form === 'string') {
        form = document.getElementById(form);
    }

    function refresh() {
        var values = formToObject(form);
        Object.keys(values).forEach(function (key) {
            state[key] = values[key];
        });
        state.page = 1;
        onChange();
    }

    var delayed = debounce(refresh, 300);
    form.addEventListener('input', function (event) {
        if (event.target.type === 'search' || event.target.type === 'text') {
            delayed();
        }
    });
    form.addEventListener('change', function (event) {
        if (event.target.tagName === 'SELECT' || event.target.type === 'date' || event.target.type === 'checkbox') {
            refresh();
        }
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        refresh();
    });
    form.addEventListener('reset', function () {
        setTimeout(refresh, 0);
    });
}

// Isi <select> dengan pilihan. keepFirst = kekalkan pilihan pertama (cth. "Semua")
function fillSelect(select, items, valueField, labelFn, keepFirst) {
    if (typeof select === 'string') {
        select = document.getElementById(select);
    }
    var first = keepFirst ? select.options[0] : null;
    select.replaceChildren();
    if (first) {
        select.appendChild(first);
    }
    items.forEach(function (item) {
        var option = createEl('option', '', labelFn(item));
        option.value = item[valueField];
        select.appendChild(option);
    });
}

// ---------- Muat naik gambar (UI) ----------
// Semak jenis & saiz di pelayar (pelayan semak semula dengan finfo — PELAN 15).
// Pratonton sebagai data URL; onChange(dataUrl | null). Peringkat 1: data URL dihantar terus dalam mock.
var IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
var IMAGE_MAX_BYTES = 2 * 1024 * 1024;

function bindImageInput(input, previewBox, onChange) {
    if (typeof input === 'string') {
        input = document.getElementById(input);
    }
    input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) {
            return;
        }
        if (IMAGE_TYPES.indexOf(file.type) === -1) {
            input.value = '';
            showToast(T('upload.invalid_type'), 'danger');
            return;
        }
        if (file.size > IMAGE_MAX_BYTES) {
            input.value = '';
            showToast(T('upload.too_large', { max: '2MB' }), 'danger');
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            showImagePreview(previewBox, reader.result);
            onChange(reader.result);
        };
        reader.readAsDataURL(file);
    });
}

// Papar gambar dalam kotak; url kosong → ikon gantian
function showImagePreview(box, url, iconClass) {
    if (typeof box === 'string') {
        box = document.getElementById(box);
    }
    box.replaceChildren();
    if (url) {
        var img = createEl('img');
        img.src = url;
        img.alt = '';
        box.appendChild(img);
    } else {
        box.appendChild(createIcon(iconClass || 'bi-image'));
    }
}

// "SK Taman Melati" → "sk-taman-melati"
function slugify(text) {
    return String(text).toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60);
}
