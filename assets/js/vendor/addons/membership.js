// Modul keahlian koperasi — ahli, transaksi syer & dividen tahunan (koperasi semasa). Amaun dalam sen; kiraan di pelayan.

var mbList = { page: 1, per_page: 20, q: '', member_type: '', status: '' };
var memberForm = document.getElementById('member-form');
var txForm = document.getElementById('tx-form');
var dvForm = document.getElementById('dv-form');
var mbState = { member: null, dividend: null };

function todayLocal() {
    return localDay(new Date());
}

// ---------- Ahli ----------

function loadMembers() {
    tableLoading('mb-body', 7);
    Api.get('vendor/membership/members', withCoop(mbList)).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        document.getElementById('mb-stat-members').textContent = String(res.data.summary.active_members);
        document.getElementById('mb-stat-shares').textContent = formatRM(res.data.summary.total_shares);
        renderTable('mb-body', res.data.items, [
            { className: 'font-monospace', render: function (row) { return row.member_no; } },
            { render: function (row) { return createStack(row.full_name, row.class_name || row.phone || ''); } },
            { className: 'small', render: function (row) { return T('membership.type.' + row.member_type); } },
            { className: 'small text-nowrap', render: function (row) { return formatDate(row.joined_on + 'T00:00:00+08:00'); } },
            { className: 'text-end', render: function (row) { return formatRM(row.share_balance); } },
            { render: function (row) { return statusBadge(row.status, 'membership.status'); } },
            { className: 'text-end text-nowrap', render: function (row) {
                var wrap = createEl('div', 'd-inline-flex gap-1');
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-wallet2', function () {
                    openShares(row.id);
                }, T('membership.shares')));
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                    openMemberForm(row);
                }, T('btn.edit')));
                return wrap;
            } }
        ]);
        renderPagination('mb-pagination', res.data, function (page) {
            mbList.page = page;
            loadMembers();
        });
    });
}

function updateClassField() {
    document.getElementById('mf-class-box').classList.toggle('d-none', memberForm.elements.member_type.value !== 'student');
}

function openMemberForm(row) {
    document.getElementById('member-modal-title').textContent = row ? T('membership.edit', { no: row.member_no }) : T('membership.add');
    fillForm(memberForm, row || { member_type: 'student', status: 'active', joined_on: todayLocal() });
    updateClassField();
    showModal('member-modal');
}

memberForm.elements.member_type.addEventListener('change', updateClassField);

bindForm(memberForm, 'vendor/membership/save-member', function (data) {
    hideModal('member-modal');
    showToast(T('membership.saved', { no: data.member_no }), 'success');
    loadMembers();
}, function (payload) {
    return withCoop(payload);
});

// ---------- Syer ----------

function renderShares(member) {
    mbState.member = member;
    var info = document.getElementById('sh-member');
    info.replaceChildren(createStack(member.member_no + ' · ' + member.full_name, T('membership.type.' + member.member_type)));
    document.getElementById('sh-balance').textContent = formatRM(member.share_balance);
    renderTable('sh-body', member.transactions, [
        { className: 'small text-nowrap', render: function (row) { return formatDate(row.transaction_date + 'T00:00:00+08:00'); } },
        { render: function (row) { return createEl('span', 'badge ' + (row.type === 'withdraw' ? 'text-bg-warning' : 'text-bg-success'), T('membership.tx.' + row.type)); } },
        { className: 'text-end', render: function (row) { return (row.type === 'withdraw' ? '−' : '+') + formatRM(row.amount); } },
        { className: 'small', render: function (row) { return createStack(row.note || '—', row.staff_name); } }
    ]);
}

function openShares(memberId) {
    Api.get('vendor/membership/member', withCoop({ id: memberId })).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        resetForm(txForm);
        txForm.elements.transaction_date.value = todayLocal();
        renderShares(res.data);
        showModal('shares-modal');
    });
}

bindForm(txForm, 'vendor/membership/add-transaction', function (data) {
    resetForm(txForm);
    txForm.elements.transaction_date.value = todayLocal();
    renderShares(data);
    showToast(T('common.saved'), 'success');
    loadMembers();
}, function (payload) {
    payload.member_id = mbState.member ? mbState.member.id : 0;
    return withCoop(payload);
});

// ---------- Dividen ----------

function dividendPayload() {
    return withCoop(formToObject(dvForm));
}

