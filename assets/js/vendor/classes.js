// Kelas sekolah — dikumpul ikut darjah/tingkatan

var classForm = document.getElementById('class-form');
var renameForm = document.getElementById('rename-form');
var classGrades = [];

function loadGrades() {
    return Api.get('vendor/grades/list', withCoop()).then(function (res) {
        classGrades = res.ok ? res.data : [];
        fillSelect('class-grade', classGrades.map(function (code) { return { code: code }; }), 'code', function (item) {
            return T('grade.' + item.code);
        }, false);
    });
}

function loadClasses() {
    Api.get('vendor/classes/list', withCoop()).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var box = document.getElementById('class-groups');
        box.replaceChildren();
        classGrades.forEach(function (code) {
            var rows = res.data.filter(function (row) { return row.grade_level_code === code; });
            box.appendChild(gradeCard(code, rows));
        });
    });
}

function gradeCard(code, rows) {
    var col = createEl('div', 'col-12 col-md-6');
    var card = createEl('div', 'card border-0 shadow-sm h-100');
    var header = createEl('div', 'card-header bg-body d-flex justify-content-between');
    header.appendChild(createEl('span', 'fw-semibold', T('grade.' + code)));
    header.appendChild(createEl('span', 'badge text-bg-light border', T('class.count', { n: rows.length })));
    card.appendChild(header);

    var list = createEl('ul', 'list-group list-group-flush');
    if (rows.length === 0) {
        list.appendChild(createEl('li', 'list-group-item small text-body-secondary', T('class.none')));
    }
    rows.forEach(function (row) {
        var item = createEl('li', 'list-group-item d-flex justify-content-between align-items-center');
        item.appendChild(createEl('span', '', T('class.full_name', { grade: T('grade.' + code), name: row.class_name })));
        var actions = createEl('div', 'd-flex gap-1');
        actions.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
            fillForm(renameForm, row);
            showModal('rename-modal');
        }, T('class.rename')));
        actions.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
            deleteClass(row, code);
        }, T('btn.delete')));
        item.appendChild(actions);
        list.appendChild(item);
    });
    card.appendChild(list);
    col.appendChild(card);
    return col;
}

function deleteClass(row, code) {
    var name = T('class.full_name', { grade: T('grade.' + code), name: row.class_name });
    confirmDialog(T('class.delete_confirm', { name: name }), { okText: T('btn.delete'), okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/classes/delete', withCoop({ id: row.id })).then(function (res) {
            showToast(res.ok ? T('common.deleted') : res.error, res.ok ? 'success' : 'danger');
            loadClasses();
        });
    });
}

bindForm(classForm, 'vendor/classes/add', function (data) {
    document.getElementById('class-names').value = '';
    classForm.classList.remove('was-validated');
    showToast(T('class.added', { n: data.added }), 'success');
    loadClasses();
}, withCoop);

bindForm(renameForm, 'vendor/classes/update', function () {
    hideModal('rename-modal');
    showToast(T('common.saved'), 'success');
    loadClasses();
}, function (payload) {
    payload.id = Number(payload.id);
    return withCoop(payload);
});

onCooperativeReady(function () {
    loadGrades().then(loadClasses);
});
