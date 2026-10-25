// Kedai modul add-on & template premium untuk koperasi semasa

var storeState = { catalog: null, pending: null, highlight: queryParam('module') };
var ADDON_ICONS = JSON.parse(document.getElementById('module-cards').getAttribute('data-icons') || '{}');

function loadCatalog(coop) {
    document.getElementById('store-coop').textContent = T('common.current_cooperative', { name: coop.school_name });
    Api.get('vendor/addons/catalog', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        storeState.catalog = res.data;
        renderModules(res.data);
        renderTemplates(res.data);
        // Dari menu add-on berkunci: buka anggaran harga modul itu terus (sekali sahaja)
        if (storeState.highlight) {
            var wanted = res.data.modules.filter(function (module) { return module.code === storeState.highlight && module.status === 'available'; })[0];
            storeState.highlight = null;
            if (wanted) {
                openQuote('module', wanted.code);
            }
        }
    });
}

function priceLine(row, cycle) {
    return T('billing.price_cycle', { amount: formatRM(row.price), cycle: T('billing.per.' + cycle) });
}

function moduleAction(module, data) {
    if (module.status === 'available') {
        return createButton(T('module.subscribe'), 'btn-brand', 'bi-cart-plus', function () {
            openQuote('module', module.code);
        });
    }
    if (module.status === 'active') {
        var wrap = createEl('div', 'd-flex flex-wrap gap-1');
        wrap.appendChild(createIconLinkText(pageUrl('vendor/addons/' + module.code + '.php'), 'bi-box-arrow-up-right', T('addon.open'), 'btn-outline-secondary'));
        wrap.appendChild(createButton(T('addon.stop'), 'btn-outline-danger', null, function () {
            setCancel(module, false, data);
        }));
        return wrap;
    }
    if (module.status === 'cancelling') {
        return createButton(T('addon.resume'), 'btn-outline-success', 'bi-arrow-counterclockwise', function () {
            setCancel(module, true, data);
        });
    }
    return createEl('span', 'small text-body-secondary', T('addon.coming_soon'));
}

function renderModules(data) {
    var box = document.getElementById('module-cards');
    box.replaceChildren();
    data.modules.forEach(function (module) {
        var col = createEl('div', 'col-12 col-md-6 col-xxl-4');
        var card = createEl('div', 'card border shadow-sm h-100' + (module.code === storeState.highlight ? ' app-border-brand border-2' : ''));
        card.id = 'module-' + module.code;
        var body = createEl('div', 'card-body d-flex gap-3');
        var icon = createEl('div', 'app-stat-icon app-stat-brand');
        icon.appendChild(createIcon(ADDON_ICONS[module.code] || 'bi-puzzle'));
        body.appendChild(icon);

        var text = createEl('div', 'flex-grow-1 min-w-0');
        var title = createEl('div', 'd-flex flex-wrap justify-content-between gap-2');
        title.appendChild(createEl('span', 'fw-semibold', T('module.' + module.code)));
        title.appendChild(statusBadge(module.status, 'addon.status'));
        text.appendChild(title);
        text.appendChild(createEl('p', 'small text-body-secondary mb-2', T('addon.desc.' + module.code)));
        text.appendChild(createEl('div', 'fw-semibold', priceLine(module, data.billing_cycle)));
        if (module.status === 'cancelling') {
            text.appendChild(createEl('div', 'small text-warning-emphasis', T('addon.ends_on', { date: formatDate(data.current_period_end + 'T00:00:00+08:00') })));
        } else if (module.started_at) {
            text.appendChild(createEl('div', 'small text-body-secondary', T('addon.since', { date: formatDate(module.started_at + 'T00:00:00+08:00') })));
        }
        body.appendChild(text);
        card.appendChild(body);

        var footer = createEl('div', 'card-footer bg-body');
        footer.appendChild(moduleAction(module, data));
        card.appendChild(footer);
        col.appendChild(card);
        box.appendChild(col);
    });
}

