// Picking list ikut kelas (cetak)

var pickState = { grade: queryParam('grade') || '', class_name: queryParam('class_name') || '', status: 'paid,unpaid_cash,preparing' };

function itemName(item) {
    return item.variant_label ? item.product_name + ' — ' + item.variant_label : item.product_name;
}

function loadPickingList() {
    Api.get('vendor/orders/picking-list', withCoop(pickState)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var data = res.data;
        document.getElementById('pick-school').textContent = data.school_name;
        document.getElementById('pick-scope').textContent = document.getElementById('pick-status').selectedOptions[0].textContent
            + (pickState.grade ? ' · ' + T('grade.' + pickState.grade) : '') + (pickState.class_name ? ' ' + pickState.class_name : '');
        document.getElementById('pick-meta').textContent = T('order.pick_meta', { n: data.order_count, date: formatDate(data.generated_at, true) });

        var box = document.getElementById('pick-groups');
        box.replaceChildren();
        if (data.groups.length === 0) {
            box.appendChild(createEl('p', 'text-body-secondary', T('common.no_data')));
        }
        data.groups.forEach(function (group, index) {
            box.appendChild(pickGroup(group, index));
        });

        renderTable('pick-totals', data.totals, [
            { render: itemName },
            { key: 'sku', className: 'small' },
            { key: 'qty', className: 'text-end fw-semibold' },
            { className: 'text-center', render: function () { return createEl('span', 'app-pick-box'); } }
        ]);
    });
}

// Satu bahagian setiap kelas: murid → barang
function pickGroup(group, index) {
    var section = createEl('section', 'app-pick-group mb-4' + (index > 0 ? ' app-print-break' : ''));
    section.appendChild(createEl('h2', 'h5 border-bottom pb-1', T('class.full_name', { grade: T('grade.' + group.grade_level_code), name: group.class_name })
        + ' — ' + T('order.child_count', { n: group.children.length })));
    var table = createEl('table', 'table table-sm table-bordered align-top');
    var head = createEl('thead');
    var row = createEl('tr');
    [T('order.child'), T('order.short_code'), T('order.items_to_pick'), T('order.picked')].forEach(function (label) {
        row.appendChild(createEl('th', '', label));
    });
    head.appendChild(row);
    table.appendChild(head);
    var body = createEl('tbody');
    group.children.forEach(function (child) {
        var tr = createEl('tr');
        tr.appendChild(createEl('td', 'fw-semibold', child.child_name));
        tr.appendChild(createEl('td', 'font-monospace fw-bold', child.short_code));
        var items = createEl('td');
        child.items.forEach(function (item) {
            items.appendChild(createEl('div', '', item.qty + ' × ' + itemName(item)));
        });
        tr.appendChild(items);
        var check = createEl('td', 'text-center');
        check.appendChild(createEl('span', 'app-pick-box'));
        tr.appendChild(check);
        body.appendChild(tr);
    });
    table.appendChild(body);
    section.appendChild(table);
    return section;
}

document.getElementById('btn-print').addEventListener('click', function () {
    window.print();
});
document.getElementById('pick-status').addEventListener('change', function (event) {
    pickState.status = event.target.value;
    loadPickingList();
});
['pick-grade', 'pick-class'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () {
        pickState.grade = document.getElementById('pick-grade').value;
        pickState.class_name = document.getElementById('pick-class').value;
        loadPickingList();
    });
});

onCooperativeReady(function () {
    bindGradeClassFilters('pick-grade', 'pick-class').then(function () {
        document.getElementById('pick-grade').value = pickState.grade;
        fillClassOptions(document.getElementById('pick-grade'), document.getElementById('pick-class'));
        document.getElementById('pick-class').value = pickState.class_name;
        pickState.class_name = document.getElementById('pick-class').value;
        loadPickingList();
    });
});
