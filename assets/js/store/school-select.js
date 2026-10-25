// Pilih sekolah: carian nama / daerah & tapis negeri

var schoolState = { q: queryParam('q') || '', state: '', page: 1, per_page: 50 };

function loadSchools() {
    var list = document.getElementById('school-list');
    loadingState(list);
    Api.get('store/schools/list', schoolState).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        document.getElementById('school-count').textContent = T('store.school.count', { n: res.data.total });
        if (res.data.items.length === 0) {
            emptyState(list, 'bi-building-x', T('store.school.none'));
            return;
        }
        list.replaceChildren();
        res.data.items.forEach(function (school) {
            list.appendChild(schoolListItem(school));
        });
    });
}

function schoolListItem(school) {
    var link = createEl('a', 'list-group-item list-group-item-action d-flex align-items-center gap-3 py-3');
    link.href = storeUrl('home', null, school.slug);
    link.appendChild(createIcon('bi-building fs-4 text-brand'));
    var text = createStack(school.school_name, [school.district, school.state].join(', '));
    text.classList.add('flex-grow-1', 'min-w-0');
    link.appendChild(text);
    link.appendChild(createIcon('bi-chevron-right text-body-secondary'));
    return link;
}

document.getElementById('school-q').value = schoolState.q;
bindListFilters('school-filters', schoolState, loadSchools);
loadSchools();
