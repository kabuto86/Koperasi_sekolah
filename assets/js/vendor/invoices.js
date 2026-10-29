// Invois langganan vendor

var invoiceList = { page: 1, per_page: 20, q: '', status: queryParam('status') || '' };
var invoiceState = { current: null };

function isoDay(day) {
    return formatDate(day + 'T00:00:00+08:00');
}

function loadInvoices() {
    tableLoading('invoices-body', 6);
    Api.get('vendor/invoices/list', invoiceList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('invoices-body', res.data.items, [
            { render: function (row) { return createStack(row.invoice_no, formatDate(row.issued_at)); } },
            { className: 'small text-nowrap', render: function (row) { return isoDay(row.period_start) + ' – ' + isoDay(row.period_end); } },
            { className: 'small', render: function (row) {
                var overdue = row.status === 'issued' && new Date(row.due_at) < new Date();
                return createEl('span', overdue ? 'text-danger fw-semibold' : '', formatDate(row.due_at));
            } },
            { className: 'text-end fw-semibold', render: function (row) { return formatRM(row.total); } },
            { render: function (row) { return statusBadge(row.status, 'invoice.status'); } },
            { className: 'text-end text-nowrap', render: function (row) {
                var wrap = createEl('div', 'd-inline-flex gap-1');
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-eye', function () {
                    openInvoice(row.id);
                }, T('btn.view')));
                if (row.status === 'issued') {
                    wrap.appendChild(createButton(T('billing.pay_now'), 'btn-brand', 'bi-credit-card', function () {
                        payInvoice(row);
                    }));
                }
                return wrap;
            } }
        ]);
        renderPagination('invoices-pagination', res.data, function (page) {
            invoiceList.page = page;
            loadInvoices();
        });
    });
}

function openInvoice(id) {
    Api.get('vendor/invoices/get', { id: id }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var invoice = res.data;
        invoiceState.current = invoice;
        document.getElementById('invoice-title').textContent = T('invoice.title') + ' ' + invoice.invoice_no;
        document.getElementById('iv-vendor').textContent = invoice.vendor.company_name + ' (' + invoice.vendor.code + ')';
        document.getElementById('iv-status').replaceChildren(statusBadge(invoice.status, 'invoice.status'));
        document.getElementById('iv-dates').textContent = T('billing.invoice_dates', {
            period: isoDay(invoice.period_start) + ' – ' + isoDay(invoice.period_end), due: formatDate(invoice.due_at)
        });
        renderTable('iv-lines', invoice.lines, [
            { render: function (line) {
                var stack = createStack(createEl('span', '', line.description), T('invoice.item_type.' + line.item_type));
                if (line.is_prorated) {
                    stack.appendChild(createEl('span', 'badge text-bg-light border', T('invoice.prorated')));
                }
                return stack;
            } },
            { key: 'qty', className: 'text-end' },
            { className: 'text-end', render: function (line) { return formatRM(line.amount); } }
        ]);
        document.getElementById('iv-subtotal').textContent = formatRM(invoice.subtotal);
        document.getElementById('iv-sst-label').textContent = T('invoice.sst', { rate: invoice.sst_rate });
        document.getElementById('iv-tax').textContent = formatRM(invoice.tax);
        document.getElementById('iv-total').textContent = formatRM(invoice.total);

        var payments = document.getElementById('iv-payments');
        payments.replaceChildren();
        invoice.payments.forEach(function (payment) {
            payments.appendChild(createEl('div', 'alert alert-success py-2 small', T('billing.paid_line', {
                amount: formatRM(payment.amount), date: formatDate(payment.paid_at, true), ref: payment.reference
            })));
        });
        document.getElementById('btn-pay').classList.toggle('d-none', invoice.status !== 'issued');
        showModal('invoice-modal');
    });
}

// Pelayan pulangkan redirect_url gateway pemilik; status "dibayar" hanya selepas gateway disahkan (webhook / semakan semula)
function payInvoice(invoice) {
    confirmDialog(T('billing.pay_confirm', { no: invoice.invoice_no, amount: formatRM(invoice.total) }), { okText: T('billing.pay_now'), okClass: 'btn-brand' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/invoices/pay', { id: invoice.id }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            if (res.data.redirect_url) {
                window.location.href = res.data.redirect_url;
                return;
            }
            showToast(T('billing.gateway_unavailable'), 'danger');
        });
    });
}

document.getElementById('btn-pay').addEventListener('click', function () {
    if (invoiceState.current) {
        payInvoice(invoiceState.current);
    }
});

// Kembali dari gateway (?paid_return={id}): pelayan bertanya sendiri ke gateway — parameter redirect tidak dipercayai
function checkReturnedPayment() {
    var invoiceId = Number(queryParam('paid_return'));
    if (!invoiceId) {
        return Promise.resolve();
    }
    window.history.replaceState(null, '', window.location.pathname);
    return Api.post('vendor/invoices/check-payment', { id: invoiceId }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var result = res.data.result.split(' ')[0];
        if (result === 'paid' || result === 'already_paid') {
            showToast(T('billing.paid_toast', { no: res.data.invoice.invoice_no }), 'success');
        } else if (result === 'failed') {
            showToast(T('billing.payment_failed'), 'danger');
        } else {
            showToast(T('billing.payment_pending'), 'warning');
        }
    });
}

document.getElementById('inv-status').value = invoiceList.status;
bindListFilters('invoice-filters', invoiceList, loadInvoices);
checkReturnedPayment().then(loadInvoices);
