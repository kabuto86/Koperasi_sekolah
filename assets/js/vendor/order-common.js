// Fungsi dikongsi oleh halaman pesanan, picking list, label & serah barang

// "Aisyah binti Rahman · Darjah 4 Bestari"
function childLine(child) {
    return child.child_name + ' · ' + T('class.full_name', { grade: T('grade.' + child.grade_level_code), name: child.class_name });
}

function childClass(child) {
    return T('class.full_name', { grade: T('grade.' + child.grade_level_code), name: child.class_name });
}

// Lencana status bayaran (berasingan dari status pesanan: tunai boleh "Sedia diambil" tetapi belum dibayar)
function paymentBadge(order) {
    var colors = { paid: 'text-bg-success', unpaid: 'text-bg-warning', pending: 'text-bg-light border', none: 'text-bg-secondary' };
    var wrap = createEl('div');
    wrap.appendChild(createEl('span', 'badge ' + (colors[order.payment_status] || 'text-bg-secondary'), T('payment.status.' + order.payment_status)));
    wrap.appendChild(createEl('div', 'small text-body-secondary', T('payment.method.' + order.payment_method)));
    return wrap;
}

// Isi pilihan darjah & kelas (kelas bergantung pada darjah dipilih).
// Dipanggil semula setiap kali koperasi ditukar; pendengar hanya dipasang sekali.
var gradeClassOptions = [];

function fillClassOptions(gradeSelect, classSelect) {
    var grade = gradeSelect.value;
    var current = classSelect.value;
    var names = gradeClassOptions.filter(function (row) { return !grade || row.grade_level_code === grade; }).map(function (row) {
        return row.class_name;
    }).filter(function (name, index, all) { return all.indexOf(name) === index; });
    fillSelect(classSelect, names.map(function (name) { return { name: name }; }), 'name', function (item) { return item.name; }, true);
    classSelect.value = names.indexOf(current) !== -1 ? current : '';
}

function bindGradeClassFilters(gradeId, classId) {
    var gradeSelect = document.getElementById(gradeId);
    var classSelect = document.getElementById(classId);

    if (!gradeSelect.hasAttribute('data-bound')) {
        gradeSelect.setAttribute('data-bound', '1');
        gradeSelect.addEventListener('change', function () {
            fillClassOptions(gradeSelect, classSelect);
        });
    }

    return Promise.all([Api.get('vendor/grades/list', withCoop()), Api.get('vendor/orders/class-options', withCoop())]).then(function (results) {
        var current = gradeSelect.value;
        fillSelect(gradeSelect, (results[0].ok ? results[0].data : []).map(function (code) { return { code: code }; }), 'code', function (item) {
            return T('grade.' + item.code);
        }, true);
        gradeSelect.value = Array.prototype.some.call(gradeSelect.options, function (option) { return option.value === current; }) ? current : '';
        gradeClassOptions = results[1].ok ? results[1].data : [];
        fillClassOptions(gradeSelect, classSelect);
    });
}

// "12/01/2027 · 08:00–10:00 · Darjah 4"
function slotText(slot) {
    return formatDate(slot.slot_date) + ' · ' + slot.time_start + '–' + slot.time_end
        + ' · ' + (slot.grade_level_code ? T('grade.' + slot.grade_level_code) : T('store.slot.all_grades'));
}

// Tapisan slot ambil (modul pembukaan sekolah). Disembunyikan jika koperasi tiada slot.
// Bekas medan mesti ada kelas .js-slot-filter
function bindSlotFilter(selectId, current) {
    var select = document.getElementById(selectId);
    return Api.get('vendor/orders/slot-options', withCoop()).then(function (res) {
        var slots = res.ok ? res.data : [];
        fillSelect(select, slots, 'id', slotText, true);
        select.value = slots.some(function (slot) { return String(slot.id) === String(current); }) ? String(current) : '';
        select.closest('.js-slot-filter').classList.toggle('d-none', slots.length === 0);
        return select.value;
    });
}

// Salin tapisan semasa ke pautan picking list / label
function filterQuery(state, keys) {
    var params = new URLSearchParams();
    keys.forEach(function (key) {
        if (state[key]) {
            params.set(key, state[key]);
        }
    });
    var text = params.toString();
    return text ? '?' + text : '';
}
