// Template frontend — kad, pratonton, sunting harga

var templateForm = document.getElementById('template-form');

function loadTemplates() {
    Api.get('owner/templates/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var grid = document.getElementById('templates-grid');
        grid.replaceChildren();
        res.data.forEach(function (template) {
            grid.appendChild(templateCard(template));
        });
    });
}

// Gambar pratonton; tiada gambar → ikon
function previewImage(template, container) {
    container.replaceChildren();
    if (template.preview_image) {
        var img = createEl('img', 'img-fluid rounded');
        img.src = template.preview_image;
        img.alt = template.name;
        container.appendChild(img);
    } else {
        container.appendChild(createIcon('bi-window-desktop'));
    }
}

function templateCard(template) {
    var col = createEl('div', 'col-12 col-md-6 col-xl-4');
    var card = createEl('div', 'card border-0 shadow-sm h-100');

    var preview = createEl('div', 'app-template-preview card-img-top');
    previewImage(template, preview);
    card.appendChild(preview);

    var body = createEl('div', 'card-body');
    var titleRow = createEl('div', 'd-flex justify-content-between align-items-start gap-2 mb-1');
    titleRow.appendChild(createEl('h2', 'h6 mb-0', template.name));
    titleRow.appendChild(activeBadge(template.is_active));
    body.appendChild(titleRow);
    body.appendChild(createEl('div', 'small text-body-secondary font-monospace mb-2', template.code));
    body.appendChild(createEl('p', 'small mb-2', template.description || ''));

    var price = template.is_free
        ? createEl('span', 'badge text-bg-success', T('template.free'))
        : createEl('span', 'fw-semibold', T('template.price_line', { monthly: formatRM(template.price_monthly), yearly: formatRM(template.price_yearly) }));
    body.appendChild(price);
    body.appendChild(createEl('div', 'small text-body-secondary mt-1', T('template.used_by', { n: template.cooperative_count })));
    card.appendChild(body);

    var footer = createEl('div', 'card-footer bg-body d-flex gap-2');
    footer.appendChild(createButton(T('template.preview'), 'btn-outline-secondary', 'bi-eye', function () {
        openPreview(template);
    }));
    footer.appendChild(createButton(T('btn.edit'), 'btn-outline-secondary', 'bi-pencil', function () {
        openTemplateForm(template);
    }));
    card.appendChild(footer);

    col.appendChild(card);
    return col;
}

function openPreview(template) {
    document.getElementById('preview-title').textContent = template.name;
    previewImage(template, document.getElementById('preview-image'));
    document.getElementById('preview-description').textContent = template.description || '';
    document.getElementById('preview-folder').textContent = template.folder;
    document.getElementById('preview-open').href = pageUrl('store/index.php?school=sk-taman-melati&template=' + encodeURIComponent(template.code));
    showModal('preview-modal');
}

function togglePriceFields() {
    var isFree = document.getElementById('template-free').checked;
    document.getElementById('template-prices').classList.toggle('d-none', isFree);
    ['template-monthly', 'template-yearly'].forEach(function (id) {
        document.getElementById(id).disabled = isFree;
    });
}

function openTemplateForm(template) {
    fillForm(templateForm, template);
    // Template 1 sentiasa percuma & aktif — jatuh balik untuk semua koperasi
    var locked = template.code === 'template1';
    document.getElementById('template-free').disabled = locked;
    document.getElementById('template-active').disabled = locked;
    document.getElementById('template1-note').classList.toggle('d-none', !locked);
    togglePriceFields();
    showModal('template-modal');
}

document.getElementById('template-free').addEventListener('change', togglePriceFields);

bindForm(templateForm, 'owner/templates/save', function () {
    hideModal('template-modal');
    showToast(T('common.saved'), 'success');
    loadTemplates();
}, function (payload) {
    payload.id = Number(payload.id);
    payload.is_free = document.getElementById('template-free').checked;
    payload.is_active = document.getElementById('template-active').checked;
    return payload;
});

loadTemplates();
