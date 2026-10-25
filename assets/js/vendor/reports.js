// Laporan koperasi semasa. Jawapan API: { columns: [{ key, label, format }], rows, totals, chart }
// Satu pemapar untuk semua jenis laporan; CSV dijana dari data yang sama.

var reportForm = document.getElementById('report-form');
var reportState = { data: null };

// 'YYYY-MM-DD' hari ini + offset hari (zon Asia/Kuala_Lumpur)
function dayString(offsetDays) {
    return new Date(Date.now() + 8 * 3600000 + (offsetDays || 0) * 86400000).toISOString().slice(0, 10);
}

function rangeDates(range) {
    var today = dayString(0);
    var monthStart = today.slice(0, 8) + '01';
    if (range === 'today') {
        return [today, today];
    }
    if (range === '7days') {
        return [dayString(-6), today];
    }
    if (range === 'last_month') {
        var date = new Date(monthStart + 'T00:00:00Z');
        date.setUTCDate(0);
        var end = date.toISOString().slice(0, 10);
        return [end.slice(0, 8) + '01', end];
    }
    return [monthStart, today];
}

function setRange(range) {
    var dates = rangeDates(range);
    document.getElementById('rep-from').value = dates[0];
    document.getElementById('rep-to').value = dates[1];
}

// Format nilai sel ikut jenis lajur
function formatCell(value, format) {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    switch (format) {
        case 'money': return formatRM(value);
        case 'percent': return value + '%';
        case 'date': return formatDate(value + 'T00:00:00+08:00');
        case 'month': return formatMonth(value) + ' ' + value.slice(0, 4);
        case 'grade': return T('grade.' + value);
        case 'method': return T('payment.method.' + value);
        default: return String(value);
    }
}

function isNumeric(format) {
    return ['money', 'percent', 'int'].indexOf(format) !== -1;
}

function runReport() {
    var params = withCoop(formToObject(reportForm));
    document.getElementById('rep-body').replaceChildren();
    Api.get('vendor/reports/run', params).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        reportState.data = res.data;
        renderReport(res.data);
    });
}

function renderReport(data) {
    document.getElementById('report-result').classList.remove('d-none');
    document.getElementById('rep-title').textContent = T('report.type.' + data.type) + ' — ' + data.school_name;
    document.getElementById('rep-meta').textContent = (data.date_from ? T('report.period', { from: formatDate(data.date_from + 'T00:00:00+08:00'), to: formatDate(data.date_to + 'T00:00:00+08:00') }) + ' · ' : '')
        + T('report.generated', { time: formatDate(data.generated_at, true) });
    document.getElementById('rep-note').textContent = T('report.note.' + data.type);

    var head = document.getElementById('rep-head');
    head.replaceChildren();
    data.columns.forEach(function (column) {
        head.appendChild(createEl('th', isNumeric(column.format) ? 'text-end text-nowrap' : 'text-nowrap', T(column.label)));
    });

    renderTable('rep-body', data.rows, data.columns.map(function (column) {
        return { className: isNumeric(column.format) ? 'text-end' : '', render: function (row) { return formatCell(row[column.key], column.format); } };
    }));

    var foot = document.getElementById('rep-foot');
    foot.replaceChildren();
    data.columns.forEach(function (column, index) {
        var value = index === 0 ? T('field.total') : formatCell(data.totals[column.key], column.format);
        foot.appendChild(createEl('td', isNumeric(column.format) ? 'text-end' : '', data.rows.length ? value : ''));
    });

    var chartCard = document.getElementById('rep-chart-card');
    chartCard.classList.toggle('d-none', !data.chart || data.chart.length === 0);
    if (data.chart && data.chart.length) {
        var moneyColumn = data.columns.filter(function (column) { return column.format === 'money'; })[0];
        renderBarChart('rep-chart', data.chart.map(function (point) {
            return { label: point.format ? formatCell(point.label, point.format) : point.label, value: point.value };
        }), moneyColumn ? chartMoney(data.chart.length) : null);
    }
}

// Label nilai carta: banyak bar → tanpa "RM" dan sen supaya tidak bertindih
function chartMoney(count) {
    return count > 8 ? function (sen) { return String(Math.round(sen / 100)); } : formatRM;
}

// CSV: nilai wang dalam RM (2 titik perpuluhan) supaya boleh dijumlah dalam Excel
function exportCsv() {
    var data = reportState.data;
    if (!data) {
        showToast(T('report.run_first'), 'warning');
        return;
    }
    var csvValue = function (value, format) {
        if (format === 'money') {
            return (Number(value || 0) / 100).toFixed(2);
        }
        return isNumeric(format) ? value : formatCell(value, format);
    };
    var rows = [data.columns.map(function (column) { return T(column.label); })];
    data.rows.forEach(function (row) {
        rows.push(data.columns.map(function (column) { return csvValue(row[column.key], column.format); }));
    });
    rows.push(data.columns.map(function (column, index) {
        return index === 0 ? T('field.total') : csvValue(data.totals[column.key], column.format);
    }));
    var suffix = data.date_from ? '-' + data.date_from + '-' + data.date_to : '-' + data.generated_at.slice(0, 10);
    downloadCsv('laporan-' + data.type + suffix + '.csv', rows);
}

reportForm.addEventListener('submit', function (event) {
    event.preventDefault();
    runReport();
});

document.getElementById('rep-type').addEventListener('change', function () {
    var noDates = this.value === 'stock';
    document.getElementById('rep-from').disabled = noDates;
    document.getElementById('rep-to').disabled = noDates;
    runReport();
});

document.querySelectorAll('[data-range]').forEach(function (button) {
    button.addEventListener('click', function () {
        setRange(button.getAttribute('data-range'));
        runReport();
    });
});

document.getElementById('btn-csv').addEventListener('click', exportCsv);
document.getElementById('btn-print').addEventListener('click', function () {
    window.print();
});

setRange('this_month');
onCooperativeReady(runReport);
