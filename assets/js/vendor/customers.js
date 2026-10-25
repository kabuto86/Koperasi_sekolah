// Ibu bapa & murid (baca sahaja + nota)

var customerList = { page: 1, per_page: 20, q: '', grade: '', class_name: '' };
var noteForm = document.getElementById('note-form');

function loadCustomers() {
    tableLoading('customers-body', 6);
    Api.get('vendor/customers/list', withCoop(customerList)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('customers-body', res.data.items, [
            { render: function (row) {
                var stack = createStack(row.full_name, T('customer.relationship.' + row.relationship));
                stack.appendChild(createEl('div', 'small', row.phone + ' · ' + row.email));
                return stack;
            } },
            { render: function (row) {
                var wrap = createEl('div', 'small');
                row.children.forEach(function (child) {
                    wrap.appendChild(createEl('div', '', child.full_name + ' · ' + T('class.full_name', { grade: T('grade.' + child.grade_level_code), name: child.class_name })));
                });
                return wrap;
            } },
            { className: 'text-center', render: function (row) {
                return row.order_count ? createLink(String(row.order_count), pageUrl('vendor/orders.php?q=' + encodeURIComponent(row.phone))) : '0';
            } },
            { className: 'text-end', render: function (row) { return formatRM(row.total_spent); } },
            { className: 'small', render: function (row) { return row.last_order_at ? formatDate(row.last_order_at) : '—'; } },
            { render: function (row) {
                var wrap = createEl('div', 'd-flex align-items-start gap-2');
                wrap.appendChild(createEl('span', 'small text-body-secondary flex-grow-1', row.note || ''));
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-sticky', function () {
                    fillForm(noteForm, { customer_id: row.id, note: row.note });
                    document.getElementById('note-customer').textContent = row.full_name;
                    showModal('note-modal');
                }, T('customer.note_title')));
                return wrap;
            } }
        ]);
        renderPagination('customers-pagination', res.data, function (page) {
            customerList.page = page;
            loadCustomers();
        });
    });
}

bindForm(noteForm, 'vendor/customers/save-note', function () {
    hideModal('note-modal');
    showToast(T('common.saved'), 'success');
    loadCustomers();
}, function (payload) {
    payload.customer_id = Number(payload.customer_id);
    return withCoop(payload);
});

bindListFilters('customer-filters', customerList, loadCustomers);

onCooperativeReady(function () {
    customerList.page = 1;
    bindGradeClassFilters('cus-grade', 'cus-class').then(function () {
        customerList.grade = document.getElementById('cus-grade').value;
        customerList.class_name = document.getElementById('cus-class').value;
        loadCustomers();
    });
});
