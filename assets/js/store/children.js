// Anak saya — senarai, tambah / sunting, naik darjah, buang

var childRows = [];
var schoolOptions = [];

function childCard(child) {
    var col = createEl('div', 'col-12 col-md-6 col-xl-4');
    var card = createEl('div', 'card h-100 border-0 shadow-sm');
    var body = createEl('div', 'card-body');
    var head = createEl('div', 'd-flex align-items-start gap-2 mb-2');
    head.appendChild(createIcon('bi-person-badge fs-3 text-brand'));
    var text = createStack(child.full_name, childClassLine(child) + ' · ' + child.school_year);
    text.classList.add('flex-grow-1', 'min-w-0');
    head.appendChild(text);
    body.appendChild(head);
    body.appendChild(createEl('div', 'small text-body-secondary mb-3', child.school_name));

    if (child.promote_to) {
        var promote = createEl('div', 'alert alert-info small py-2 d-flex align-items-center gap-2');
        promote.appendChild(createEl('span', 'flex-grow-1', T('store.children.promote_hint', {
            grade: gradeLabel(child.promote_to.grade_level_code), year: child.promote_to.school_year })));
        promote.appendChild(createButton(T('store.children.promote'), 'btn-info', 'bi-arrow-up-circle', function () { promoteChild(child); }));
        body.appendChild(promote);
    }

    var actions = createEl('div', 'd-flex flex-wrap gap-2');
    if (child.school_slug === Store.slug) {
        actions.appendChild(createIconLinkText(storeUrl('kit', { child: child.id }), 'bi-backpack', T('store.home.buy_kit'), 'btn-brand'));
    } else {
        actions.appendChild(createIconLinkText(storeUrl('home', null, child.school_slug), 'bi-shop', T('store.children.open_store'), 'btn-outline-brand'));
    }
    actions.appendChild(createButton(T('btn.edit'), 'btn-outline-secondary', 'bi-pencil', function () { openChildForm(child); }));
    actions.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () { removeChild(child); }, T('store.children.remove')));
    body.appendChild(actions);
    card.appendChild(body);
    col.appendChild(card);
    return col;
}

function loadChildren() {
    var list = document.getElementById('child-list');
    loadingState(list);
    storeGet('store/children/list').then(function (res) {
        if (!res.ok) {
            return;
        }
        childRows = res.data;
        if (childRows.length === 0) {
            emptyState(list, 'bi-people', T('store.children.none'));
            return;
        }
        list.replaceChildren();
        childRows.forEach(function (child) {
            list.appendChild(childCard(child));
        });
    });
}

// Darjah ikut jenis sekolah + cadangan kelas (datalist)
function loadSchoolGrades(slug, selectedGrade) {
    var gradeSelect = document.getElementById('child-grade');
    if (!slug) {
        fillSelect(gradeSelect, [], 'code', function () { return ''; }, true);
        return Promise.resolve();
    }
    return Api.get('store/school', { slug: slug }).then(function (res) {
        if (!res.ok) {
            return;
        }
        fillSelect(gradeSelect, res.data.grades.map(function (code) { return { code: code }; }), 'code', function (row) { return gradeLabel(row.code); }, true);
        gradeSelect.value = selectedGrade || '';
        loadClassOptions();
    });
}

function loadClassOptions() {
    var slug = document.getElementById('child-school').value;
    var grade = document.getElementById('child-grade').value;
    var list = document.getElementById('child-class-options');
    list.replaceChildren();
    if (!slug || !grade) {
        return;
    }
    Api.get('store/classes', { slug: slug, grade: grade }).then(function (res) {
        (res.ok ? res.data : []).forEach(function (row) {
            var option = createEl('option');
            option.value = row.class_name;
            list.appendChild(option);
        });
    });
}

function fillYears() {
    var year = new Date().getFullYear();
    fillSelect('child-year', [{ y: year }, { y: year + 1 }], 'y', function (row) { return String(row.y); });
}

function openChildForm(child) {
    var form = document.getElementById('child-form');
    resetForm(form);
    document.getElementById('child-modal-title').textContent = T(child ? 'store.children.edit' : 'store.children.add');
    var data = child || { school_slug: Store.slug || '', school_year: new Date().getFullYear() };
    fillForm(form, data);
    loadSchoolGrades(data.school_slug, data.grade_level_code);
    showModal('child-modal');
}

function promoteChild(child) {
    confirmDialog(T('store.children.promote_confirm', { name: child.full_name, grade: gradeLabel(child.promote_to.grade_level_code), year: child.promote_to.school_year }))
        .then(function (yes) {
            if (!yes) {
                return;
            }
            storePost('store/children/promote', { id: child.id }).then(function (res) {
                if (!res.ok) {
                    showToast(res.error, 'danger');
                    return;
                }
                showToast(T('store.children.promoted'));
                loadChildren();
            });
        });
}

function removeChild(child) {
    confirmDialog(T('store.children.remove_confirm', { name: child.full_name }), { okClass: 'btn-danger', okText: T('store.children.remove') })
        .then(function (yes) {
            if (!yes) {
                return;
            }
            storePost('store/children/remove', { id: child.id }).then(function (res) {
                if (res.ok) {
                    showToast(T('store.children.removed'));
                    loadChildren();
                }
            });
        });
}

function loadSchoolOptions() {
    return Api.get('store/schools/list', { per_page: 100 }).then(function (res) {
        schoolOptions = res.ok ? res.data.items : [];
        fillSelect('child-school', schoolOptions, 'slug', function (school) { return school.school_name + ' (' + school.state + ')'; }, true);
    });
}

onStoreReady(function () {
    if (!storeRequireLogin()) {
        return;
    }
    fillYears();
    document.getElementById('child-school').addEventListener('change', function (event) {
        loadSchoolGrades(event.target.value, '');
    });
    document.getElementById('child-grade').addEventListener('change', loadClassOptions);
    document.getElementById('child-add').addEventListener('click', function () { openChildForm(null); });
    bindForm('child-form', 'store/children/save', function () {
        hideModal('child-modal');
        showToast(T('common.saved'));
        loadChildren();
    });
    loadSchoolOptions().then(function () {
        if (queryParam('add')) {
            openChildForm(null);
        }
    });
    loadChildren();
});
