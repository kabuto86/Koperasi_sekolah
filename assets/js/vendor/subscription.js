// Langganan & bil (peringkat vendor — semua koperasi)

var planForm = document.getElementById('plan-form');
var subscriptionState = { data: null, plans: [] };

function cyclePrice(row, cycle) {
    return cycle === 'yearly' ? row.price_yearly : row.price_monthly;
}

function loadSubscription() {
    Api.get('vendor/subscription/get').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        subscriptionState.data = res.data;
        renderPlan(res.data);
        renderUsage(res.data);
        renderCooperatives(res.data);
        renderRenewal(res.data);
        renderAlerts(res.data);
    });
}

function renderPlan(data) {
    document.getElementById('sub-plan').textContent = data.plan.name;
    document.getElementById('sub-status').replaceChildren(statusBadge(data.status, 'vendor.status'));
    document.getElementById('sub-cycle').textContent = T('billing.cycle.' + data.billing_cycle);
    document.getElementById('sub-period').textContent = formatDate(data.current_period_start + 'T00:00:00+08:00') + ' – '
        + formatDate(data.current_period_end + 'T00:00:00+08:00') + ' · ' + T('billing.days_left', { n: data.days_left });
    document.getElementById('sub-price').textContent = T('billing.price_cycle', { amount: formatRM(cyclePrice(data.plan, data.billing_cycle)), cycle: T('billing.per.' + data.billing_cycle) });
    var list = document.getElementById('sub-features');
    list.replaceChildren();
    data.plan.features.forEach(function (feature) {
        var item = createEl('li');
        item.appendChild(createIcon('bi-check2 text-success'));
        item.appendChild(document.createTextNode(' ' + feature));
        list.appendChild(item);
    });
}

// Penggunaan berbanding had pelan (<progress> — tiada lebar inline)
function renderUsage(data) {
    var box = document.getElementById('sub-usage');
    box.replaceChildren();
    [['cooperatives', 'max_cooperatives', 'billing.usage_coops'], ['products', 'max_products', 'billing.usage_products'], ['staff', 'max_staff', 'billing.usage_staff']].forEach(function (row) {
        var used = data.usage[row[0]];
        var max = data.plan[row[1]];
        var wrap = createEl('div', 'mb-3');
        var line = createEl('div', 'd-flex justify-content-between small');
        line.appendChild(createEl('span', '', T(row[2])));
        line.appendChild(createEl('span', 'fw-semibold', max === null ? T('billing.used_unlimited', { used: used }) : used + ' / ' + max));
        wrap.appendChild(line);
        var bar = createEl('progress', 'app-progress' + (max !== null && used >= max ? ' is-full' : ''));
        bar.max = max === null ? 1 : max;
        bar.value = max === null ? 0 : Math.min(used, max);
        bar.setAttribute('aria-label', T(row[2]));
        wrap.appendChild(bar);
        box.appendChild(wrap);
    });
}

function renderCooperatives(data) {
    renderTable('coops-body', data.cooperatives, [
        { render: function (coop) {
            return createStack(coop.school_name, coop.included ? T('billing.coop_included') : T('billing.coop_additional', { n: coop.number }));
        } },
        { className: 'small', render: function (coop) {
            return createStack(createEl('span', '', coop.template_name), coop.template_price ? formatRM(coop.template_price) : T('template.free'));
        } },
        { render: function (coop) {
            var wrap = createEl('div', 'd-flex flex-wrap gap-1');
            coop.modules.forEach(function (module) {
                var badge = createEl('span', 'badge ' + (module.cancel_at_period_end ? 'text-bg-warning' : 'text-bg-light border'), T('module.' + module.code));
                if (module.cancel_at_period_end) {
                    badge.title = T('addon.ends_on', { date: formatDate(data.current_period_end + 'T00:00:00+08:00') });
                }
                wrap.appendChild(badge);
            });
            if (!coop.modules.length) {
                wrap.appendChild(createEl('span', 'small text-body-secondary', T('common.none')));
            }
            return wrap;
        } },
        { className: 'text-end text-nowrap', render: function (coop) { return coop.included ? T('billing.in_plan') : formatRM(coop.price); } }
    ]);
}

function renewalLabel(line) {
    if (line.type === 'plan') {
        return T('billing.renewal_plan', { name: line.label });
    }
    if (line.type === 'module') {
        return T('billing.renewal_module', { name: T('module.' + line.label), school: line.cooperative_name });
    }
    if (line.type === 'template') {
        return T('billing.renewal_template', { name: line.label, school: line.cooperative_name });
    }
    return T('billing.renewal_coop', { school: line.label });
}