function renderTemplates(data) {
    var box = document.getElementById('template-cards');
    box.replaceChildren();
    data.templates.forEach(function (template) {
        var col = createEl('div', 'col-12 col-md-6 col-xxl-4');
        var card = createEl('div', 'card border shadow-sm h-100');
        var preview = createEl('div', 'app-template-preview');
        preview.appendChild(createIcon('bi-phone'));
        card.appendChild(preview);
        var body = createEl('div', 'card-body');
        var title = createEl('div', 'd-flex flex-wrap justify-content-between gap-2');
        title.appendChild(createEl('span', 'fw-semibold', template.name));
        if (template.in_use) {
            title.appendChild(createEl('span', 'badge text-bg-brand', T('addon.in_use')));
        } else if (template.subscribed) {
            title.appendChild(statusBadge('active', 'addon.status'));
        }
        body.appendChild(title);
        body.appendChild(createEl('div', 'small mt-1', template.is_free ? T('template.free') : priceLine(template, data.billing_cycle)));
        card.appendChild(body);

        var footer = createEl('div', 'card-footer bg-body d-flex flex-wrap gap-1');
        if (!template.subscribed) {
            footer.appendChild(createButton(T('module.subscribe'), 'btn-brand', 'bi-cart-plus', function () {
                openQuote('template', template.code);
            }));
        } else if (!template.in_use) {
            footer.appendChild(createIconLinkText(pageUrl('vendor/cooperative-form.php?id=' + currentCooperativeId()), 'bi-brush', T('addon.use_template'), 'btn-outline-secondary'));
        }
        footer.appendChild(createIconLinkText(pageUrl('store/index.php?school=' + encodeURIComponent(currentCooperative().slug) + '&template=' + template.code), 'bi-eye', T('template.preview'), 'btn-outline-secondary'));
        card.appendChild(footer);
        col.appendChild(card);
        box.appendChild(col);
    });
}

// ---------- Langgan dengan anggaran prorata ----------

function openQuote(itemType, code) {
    Api.get('vendor/addons/quote', withCoop({ item_type: itemType, code: code })).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var quote = res.data;
        storeState.pending = { item_type: itemType, code: code };
        document.getElementById('quote-item').textContent = quote.item_name;
        document.getElementById('quote-coop').textContent = quote.cooperative_name;
        document.getElementById('quote-unit').textContent = T('billing.price_cycle', { amount: formatRM(quote.unit_price), cycle: T('billing.per.' + quote.billing_cycle) });
        document.getElementById('quote-days').textContent = T('addon.days_of', { days: quote.days_left, total: quote.period_days });
        document.getElementById('quote-amount').textContent = formatRM(quote.amount);
        document.getElementById('quote-sst-label').textContent = T('invoice.sst', { rate: quote.sst_rate });
        document.getElementById('quote-tax').textContent = formatRM(quote.tax);
        document.getElementById('quote-total').textContent = formatRM(quote.total);
        document.getElementById('quote-note').textContent = T('addon.quote_note', { date: formatDate(quote.period_end + 'T00:00:00+08:00') });
        showModal('quote-modal');
    });
}

document.getElementById('btn-confirm-subscribe').addEventListener('click', function () {
    var button = this;
    button.disabled = true;
    Api.post('vendor/addons/subscribe', withCoop(storeState.pending)).then(function (res) {
        button.disabled = false;
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        hideModal('quote-modal');
        showToast(T('addon.subscribed', { no: res.data.invoice_no, amount: formatRM(res.data.total) }), 'success');
        // Menu sidebar: buka kunci modul yang baru dilanggan
        loadAddonMenu(currentCooperativeId());
        loadCatalog(currentCooperative());
    });
});

function setCancel(module, undo, data) {
    var message = undo ? T('addon.resume_confirm', { name: T('module.' + module.code) })
        : T('addon.stop_confirm', { name: T('module.' + module.code), date: formatDate(data.current_period_end + 'T00:00:00+08:00') });
    confirmDialog(message, { okClass: undo ? 'btn-success' : 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/addons/cancel', withCoop({ code: module.code, undo: undo })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('common.saved'), 'success');
            loadCatalog(currentCooperative());
        });
    });
}

onCooperativeReady(loadCatalog);
