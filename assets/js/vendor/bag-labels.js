// Label beg dengan QR (qrcode-generator, dijana dalam pelayar)

var labelState = { grade: queryParam('grade') || '', class_name: queryParam('class_name') || '', order_id: queryParam('order_id') || '',
    pickup_slot_id: queryParam('pickup_slot_id') || '' };

function qrDataUrl(text) {
    var qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr.createDataURL(4, 1);
}

function labelCard(label, schoolName) {
    var card = createEl('div', 'app-label');
    var img = createEl('img', 'app-label-qr');
    img.src = qrDataUrl(label.qr_token);
    img.alt = T('order.qr_alt', { code: label.short_code });
    card.appendChild(img);

    var text = createEl('div', 'min-w-0');
    text.appendChild(createEl('div', 'app-label-school text-truncate', schoolName));
    text.appendChild(createEl('div', 'app-label-child', label.child_name));
    text.appendChild(createEl('div', '', childClass(label)));
    text.appendChild(createEl('div', 'app-label-code', label.short_code));
    if (label.pickup_slot) {
        text.appendChild(createEl('div', 'small', slotText(label.pickup_slot)));
    }
    var meta = T('order.item_count', { n: label.item_count });
    if (label.payment_status !== 'paid') {
        meta += ' · ' + T('order.label_pay_cash');
    }
    text.appendChild(createEl('div', 'small' + (label.payment_status !== 'paid' ? ' fw-bold' : ''), meta));
    card.appendChild(text);
    return card;
}

function loadLabels() {
    Api.get('vendor/orders/labels', withCoop(labelState)).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var grid = document.getElementById('label-grid');
        grid.replaceChildren();
        res.data.labels.forEach(function (label) {
            grid.appendChild(labelCard(label, res.data.school_name));
        });
        document.getElementById('label-count').textContent = T('order.label_count', { n: res.data.labels.length });
        if (res.data.labels.length === 0) {
            grid.appendChild(createEl('p', 'text-body-secondary', T('common.no_data')));
        }
    });
}

document.getElementById('btn-print').addEventListener('click', function () {
    window.print();
});
['label-grade', 'label-class', 'label-slot'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () {
        labelState.grade = document.getElementById('label-grade').value;
        labelState.class_name = document.getElementById('label-class').value;
        labelState.pickup_slot_id = document.getElementById('label-slot').value;
        labelState.order_id = '';
        loadLabels();
    });
});

onCooperativeReady(function () {
    Promise.all([bindGradeClassFilters('label-grade', 'label-class'), bindSlotFilter('label-slot', labelState.pickup_slot_id)]).then(function (results) {
        labelState.pickup_slot_id = results[1];
        document.getElementById('label-grade').value = labelState.grade;
        fillClassOptions(document.getElementById('label-grade'), document.getElementById('label-class'));
        document.getElementById('label-class').value = labelState.class_name;
        labelState.class_name = document.getElementById('label-class').value;
        loadLabels();
    });
});
