// Borang koperasi — tambah / sunting

var coopForm = document.getElementById('coop-form');
var coopId = Number(queryParam('id')) || null;
var coopState = { logoUrl: null, slugTouched: Boolean(coopId) };

var DEFAULT_HOURS = {
    mon: { open: true, from: '07:30', to: '13:00' }, tue: { open: true, from: '07:30', to: '13:00' },
    wed: { open: true, from: '07:30', to: '13:00' }, thu: { open: true, from: '07:30', to: '13:00' },
    fri: { open: true, from: '07:30', to: '12:00' }, sat: { open: false, from: '', to: '' }, sun: { open: false, from: '', to: '' }
};

function loadTemplates() {
    // Template premium dilanggan per koperasi (B12) — koperasi baharu: percuma sahaja
    return Api.get('vendor/templates/options', coopId ? { cooperative_id: coopId } : {}).then(function (res) {
        var select = document.getElementById('coop-template');
        select.replaceChildren();
        (res.ok ? res.data : []).forEach(function (template) {
            var label = template.is_free ? template.name + ' (' + T('template.free') + ')' : template.name;
            var option = createEl('option', '', template.subscribed ? label : label + ' — ' + T('coop.template_needs_subscription'));
            option.value = template.id;
            option.disabled = !template.subscribed;
            select.appendChild(option);
        });
    });
}

// Waktu operasi: hari tutup → masa dimatikan
function syncDayInputs() {
    coopForm.querySelectorAll('.js-day-open').forEach(function (toggle) {
        var day = toggle.getAttribute('data-day');
        ['from', 'to'].forEach(function (part) {
            var input = coopForm.querySelector('[name="opening_hours.' + day + '.' + part + '"]');
            input.disabled = !toggle.checked;
            input.required = toggle.checked;
        });
    });
}

// Amaran harga koperasi tambahan (koperasi baharu sahaja)
function loadPricingPreview() {
    Api.get('vendor/cooperatives/pricing-preview').then(function (res) {
        if (!res.ok) {
            return;
        }
        var data = res.data;
        var alert = document.getElementById('pricing-alert');
        if (data.limit_reached) {
            alert.className = 'alert alert-danger';
            alert.textContent = T('coop.limit_reached', { max: data.max_cooperatives });
            document.getElementById('coop-submit').disabled = true;
            return;
        }
        var price = data.billing_cycle === 'yearly' ? data.price_yearly : data.price_monthly;
        alert.className = 'alert alert-warning';
        alert.replaceChildren(createIcon('bi-exclamation-triangle'), document.createTextNode(' ' + T('coop.pricing_warning', {
            n: data.cooperative_number,
            price: formatRM(price),
            cycle: T('billing.cycle.' + data.billing_cycle),
            date: formatDate(data.current_period_end),
            plan: data.plan_name
        })));
    });
}

// Semak slug (unik seluruh platform) selepas pengguna berhenti menaip
var checkSlug = debounce(function () {
    var input = document.getElementById('coop-slug');
    var status = document.getElementById('slug-status');
    if (!input.value) {
        status.textContent = T('coop.slug_help');
        status.className = 'form-text';
        return;
    }
    Api.get('vendor/cooperatives/check-slug', { slug: input.value, id: coopId }).then(function (res) {
        if (!res.ok) {
            return;
        }
        var url = window.location.origin + pageUrl('store/index.php?school=' + res.data.slug);
        if (!res.data.valid) {
            status.textContent = T('coop.slug_format');
        } else if (!res.data.available) {
            status.textContent = T('coop.slug_taken');
        } else {
            status.textContent = T('coop.slug_available', { url: url });
        }
        status.className = 'form-text ' + (res.data.available ? 'text-success' : 'text-danger');
        input.setCustomValidity(res.data.available ? '' : T('coop.slug_taken'));
    });
}, 400);

function loadCooperative() {
    if (!coopId) {
        fillForm(coopForm, { accept_cash: true, accept_online: true, default_language: 'ms', opening_hours: DEFAULT_HOURS, template_id: 1 });
        syncDayInputs();
        loadPricingPreview();
        return;
    }
    Api.get('vendor/cooperatives/get', { id: coopId }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            coopForm.classList.add('d-none');
            return;
        }
        fillForm(coopForm, res.data);
        coopState.logoUrl = res.data.logo_url;
        showImagePreview('logo-preview', coopState.logoUrl, 'bi-building');
        document.getElementById('page-title').textContent = T('coop.edit') + ': ' + res.data.school_name;
        syncDayInputs();
        checkSlug();
    });
}

document.getElementById('coop-name').addEventListener('input', function (event) {
    if (!coopState.slugTouched) {
        document.getElementById('coop-slug').value = slugify(event.target.value);
        checkSlug();
    }
});

document.getElementById('coop-slug').addEventListener('input', function (event) {
    coopState.slugTouched = true;
    event.target.value = event.target.value.toLowerCase();
    event.target.setCustomValidity('');
    checkSlug();
});

coopForm.querySelectorAll('.js-day-open').forEach(function (toggle) {
    toggle.addEventListener('change', syncDayInputs);
});

// Sekurang-kurangnya satu kaedah bayaran
function checkPaymentMethods() {
    var online = document.getElementById('coop-online');
    var anyChecked = document.getElementById('coop-cash').checked || online.checked;
    online.setCustomValidity(anyChecked ? '' : T('coop.payment_required'));
}
document.getElementById('coop-cash').addEventListener('change', checkPaymentMethods);
document.getElementById('coop-online').addEventListener('change', checkPaymentMethods);

bindImageInput('logo-file', 'logo-preview', function (dataUrl) {
    coopState.logoUrl = dataUrl;
});
document.getElementById('logo-remove').addEventListener('click', function () {
    coopState.logoUrl = null;
    document.getElementById('logo-file').value = '';
    showImagePreview('logo-preview', null, 'bi-building');
});

coopForm.addEventListener('submit', function (event) {
    event.preventDefault();
    checkPaymentMethods();
    submitForm(coopForm, 'vendor/cooperatives/save', function () {
        showToast(T('common.saved'), 'success');
        window.location.href = pageUrl('vendor/cooperatives.php');
    }, function (payload) {
        payload.id = coopId;
        payload.template_id = Number(payload.template_id);
        payload.logo_url = coopState.logoUrl;
        payload.school_code = payload.school_code.toUpperCase();
        // Hari tutup: masa dikosongkan (medan dimatikan tidak dibaca oleh formToObject)
        Object.keys(DEFAULT_HOURS).forEach(function (day) {
            payload.opening_hours[day].from = payload.opening_hours[day].from || '';
            payload.opening_hours[day].to = payload.opening_hours[day].to || '';
        });
        return payload;
    });
});

loadTemplates().then(loadCooperative);
