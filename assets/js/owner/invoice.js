// Butiran invois

var invoiceId = Number(queryParam('id'));
var invoiceData = null;

function setText(id, value) {
    document.getElementById(id).textContent = (value === null || value === undefined || value === '') ? '—' : value;
}

function loadInvoice() {
    Api.get('owner/invoices/get', { id: invoiceId }).then(function (res) {
        if (!res.ok) {
            var errorBox = document.getElementById('invoice-error');
            errorBox.textContent = res.error;
            errorBox.classList.remove('d-none');
            document.getElementById('invoice-card').classList.add('d-none');
            return;
        }
        invoiceData = res.data;
        renderInvoice(res.data);
    });
}

function renderInvoice(invoice) {
    document.getElementById('page-title').textContent = T('invoice.title') + ' ' + invoice.invoice_no;
    setText('inv-vendor-name', invoice.vendor.company_name + ' (' + invoice.vendor.code + ')');
    setText('inv-vendor-reg', invoice.vendor.registration_no);
    setText('inv-vendor-address', invoice.vendor.address);
    setText('inv-vendor-email', invoice.vendor.email);
    setText('inv-no', invoice.invoice_no);
    document.getElementById('inv-status').replaceChildren(statusBadge(invoice.status, 'invoice.status'));
    setText('inv-period', formatDate(invoice.period_start) + ' – ' + formatDate(invoice.period_end));
    setText('inv-issued', formatDate(invoice.issued_at));
    setText('inv-due', formatDate(invoice.due_at));
    setText('inv-paid', formatDate(invoice.paid_at));
    setText('inv-subtotal', formatRM(invoice.subtotal));
    setText('inv-tax-label', T('invoice.sst', { rate: invoice.sst_rate }));
    setText('inv-tax', formatRM(invoice.tax));
    setText('inv-total', formatRM(invoice.total));

    renderTable('lines-body', invoice.lines, [
        { render: function (line) {
            var text = createStack(line.description, T('invoice.item_type.' + line.item_type));
            if (line.is_prorated) {
                text.appendChild(createEl('span', 'badge text-bg-info', T('invoice.prorated')));
            }
            return text;
        } },
        { key: 'qty', className: 'text-center' },
        { className: 'text-end', render: function (line) { return formatRM(line.unit_price); } },
        { className: 'text-end', render: function (line) { return formatRM(line.amount); } }
    ]);

    renderTable('payments-body', invoice.payments, [
        { render: function (payment) { return formatDate(payment.paid_at, true); } },
        { render: function (payment) {
            return T('payment.method.' + payment.method) + (payment.gateway_code ? ' · ' + payment.gateway_code : '');
        } },
        { key: 'reference' },
        { className: 'text-end', render: function (payment) { return formatRM(payment.amount); } }
    ]);

    document.getElementById('btn-issue').classList.toggle('d-none', invoice.status !== 'draft');
    document.getElementById('btn-mark-paid').classList.toggle('d-none', invoice.status !== 'issued');
    document.getElementById('btn-void').classList.toggle('d-none', ['draft', 'issued'].indexOf(invoice.status) === -1);
}

function invoiceAction(path, message, options, successText) {
    confirmDialog(message, options).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post(path, { id: invoiceId }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(successText, 'success');
            loadInvoice();
        });
    });
}

function todayIso() {
    return new Date(Date.now() + 8 * 3600 * 1000).toISOString().slice(0, 10);
}

document.getElementById('btn-print').addEventListener('click', function () {
    window.print();
});

document.getElementById('btn-issue').addEventListener('click', function () {
    invoiceAction('owner/invoices/issue', T('invoice.issue_confirm', { no: invoiceData.invoice_no }),
        { okText: T('invoice.issue') }, T('invoice.issued'));
});

document.getElementById('btn-void').addEventListener('click', function () {
    invoiceAction('owner/invoices/void', T('invoice.void_confirm', { no: invoiceData.invoice_no }),
        { okText: T('invoice.void'), okClass: 'btn-danger' }, T('invoice.voided'));
});

document.getElementById('btn-mark-paid').addEventListener('click', function () {
    var form = document.getElementById('paid-form');
    resetForm(form);
    document.getElementById('paid-date').value = todayIso();
    document.getElementById('paid-summary').textContent = T('invoice.mark_paid_summary', {
        no: invoiceData.invoice_no, total: formatRM(invoiceData.total)
    });
    showModal('paid-modal');
});

bindForm('paid-form', 'owner/invoices/mark-paid', function () {
    hideModal('paid-modal');
    showToast(T('invoice.paid_saved'), 'success');
    loadInvoice();
}, function (payload) {
    payload.id = invoiceId;
    return payload;
});

loadInvoice();
