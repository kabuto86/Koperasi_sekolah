// Promosi / kupon koperasi semasa

var promoList = { page: 1, per_page: 20, q: '', state: '' };
var promoForm = document.getElementById('promo-form');
var promoType = document.getElementById('promo-type');

// "10%" atau "RM 5.00"
function promoValueText(promo) {
    return promo.type === 'percent' ? T('promo.percent_off', { value: promo.value }) : T('promo.fixed_off', { amount: formatRM(promo.value) });
}

function loadPromotions() {
    tableLoading('promos-body', 6);
    Api.get('vendor/promotions/list', withCoop(promoList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('promos-body', res.data.items, [
            { render: function (row) {
                var code = row.code ? createEl('span', 'badge text-bg-light border font-monospace', row.code) : createEl('span', 'small text-body-secondary', T('promo.automatic'));
                var stack = createStack(row.name);
                stack.appendChild(code);
                return stack;
            } },
            { render: function (row) {
                return createStack(promoValueText(row), row.min_spend ? T('promo.min_spend_line', { amount: formatRM(row.min_spend) }) : '');
            } },
            { className: 'small text-nowrap', render: function (row) { return formatDate(row.starts_on) + ' – ' + formatDate(row.ends_on); } },
            { className: 'text-center', render: function (row) {
                return row.max_uses ? row.used_count + ' / ' + row.max_uses : String(row.used_count);
            } },
            { render: function (row) { return statusBadge(row.state, 'promo.state'); } },
            { className: 'text-end text-nowrap', render: function (row) {
                var wrap = createEl('div', 'd-inline-flex gap-1');
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                    openPromoForm(row);
                }, T('btn.edit')));
                if (row.used_count === 0) {
                    wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
                        deletePromotion(row);
                    }, T('btn.delete')));
                }
                return wrap;
            } }
        ]);
        renderPagination('promos-pagination', res.data, function (page) {
            promoList.page = page;
            loadPromotions();
        });
    });
}

// Nilai: peratus (1–100) atau RM (dihantar sebagai sen)
function updateValueUnit() {
    var isPercent = promoType.value === 'percent';
    document.getElementById('promo-value-rm').classList.toggle('d-none', isPercent);
    document.getElementById('promo-value-pct').classList.toggle('d-none', !isPercent);
}

function openPromoForm(promo) {
    document.getElementById('promo-title').textContent = promo ? T('promo.edit') : T('promo.add');
    var data = promo || { type: 'percent', is_active: true, starts_on: new Date().toISOString().slice(0, 10) };
    fillForm(promoForm, data);
    document.getElementById('promo-value').value = promo ? (promo.type === 'percent' ? promo.value : senToInput(promo.value)) : '';
    updateValueUnit();
    showModal('promo-modal');
}

function deletePromotion(promo) {
    confirmDialog(T('promo.delete_confirm', { name: promo.name }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/promotions/delete', withCoop({ id: promo.id })).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('common.deleted'), 'success');
            loadPromotions();
        });
    });
}

promoType.addEventListener('change', updateValueUnit);

document.getElementById('btn-add-promo').addEventListener('click', function () {
    openPromoForm(null);
});

bindForm(promoForm, 'vendor/promotions/save', function () {
    hideModal('promo-modal');
    showToast(T('common.saved'), 'success');
    loadPromotions();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    payload.code = payload.code ? payload.code.toUpperCase() : null;
    payload.value = payload.type === 'percent' ? Number(payload.value) : parseRM(payload.value);
    return withCoop(payload);
});

bindListFilters('promo-filters', promoList, loadPromotions);

onCooperativeReady(function () {
    promoList.page = 1;
    loadPromotions();
});
