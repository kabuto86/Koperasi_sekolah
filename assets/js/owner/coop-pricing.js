// Harga koperasi tambahan — jadual peringkat per pelan.
// Peraturan: peringkat pertama bermula koperasi ke-2; setiap "dari" = "hingga" sebelumnya + 1;
// peringkat terakhir tiada had (hingga kosong).

var pricing = { plan: null, plans: [] };
var tiersBody = document.getElementById('tiers-body');

function loadPlans() {
    Api.get('owner/plans/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        pricing.plans = res.data;
        fillSelect('plan-select', res.data, 'id', function (plan) { return plan.name + ' (' + plan.code + ')'; }, false);
        var wanted = Number(queryParam('plan_id'));
        if (wanted) {
            document.getElementById('plan-select').value = wanted;
        }
        loadTiers();
    });
}

function loadTiers() {
    var planId = Number(document.getElementById('plan-select').value);
    Api.get('owner/plans/tiers', { plan_id: planId }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        pricing.plan = res.data.plan;
        document.getElementById('plan-base-price').textContent = T('coop_pricing.base_price', {
            monthly: formatRM(pricing.plan.price_monthly), yearly: formatRM(pricing.plan.price_yearly)
        });
        tiersBody.replaceChildren();
        res.data.tiers.forEach(addTierRow);
        refreshTierRows();
    });
}

function moneyInput(name, sen, label) {
    var group = createEl('div', 'input-group input-group-sm app-money-input');
    group.appendChild(createEl('span', 'input-group-text', 'RM'));
    var input = createEl('input', 'form-control');
    input.type = 'number';
    input.min = '0';
    input.step = '0.01';
    input.required = true;
    input.name = name;
    input.value = (sen === null || sen === undefined) ? '' : senToInput(sen);
    input.setAttribute('aria-label', label);
    input.addEventListener('input', updateCalculator);
    group.appendChild(input);
    return group;
}

function addTierRow(tier) {
    var row = createEl('tr');

    var fromCell = createEl('td', 'fw-semibold text-nowrap js-from');
    row.appendChild(fromCell);

    var toCell = createEl('td');
    var toInput = createEl('input', 'form-control form-control-sm app-int-input');
    toInput.type = 'number';
    toInput.min = '2';
    toInput.name = 'to_coop';
    toInput.value = tier && tier.to_coop !== null ? tier.to_coop : '';
    toInput.setAttribute('aria-label', T('coop_pricing.to_coop'));
    toInput.addEventListener('input', refreshTierRows);
    toCell.appendChild(toInput);
    toCell.appendChild(createEl('div', 'form-text js-to-help'));
    row.appendChild(toCell);

    var monthlyCell = createEl('td');
    monthlyCell.appendChild(moneyInput('price_monthly', tier ? tier.price_monthly : null, T('coop_pricing.price_monthly_each')));
    row.appendChild(monthlyCell);

    var yearlyCell = createEl('td');
    yearlyCell.appendChild(moneyInput('price_yearly', tier ? tier.price_yearly : null, T('coop_pricing.price_yearly_each')));
    row.appendChild(yearlyCell);

    var actionCell = createEl('td', 'text-end');
    actionCell.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
        row.remove();
        refreshTierRows();
    }, T('btn.delete')));
    row.appendChild(actionCell);

    tiersBody.appendChild(row);
}

// Kira semula lajur "dari", tandakan peringkat terakhir (hingga = tiada had)
function refreshTierRows() {
    var rows = Array.prototype.slice.call(tiersBody.rows);
    var from = 2;
    rows.forEach(function (row, index) {
        var isLast = index === rows.length - 1;
        var toInput = row.querySelector('[name="to_coop"]');
        row.querySelector('.js-from').textContent = T('coop_pricing.coop_n', { n: from });
        toInput.disabled = isLast;
        toInput.required = !isLast;
        toInput.min = from;
        if (isLast) {
            toInput.value = '';
        }
        row.querySelector('.js-to-help').textContent = isLast ? T('coop_pricing.and_above') : '';
        var to = parseInt(toInput.value, 10);
        from = isNaN(to) ? from + 1 : to + 1;
    });
    updateCalculator();
}

function readTiers() {
    var from = 2;
    return Array.prototype.map.call(tiersBody.rows, function (row, index, rows) {
        var isLast = index === rows.length - 1;
        var to = isLast ? null : parseInt(row.querySelector('[name="to_coop"]').value, 10);
        var tier = {
            from_coop: from,
            to_coop: (to === null || isNaN(to)) ? null : to,
            price_monthly: parseRM(row.querySelector('[name="price_monthly"]').value),
            price_yearly: parseRM(row.querySelector('[name="price_yearly"]').value)
        };
        from = (tier.to_coop || from) + 1;
        return tier;
    });
}

// Jumlah = harga pelan (koperasi ke-1) + harga peringkat untuk koperasi ke-2 dan seterusnya
function updateCalculator() {
    if (!pricing.plan) {
        return;
    }
    var count = Math.max(1, parseInt(document.getElementById('calc-coops').value, 10) || 1);
    var tiers = readTiers();
    var monthly = pricing.plan.price_monthly;
    var yearly = pricing.plan.price_yearly;

    for (var n = 2; n <= count; n++) {
        var tier = tiers.filter(function (item) {
            return n >= item.from_coop && (item.to_coop === null || n <= item.to_coop);
        })[0];
        if (tier) {
            monthly += tier.price_monthly;
            yearly += tier.price_yearly;
        }
    }
    document.getElementById('calc-monthly').textContent = formatRM(monthly);
    document.getElementById('calc-yearly').textContent = formatRM(yearly);
}

document.getElementById('plan-select').addEventListener('change', loadTiers);
document.getElementById('calc-coops').addEventListener('input', updateCalculator);

document.getElementById('btn-add-tier').addEventListener('click', function () {
    // Tetapkan had pada peringkat terakhir sedia ada supaya peringkat baharu boleh bermula selepasnya
    var rows = tiersBody.rows;
    if (rows.length > 0) {
        var lastTo = rows[rows.length - 1].querySelector('[name="to_coop"]');
        lastTo.disabled = false;
        lastTo.value = readTiers()[rows.length - 1].from_coop + 1;
    }
    addTierRow(null);
    refreshTierRows();
});

document.getElementById('btn-default-tier').addEventListener('click', function () {
    tiersBody.replaceChildren();
    addTierRow({ to_coop: null, price_monthly: pricing.plan.price_monthly, price_yearly: pricing.plan.price_yearly });
    refreshTierRows();
});

document.getElementById('tiers-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var form = event.target;
    if (tiersBody.rows.length === 0) {
        showToast(T('coop_pricing.invalid_tiers'), 'danger');
        return;
    }
    if (!validateForm(form)) {
        return;
    }
    Api.post('owner/plans/save-tiers', { plan_id: pricing.plan.id, tiers: readTiers() }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        form.classList.remove('was-validated');
        showToast(T('common.saved'), 'success');
    });
});

loadPlans();
