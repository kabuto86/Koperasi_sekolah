// Pelan langganan — CRUD

var planForm = document.getElementById('plan-form');

function loadPlans() {
    tableLoading('plans-body', 7);
    Api.get('owner/plans/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('plans-body', res.data, [
            { render: function (row) { return createStack(row.name, row.code); } },
            { className: 'text-end', render: function (row) { return formatRM(row.price_monthly); } },
            { className: 'text-end', render: function (row) { return formatRM(row.price_yearly); } },
            { render: function (row) { return planLimits(row); } },
            { key: 'vendor_count', className: 'text-center' },
            { render: function (row) { return activeBadge(row.is_active); } },
            { className: 'text-end text-nowrap', render: function (row) { return planActions(row); } }
        ]);
    });
}

function limitText(value) {
    return value === null ? T('plan.unlimited') : String(value);
}

function planLimits(plan) {
    return createStack(
        T('plan.limit_cooperatives', { n: limitText(plan.max_cooperatives) }),
        T('plan.limit_products', { n: limitText(plan.max_products) }) + ' · ' + T('plan.limit_staff', { n: limitText(plan.max_staff) })
    );
}

function planActions(plan) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
        openPlanForm(plan);
    }, T('btn.edit')));
    wrap.appendChild(createLink(T('menu.coop_pricing'), pageUrl('owner/coop-pricing.php?plan_id=' + plan.id), 'btn btn-sm btn-outline-secondary'));
    wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
        deletePlan(plan);
    }, T('btn.delete')));
    return wrap;
}

function openPlanForm(plan) {
    document.getElementById('plan-modal-title').textContent = plan ? T('plan.edit') : T('plan.add');
    fillForm(planForm, plan || { is_active: true, sort_order: 0, features: [] });
    updateYearlyHint();
    showModal('plan-modal');
}

// Petunjuk: harga tahunan berbanding 12 × bulanan
function updateYearlyHint() {
    var monthly = parseRM(document.getElementById('plan-monthly').value);
    var yearly = parseRM(document.getElementById('plan-yearly').value);
    var hint = '';
    if (monthly > 0 && yearly > 0) {
        var saving = Math.round((1 - yearly / (monthly * 12)) * 100);
        hint = T('plan.yearly_saving', { percent: saving });
    }
    document.getElementById('plan-yearly-hint').textContent = hint;
}

function deletePlan(plan) {
    confirmDialog(T('plan.delete_confirm', { name: plan.name }), { okText: T('btn.delete'), okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('owner/plans/delete', { id: plan.id }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('common.deleted'), 'success');
            loadPlans();
        });
    });
}

document.getElementById('btn-add-plan').addEventListener('click', function () {
    openPlanForm(null);
});
document.getElementById('plan-monthly').addEventListener('input', updateYearlyHint);
document.getElementById('plan-yearly').addEventListener('input', updateYearlyHint);

bindForm(planForm, 'owner/plans/save', function () {
    hideModal('plan-modal');
    showToast(T('common.saved'), 'success');
    loadPlans();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return payload;
});

loadPlans();
