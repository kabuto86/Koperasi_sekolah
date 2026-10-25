// Koperasi saya — kad setiap koperasi

function coopCard(coop) {
    var col = createEl('div', 'col-12 col-md-6 col-xxl-4');
    var card = createEl('div', 'card border-0 shadow-sm h-100' + (coop.id === currentCooperativeId() ? ' border-start border-4 app-border-brand' : ''));
    var body = createEl('div', 'card-body');

    var head = createEl('div', 'd-flex gap-3 align-items-start mb-2');
    head.appendChild(coopLogo(coop));
    var title = createEl('div', 'flex-grow-1 min-w-0');
    title.appendChild(createEl('h2', 'h6 mb-0 text-truncate', coop.school_name));
    title.appendChild(createEl('div', 'small text-body-secondary', coop.school_type + ' · ' + coop.school_code));
    title.appendChild(createEl('div', 'small text-body-secondary', coop.district + ', ' + coop.state));
    head.appendChild(title);
    head.appendChild(statusBadge(coop.status, 'coop.status'));
    body.appendChild(head);

    var facts = createEl('div', 'd-flex flex-wrap gap-1 mb-2');
    if (coop.accept_cash) {
        facts.appendChild(createEl('span', 'badge text-bg-light border', T('payment.method.cash')));
    }
    if (coop.accept_online) {
        facts.appendChild(createEl('span', 'badge text-bg-light border', T('payment.method.online')));
    }
    coop.modules.forEach(function (code) {
        facts.appendChild(createEl('span', 'badge text-bg-info', T('module.' + code)));
    });
    body.appendChild(facts);

    body.appendChild(createEl('div', 'small', T('coop.card_counts', { classes: coop.class_count, products: coop.product_count })));
    body.appendChild(createEl('div', 'small text-body-secondary', T('field.template') + ': ' + coop.template_name));
    body.appendChild(createEl('div', 'small text-body-secondary font-monospace text-truncate', '/' + coop.slug));
    card.appendChild(body);

    var footer = createEl('div', 'card-footer bg-body d-flex flex-wrap gap-2');
    footer.appendChild(createLink(T('btn.edit'), pageUrl('vendor/cooperative-form.php?id=' + coop.id), 'btn btn-sm btn-outline-secondary'));
    var storeLink = createLink(T('coop.view_store'), pageUrl('store/index.php?school=' + encodeURIComponent(coop.slug)), 'btn btn-sm btn-outline-secondary');
    storeLink.target = '_blank';
    storeLink.rel = 'noopener';
    footer.appendChild(storeLink);
    card.appendChild(footer);

    col.appendChild(card);
    return col;
}

function coopLogo(coop) {
    var box = createEl('div', 'app-logo-box');
    if (coop.logo_url) {
        var img = createEl('img', 'img-fluid');
        img.src = coop.logo_url;
        img.alt = '';
        box.appendChild(img);
    } else {
        box.appendChild(createIcon('bi-building'));
    }
    return box;
}

function loadCooperatives() {
    Api.get('vendor/cooperatives/list', { per_page: 100 }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var grid = document.getElementById('coop-grid');
        grid.replaceChildren();
        res.data.items.forEach(function (coop) {
            grid.appendChild(coopCard(coop));
        });
    });
}

// Muat semula supaya penanda "koperasi semasa" dikemas kini
onCooperativeReady(loadCooperatives);
