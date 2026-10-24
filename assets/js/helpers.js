// Fungsi bantu umum untuk semua halaman.
// Bergantung pada: bootstrap.bundle.min.js, lang.js

// 1250 → "RM 12.50" (wang sentiasa dalam sen)
function formatRM(sen) {
    var amount = Number(sen || 0) / 100;
    var text = Math.abs(amount).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return (amount < 0 ? '-RM ' : 'RM ') + text;
}

// "2026-10-24T10:15:00+08:00" → "24/10/2026" atau "24/10/2026 10:15"
function formatDate(iso, withTime) {
    if (!iso) {
        return '';
    }

    var date = new Date(iso);
    if (isNaN(date.getTime())) {
        return '';
    }

    var options = { timeZone: 'Asia/Kuala_Lumpur', day: '2-digit', month: '2-digit', year: 'numeric' };
    if (withTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
        options.hour12 = false;
    }

    return new Intl.DateTimeFormat('en-GB', options).format(date).replace(',', '');
}

// Cipta elemen dengan kelas & teks (teks selamat — tiada innerHTML)
function createEl(tag, className, text) {
    var el = document.createElement(tag);
    if (className) {
        el.className = className;
    }
    if (text !== undefined && text !== null) {
        el.textContent = text;
    }
    return el;
}

// Mesej ringkas di penjuru skrin. type: success | danger | warning | info
function showToast(message, type) {
    var container = document.getElementById('app-toasts');
    if (!container) {
        return;
    }

    var toast = createEl('div', 'toast align-items-center border-0 text-bg-' + (type || 'success'));
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');

    var row = createEl('div', 'd-flex');
    row.appendChild(createEl('div', 'toast-body', message));

    var close = createEl('button', 'btn-close btn-close-white me-2 m-auto');
    close.type = 'button';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', T('btn.close'));
    row.appendChild(close);

    toast.appendChild(row);
    container.appendChild(toast);

    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).show();
}

// Dialog pengesahan. Memulangkan Promise<boolean>.
// options: { title, okText, okClass }  cth. okClass: 'btn-danger'
function confirmDialog(message, options) {
    options = options || {};

    var modalEl = document.getElementById('app-confirm');
    var okButton = document.getElementById('app-confirm-ok');
    document.getElementById('app-confirm-title').textContent = options.title || T('common.confirm_title');
    document.getElementById('app-confirm-message').textContent = message || T('common.confirm_default');
    okButton.textContent = options.okText || T('btn.confirm');
    okButton.className = 'btn ' + (options.okClass || 'btn-primary');

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    return new Promise(function (resolve) {
        var confirmed = false;

        function onOk() {
            confirmed = true;
            modal.hide();
        }

        function onHidden() {
            okButton.removeEventListener('click', onOk);
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            resolve(confirmed);
        }

        okButton.addEventListener('click', onOk);
        modalEl.addEventListener('hidden.bs.modal', onHidden);
        modal.show();
    });
}

// Isi <tbody> dengan baris.
// columns: [{ key: 'name', className: 'text-end', render: function (item) { return 'teks' atau Node; } }]
function renderTable(tbody, items, columns) {
    if (typeof tbody === 'string') {
        tbody = document.getElementById(tbody);
    }
    tbody.replaceChildren();

    if (!items || items.length === 0) {
        var emptyRow = createEl('tr');
        var emptyCell = createEl('td', 'text-center text-body-secondary py-4', T('common.no_data'));
        emptyCell.colSpan = columns.length;
        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);
        return;
    }

    items.forEach(function (item) {
        var row = createEl('tr');
        columns.forEach(function (column) {
            row.appendChild(renderTableCell(item, column));
        });
        tbody.appendChild(row);
    });
}

function renderTableCell(item, column) {
    var cell = createEl('td', column.className || '');
    var value = column.render ? column.render(item) : item[column.key];

    if (value instanceof Node) {
        cell.appendChild(value);
    } else if (value !== undefined && value !== null) {
        cell.textContent = String(value);
    }

    return cell;
}

// Lencana status pesanan, cth. statusBadge('ready')
function statusBadge(status) {
    var colors = {
        pending_payment: 'text-bg-warning',
        paid: 'text-bg-info',
        unpaid_cash: 'text-bg-warning',
        preparing: 'text-bg-primary',
        ready: 'text-bg-success',
        partially_collected: 'text-bg-secondary',
        collected: 'text-bg-dark',
        cancelled: 'text-bg-light',
        expired: 'text-bg-light',
        refunded: 'text-bg-danger'
    };
    return createEl('span', 'badge ' + (colors[status] || 'text-bg-secondary'), T('order.status.' + status));
}
