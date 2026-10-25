// Papan pemuka vendor — dimuat semula setiap kali koperasi ditukar

function productLabel(row) {
    return row.variant_label ? row.product_name + ' — ' + row.variant_label : row.product_name;
}

function weekdayLabel(isoDate) {
    var locales = { ms: 'ms-MY', en: 'en-MY', zh: 'zh-CN', ta: 'ta-MY' };
    return new Intl.DateTimeFormat(locales[currentLang()] || 'ms-MY', { weekday: 'short', timeZone: 'Asia/Kuala_Lumpur' })
        .format(new Date(isoDate + 'T12:00:00+08:00'));
}

function renderVendorDashboard(data) {
    document.getElementById('stat-sales').textContent = formatRM(data.sales_today);
    document.getElementById('stat-sales-sub').textContent = T('vendor_dashboard.transactions', { n: data.sales_today_count });
    document.getElementById('stat-prepare').textContent = data.orders_to_prepare;
    document.getElementById('stat-prepare-sub').textContent = T('vendor_dashboard.orders_unit');
    document.getElementById('stat-ready').textContent = data.orders_ready;
    document.getElementById('stat-ready-sub').textContent = T('vendor_dashboard.orders_unit');
    document.getElementById('stat-low').textContent = data.low_stock_count;
    document.getElementById('stat-low-sub').textContent = T('vendor_dashboard.items_unit');

    renderBarChart('sales-chart', data.sales_7days.map(function (day) {
        return { label: weekdayLabel(day.date), value: day.amount };
    }), formatRM);

    renderTable('low-stock-body', data.low_stock, [
        { render: function (row) { return createStack(productLabel(row), row.sku); } },
        { className: 'text-end', render: function (row) {
            return createEl('span', 'fw-semibold ' + (row.qty_available <= 0 ? 'text-danger' : 'text-warning-emphasis'), row.qty_available);
        } },
        { key: 'min_stock', className: 'text-end' },
        { className: 'text-end', render: function () {
            return createLink(T('po.create'), pageUrl('vendor/purchase-order.php'), 'btn btn-sm btn-outline-secondary');
        } }
    ]);

    var list = document.getElementById('announcement-list');
    list.replaceChildren();
    if (data.announcements.length === 0) {
        list.appendChild(createEl('li', 'list-group-item text-body-secondary small', T('common.no_data')));
    }
    data.announcements.forEach(function (item) {
        var li = createEl('li', 'list-group-item');
        li.appendChild(createEl('div', 'fw-semibold small', item.title));
        li.appendChild(createEl('div', 'small', item.body));
        li.appendChild(createEl('div', 'small text-body-secondary', formatDate(item.published_at)));
        list.appendChild(li);
    });
}

onCooperativeReady(function (coop) {
    document.getElementById('current-coop').textContent = T('common.current_cooperative', { name: coop.school_name });
    tableLoading('low-stock-body', 4);
    Api.get('vendor/dashboard/summary', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderVendorDashboard(res.data);
    });
});