function renderDividendLines(data, title, status) {
    var detail = document.getElementById('dv-detail');
    detail.classList.remove('d-none');
    document.getElementById('dv-detail-title').textContent = title;
    var statusBox = document.getElementById('dv-detail-status');
    statusBox.replaceChildren();
    if (status) {
        statusBox.appendChild(statusBadge(status, 'membership.dividend_status'));
    }
    document.getElementById('dv-detail-meta').textContent = T('membership.dividend_meta', {
        rate: data.rate, members: data.member_count, shares: formatRM(data.total_shares), total: formatRM(data.total_amount)
    }) + (data.payout ? ' · ' + T('membership.payout.' + data.payout) : '');
    renderTable('dv-lines', data.lines, [
        { className: 'font-monospace', render: function (row) { return row.member_no; } },
        { render: function (row) { return row.full_name; } },
        { className: 'text-end', render: function (row) { return formatRM(row.share_balance); } },
        { className: 'text-end', render: function (row) { return formatRM(row.amount); } }
    ]);
    var foot = document.getElementById('dv-foot');
    foot.replaceChildren(createEl('td', '', T('field.total')), createEl('td', ''), createEl('td', 'text-end', formatRM(data.total_shares)),
        createEl('td', 'text-end', formatRM(data.total_amount)));
    mbState.dividend = data;
    document.getElementById('btn-dv-pay').classList.toggle('d-none', status !== 'declared');
    document.getElementById('btn-dv-delete').classList.toggle('d-none', status !== 'declared');
}

function previewDividend() {
    if (!dvForm.checkValidity()) {
        dvForm.classList.add('was-validated');
        return;
    }
    Api.get('vendor/membership/preview-dividend', dividendPayload()).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        res.data.id = null;
        renderDividendLines(res.data, T('membership.preview_title', { year: res.data.year }), null);
        if (res.data.exists) {
            showToast(T('membership.dividend_exists', { year: res.data.year }), 'warning');
        }
    });
}

function loadDividends() {
    Api.get('vendor/membership/dividends', withCoop()).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        var list = document.getElementById('dv-list');
        list.replaceChildren();
        if (res.data.length === 0) {
            list.appendChild(createEl('li', 'list-group-item small text-body-secondary', T('membership.no_dividends')));
        }
        res.data.forEach(function (row) {
            var item = createEl('li', 'list-group-item list-group-item-action d-flex align-items-center gap-2');
            item.setAttribute('role', 'button');
            item.tabIndex = 0;
            item.appendChild(createStack(T('membership.dividend_title', { year: row.year }), row.rate + '% · ' + formatRM(row.total_amount)));
            var badge = statusBadge(row.status, 'membership.dividend_status');
            badge.classList.add('ms-auto');
            item.appendChild(badge);
            var open = function () {
                openDividend(row.id);
            };
            item.addEventListener('click', open);
            item.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    open();
                }
            });
            list.appendChild(item);
        });
    });
}

function openDividend(id) {
    Api.get('vendor/membership/dividend', withCoop({ id: id })).then(function (res) {
        if (moduleResponse(res)) {
            renderDividendLines(res.data, T('membership.dividend_title', { year: res.data.year }), res.data.status);
        }
    });
}

bindForm(dvForm, 'vendor/membership/declare-dividend', function (data) {
    showToast(T('membership.declared', { year: data.year }), 'success');
    renderDividendLines(data, T('membership.dividend_title', { year: data.year }), data.status);
    loadDividends();
}, function (payload) {
    return withCoop(payload);
});

document.getElementById('btn-dv-preview').addEventListener('click', previewDividend);

document.getElementById('btn-dv-pay').addEventListener('click', function () {
    var dividend = mbState.dividend;
    var message = T(dividend.payout === 'reinvest' ? 'membership.pay_confirm_reinvest' : 'membership.pay_confirm_cash', { year: dividend.year, total: formatRM(dividend.total_amount) });
    confirmDialog(message, { okClass: 'btn-success' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/membership/pay-dividend', withCoop({ id: dividend.id })).then(function (res) {
            if (moduleResponse(res)) {
                showToast(T('common.saved'), 'success');
                renderDividendLines(res.data, T('membership.dividend_title', { year: res.data.year }), res.data.status);
                loadDividends();
                loadMembers();
            }
        });
    });
});

document.getElementById('btn-dv-delete').addEventListener('click', function () {
    var dividend = mbState.dividend;
    confirmDialog(T('membership.delete_confirm', { year: dividend.year }), { okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/membership/delete-dividend', withCoop({ id: dividend.id })).then(function (res) {
            if (moduleResponse(res)) {
                document.getElementById('dv-detail').classList.add('d-none');
                showToast(T('common.deleted'), 'success');
                loadDividends();
            }
        });
    });
});

document.getElementById('btn-dv-csv').addEventListener('click', function () {
    var data = mbState.dividend;
    if (!data) {
        return;
    }
    var rows = [[T('membership.member_no'), T('field.full_name'), T('membership.share_balance_year_end'), T('membership.dividend_amount')]];
    data.lines.forEach(function (line) {
        rows.push([line.member_no, line.full_name, (line.share_balance / 100).toFixed(2), (line.amount / 100).toFixed(2)]);
    });
    downloadCsv('dividen-' + data.year + '.csv', rows);
});

document.getElementById('btn-add-member').addEventListener('click', function () {
    openMemberForm(null);
});

bindListFilters('mb-filters', mbList, loadMembers);

onModuleReady(function () {
    mbList.page = 1;
    document.getElementById('dv-detail').classList.add('d-none');
    loadMembers();
    loadDividends();
});