function renderRenewal(data) {
    document.getElementById('renewal-date').textContent = T('billing.renewal_on', { date: formatDate(data.current_period_end + 'T00:00:00+08:00'), cycle: T('billing.cycle.' + data.billing_cycle) });
    var list = document.getElementById('renewal-lines');
    list.replaceChildren();
    data.renewal.lines.forEach(function (line) {
        var item = createEl('li', 'list-group-item d-flex justify-content-between gap-2 small' + (line.cancelling ? ' text-decoration-line-through text-body-secondary' : ''));
        item.appendChild(createEl('span', '', renewalLabel(line)));
        item.appendChild(createEl('span', 'text-nowrap', formatRM(line.price)));
        list.appendChild(item);
    });
    document.getElementById('renewal-subtotal').textContent = formatRM(data.renewal.subtotal);
    document.getElementById('renewal-sst-label').textContent = T('invoice.sst', { rate: data.renewal.sst_rate });
    document.getElementById('renewal-tax').textContent = formatRM(data.renewal.tax);
    document.getElementById('renewal-total').textContent = formatRM(data.renewal.total);
}

function renderAlerts(data) {
    document.getElementById('unpaid-alert').classList.toggle('d-none', data.unpaid_count === 0);
    document.getElementById('unpaid-text').textContent = T('billing.unpaid_alert', { n: data.unpaid_count, amount: formatRM(data.unpaid_total) });
    var pending = data.pending_change;
    document.getElementById('pending-alert').classList.toggle('d-none', !pending);
    if (pending) {
        document.getElementById('pending-text').textContent = T('billing.pending_change', {
            plan: pending.plan_name, cycle: T('billing.cycle.' + pending.billing_cycle), date: formatDate(pending.effective_at + 'T00:00:00+08:00')
        });
    }
}

// ---------- Tukar pelan / kitaran ----------

function renderPlanOptions() {
    var cycle = (planForm.querySelector('[name="billing_cycle"]:checked') || {}).value || 'monthly';
    var box = document.getElementById('plan-options');
    var selected = (planForm.querySelector('[name="plan_id"]:checked') || {}).value;
    box.replaceChildren();
    subscriptionState.plans.forEach(function (plan) {
        var col = createEl('div', 'col-md-4');
        var input = createEl('input', 'btn-check');
        input.type = 'radio';
        input.name = 'plan_id';
        input.id = 'plan-' + plan.id;
        input.value = plan.id;
        input.required = true;
        input.checked = String(plan.id) === String(selected || subscriptionState.current);
        var label = createEl('label', 'btn btn-outline-secondary w-100 h-100 text-start');
        label.htmlFor = input.id;
        label.appendChild(createEl('span', 'fw-semibold d-block', plan.name + (plan.id === subscriptionState.current ? ' ' + T('billing.current_tag') : '')));
        label.appendChild(createEl('span', 'fs-5 fw-bold d-block', formatRM(cyclePrice(plan, cycle))));
        label.appendChild(createEl('span', 'small d-block', T('billing.per.' + cycle)));
        [['max_cooperatives', 'plan.limit_cooperatives'], ['max_products', 'plan.limit_products'], ['max_staff', 'plan.limit_staff']].forEach(function (limit) {
            label.appendChild(createEl('span', 'small d-block', T(limit[1], { n: plan[limit[0]] === null ? '∞' : plan[limit[0]] })));
        });
        col.appendChild(input);
        col.appendChild(label);
        box.appendChild(col);
    });
}

document.getElementById('btn-change-plan').addEventListener('click', function () {
    Api.get('vendor/subscription/plans').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        subscriptionState.plans = res.data.plans;
        subscriptionState.current = res.data.current_plan_id;
        var pending = subscriptionState.data.pending_change;
        resetForm(planForm);
        document.getElementById('cycle-' + (pending ? pending.billing_cycle : res.data.billing_cycle)).checked = true;
        document.getElementById('plan-options').replaceChildren();
        renderPlanOptions();
        if (pending) {
            document.getElementById('plan-' + pending.plan_id).checked = true;
        }
        showModal('plan-modal');
    });
});

planForm.querySelectorAll('[name="billing_cycle"]').forEach(function (input) {
    input.addEventListener('change', renderPlanOptions);
});

bindForm(planForm, 'vendor/subscription/change', function (data) {
    hideModal('plan-modal');
    showToast(data.pending_change ? T('billing.change_scheduled') : T('common.saved'), 'success');
    loadSubscription();
}, function (payload) {
    payload.plan_id = Number(payload.plan_id);
    return payload;
});

document.getElementById('btn-cancel-change').addEventListener('click', function () {
    Api.post('vendor/subscription/cancel-change', {}).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        showToast(T('common.saved'), 'success');
        loadSubscription();
    });
});

loadSubscription();
