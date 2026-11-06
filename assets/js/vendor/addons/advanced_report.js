// Modul laporan lanjutan — perbandingan tahun & analisis kit untuk koperasi semasa.
// Jawapan API sama bentuk seperti vendor/reports/run: { columns: [{ key, label, format }], rows, totals, chart, year, compare_year }

var advForm = document.getElementById('adv-form');
var advState = { data: null };

// Label lajur: {year} / {prev} diganti dengan tahun laporan
function advLabel(column, data) {
    return T(column.label, { year: data.year, prev: data.compare_year });
}

function advCell(value, format) {
    if (value === null || value === undefined || value === '') {
        return format === 'percent' ? '—' : '';
    }
    switch (format) {
        case 'money': return formatRM(value);
        case 'percent': return (value > 0 ? '+' : '') + value + '%';
        case 'month': return formatMonth(value);
        case 'grade': return T('grade.' + value);
        default: return String(value);
    }
}

function advNumeric(format) {
    return ['money', 'percent', 'int'].indexOf(format) !== -1;
}

function runAdvanced() {
    document.getElementById('adv-body').replaceChildren();
    Api.get('vendor/advanced-report/run', withCoop(formToObject(advForm))).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        advState.data = res.data;
        renderAdvanced(res.data);
    });
}

function renderAdvanced(data) {
    document.getElementById('adv-result').classList.remove('d-none');
    document.getElementById('adv-title').textContent = T('advreport.type.' + data.type) + ' — ' + data.school_name;
    document.getElementById('adv-meta').textContent = T('advreport.meta', { year: data.year, prev: data.compare_year })
        + ' · ' + T('report.generated', { time: formatDate(data.generated_at, true) });
    document.getElementById('adv-note').textContent = T('advreport.note.' + data.type);

    var head = document.getElementById('adv-head');
    head.replaceChildren();
    data.columns.forEach(function (column) {
        head.appendChild(createEl('th', advNumeric(column.format) ? 'text-end text-nowrap' : 'text-nowrap', advLabel(column, data)));
    });
    renderTable('adv-body', data.rows, data.columns.map(function (column) {
        return {
            className: advNumeric(column.format) ? 'text-end' : '',
            render: function (row) {
                var text = advCell(row[column.key], column.format);
                if (column.key === 'change' && row.change !== null) {
                    return createEl('span', row.change < 0 ? 'text-danger' : 'text-success', text);
                }
                return text;
            }
        };
    }));

    var foot = document.getElementById('adv-foot');
    foot.replaceChildren();
    data.columns.forEach(function (column, index) {
        var value = data.totals[column.key];
        var text = index === 0 ? T('field.total') : (value === undefined ? '' : advCell(value, column.format));
        foot.appendChild(createEl('td', advNumeric(column.format) ? 'text-end' : '', data.rows.length ? text : ''));
    });

    // Carta disembunyikan bila tiada titik atau semua nilai sifar
    var hasChart = Boolean(data.chart && data.chart.some(function (point) { return point.value > 0; }));
    document.getElementById('adv-chart-card').classList.toggle('d-none', !hasChart);
    if (hasChart) {
        var percent = data.type === 'kit_items';
        renderBarChart('adv-chart', data.chart.map(function (point) {
            return { label: point.format ? advCell(point.label, point.format) : point.label, value: point.value };
        }), percent ? function (value) { return value + '%'; } : (data.chart.length > 8 ? function (sen) { return String(Math.round(sen / 100)); } : formatRM));
    }
}

// CSV: wang dalam RM (2 titik perpuluhan)
function exportAdvancedCsv() {
    var data = advState.data;
    if (!data) {
        showToast(T('report.run_first'), 'warning');
        return;
    }
    var csvValue = function (value, format) {
        if (format === 'money') {
            return (Number(value || 0) / 100).toFixed(2);
        }
        if (format === 'percent' || format === 'int') {
            return value === null || value === undefined ? '' : value;
        }
        return advCell(value, format);
    };
    var rows = [data.columns.map(function (column) { return advLabel(column, data); })];
    data.rows.forEach(function (row) {
        rows.push(data.columns.map(function (column) { return csvValue(row[column.key], column.format); }));
    });
    downloadCsv('laporan-lanjutan-' + data.type + '-' + data.year + '.csv', rows);
}

advForm.addEventListener('submit', function (event) {
    event.preventDefault();
    runAdvanced();
});
document.getElementById('adv-type').addEventListener('change', runAdvanced);
document.getElementById('adv-year').addEventListener('change', runAdvanced);
document.getElementById('btn-csv').addEventListener('click', exportAdvancedCsv);
document.getElementById('btn-print').addEventListener('click', function () {
    window.print();
});

onModuleReady(runAdvanced);
