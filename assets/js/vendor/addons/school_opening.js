// Modul pembukaan sekolah — musim pre-order & slot ambil koperasi semasa

var openingState = { seasons: [], grades: [], selectedId: null, season: null };
var seasonForm = document.getElementById('season-form');
var slotForm = document.getElementById('slot-form');

function seasonItem(season) {
    var item = createEl('button', 'list-group-item list-group-item-action d-flex gap-2 align-items-start' + (season.id === openingState.selectedId ? ' active' : ''));
    item.type = 'button';
    item.setAttribute('aria-pressed', season.id === openingState.selectedId ? 'true' : 'false');
    var body = createEl('div', 'flex-grow-1 min-w-0');
    body.appendChild(createEl('div', 'fw-semibold', T('opening.season_name', { year: season.school_year })));
    body.appendChild(createEl('div', 'small', T('opening.season_dates', { from: formatDate(season.opens_at), to: formatDate(season.closes_at) })));
    body.appendChild(createEl('div', 'small', T('opening.slot_summary', { slots: season.slot_count, booked: season.booked, capacity: season.capacity })));
    item.appendChild(body);
    item.appendChild(statusBadge(season.state, 'opening.state'));
    item.addEventListener('click', function () {
        selectSeason(season.id);
    });
    return item;
}

function renderSeasons() {
    var list = document.getElementById('season-list');
    list.replaceChildren();
    if (openingState.seasons.length === 0) {
        list.appendChild(createEl('div', 'list-group-item text-body-secondary small', T('opening.no_seasons')));
    }
    openingState.seasons.forEach(function (season) {
        list.appendChild(seasonItem(season));
    });
}

function loadSeasons() {
    return Api.get('vendor/school-opening/seasons', withCoop()).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        openingState.seasons = res.data.items;
        openingState.grades = res.data.grades;
        var stillThere = openingState.seasons.some(function (season) { return season.id === openingState.selectedId; });
        if (!stillThere) {
            var open = openingState.seasons.filter(function (season) { return season.state === 'open'; })[0];
            openingState.selectedId = (open || openingState.seasons[0] || {}).id || null;
        }
        renderSeasons();
        loadSlots();
    });
}

function selectSeason(id) {
    openingState.selectedId = id;
    renderSeasons();
    loadSlots();
}

function gradeText(code) {
    return code ? T('grade.' + code) : T('opening.all_grades');
}

function bookedCell(slot) {
    var wrap = createEl('div', 'small');
    wrap.appendChild(createEl('div', '', T('opening.booked_of', { booked: slot.booked, capacity: slot.capacity })));
    var bar = createEl('progress', 'app-progress' + (slot.remaining === 0 ? ' is-full' : ''));
    bar.max = slot.capacity;
    bar.value = slot.booked;
    bar.setAttribute('aria-label', T('opening.booked_of', { booked: slot.booked, capacity: slot.capacity }));
    wrap.appendChild(bar);
    return wrap;
}

function renderSlots(data) {
    var season = data ? data.season : null;
    openingState.season = season;
    document.getElementById('btn-edit-season').disabled = !season;
    document.getElementById('btn-add-slot').disabled = !season;
    document.getElementById('slot-title').textContent = season ? T('opening.slots_for', { year: season.school_year }) : T('opening.slots');
    document.getElementById('slot-summary').textContent = season
        ? T('opening.pickup_from', { date: formatDate(season.pickup_starts_on) }) + (season.note ? ' · ' + season.note : '')
        : '';
    renderTable('slots-body', data ? data.items : [], [
        { className: 'text-nowrap', render: function (row) { return formatDate(row.slot_date); } },
        { className: 'text-nowrap', render: function (row) { return row.time_start + ' – ' + row.time_end; } },
        { render: function (row) { return gradeText(row.grade_level_code); } },
        { render: bookedCell },
        { className: 'text-end text-nowrap', render: function (row) {
            var wrap = createEl('div', 'd-inline-flex gap-1');
            wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                openSlotForm(row);
            }, T('btn.edit')));
            if (row.booked === 0) {
                wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
                    deleteSlot(row);
                }, T('btn.delete')));
            }
            return wrap;
        } }
    ]);
}

function loadSlots() {
    if (!openingState.selectedId) {
        renderSlots(null);
        return;
    }
    tableLoading('slots-body', 5);
    Api.get('vendor/school-opening/slots', withCoop({ season_id: openingState.selectedId })).then(function (res) {
        if (moduleResponse(res)) {
            renderSlots(res.data);
        }
    });
}

// ---------- Musim ----------

function openSeasonForm(season) {
    document.getElementById('season-modal-title').textContent = season ? T('opening.edit_season') : T('opening.add_season');
    var year = new Date().getFullYear() + 1;
    fillForm(seasonForm, season || { school_year: year, opens_at: localDay(new Date()), closes_at: year - 1 + '-12-15', pickup_starts_on: year + '-01-02' });
    document.getElementById('season-delete').classList.toggle('d-none', !season);
    showModal('season-modal');
}

document.getElementById('season-delete').addEventListener('click', function () {
    var season = openingState.season;
    confirmDialog(T('opening.delete_season_confirm', { year: season.school_year }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/school-opening/season-delete', withCoop({ id: season.id })).then(function (res) {
            if (moduleResponse(res)) {
                hideModal('season-modal');
                showToast(T('common.deleted'), 'success');
                openingState.selectedId = null;
                loadSeasons();
            }
        });
    });
});

bindForm(seasonForm, 'vendor/school-opening/season-save', function (season) {
    hideModal('season-modal');
    showToast(T('common.saved'), 'success');
    openingState.selectedId = season.id;
    loadSeasons();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return withCoop(payload);
});

// ---------- Slot ----------

// Kotak semak darjah. Tambah: beberapa darjah = satu slot setiap darjah. Sunting: satu darjah sahaja
function renderGradeChecks(selected, single) {
    var box = document.getElementById('slot-grades');
    box.replaceChildren();
    openingState.grades.forEach(function (code) {
        var id = 'slot-grade-' + code;
        var input = createEl('input', 'btn-check');
        input.type = 'checkbox';
        input.id = id;
        input.name = 'grades';
        input.value = code;
        input.setAttribute('data-multi', '');
        input.checked = selected.indexOf(code) !== -1;
        input.addEventListener('change', function () {
            if (single && input.checked) {
                box.querySelectorAll('input').forEach(function (other) {
                    other.checked = other === input;
                });
            }
        });
        var label = createEl('label', 'btn btn-sm btn-outline-brand', T('grade.' + code));
        label.setAttribute('for', id);
        box.appendChild(input);
        box.appendChild(label);
    });
    document.getElementById('slot-grades-help').textContent = single ? T('opening.grade_help_edit') : T('opening.grade_help_add');
}

function openSlotForm(slot) {
    document.getElementById('slot-modal-title').textContent = slot ? T('opening.edit_slot') : T('opening.add_slot');
    fillForm(slotForm, slot || { slot_date: openingState.season.pickup_starts_on, time_start: '08:00', time_end: '10:00', capacity: 30 });
    renderGradeChecks(slot && slot.grade_level_code ? [slot.grade_level_code] : [], Boolean(slot));
    showModal('slot-modal');
}

function deleteSlot(slot) {
    confirmDialog(T('opening.delete_slot_confirm', { date: formatDate(slot.slot_date), time: slot.time_start }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/school-opening/slot-delete', withCoop({ id: slot.id })).then(function (res) {
            if (moduleResponse(res)) {
                showToast(T('common.deleted'), 'success');
                loadSeasons();
            }
        });
    });
}

bindForm(slotForm, 'vendor/school-opening/slot-save', function (data) {
    hideModal('slot-modal');
    showToast(data.created > 1 ? T('opening.slots_created', { n: data.created }) : T('common.saved'), 'success');
    loadSeasons();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    payload.season_id = openingState.selectedId;
    payload.grades = payload.grades || [];
    return withCoop(payload);
});

document.getElementById('btn-add-season').addEventListener('click', function () {
    openSeasonForm(null);
});
document.getElementById('btn-edit-season').addEventListener('click', function () {
    openSeasonForm(openingState.season);
});
document.getElementById('btn-add-slot').addEventListener('click', function () {
    openSlotForm(null);
});

onModuleReady(function () {
    openingState.selectedId = null;
    loadSeasons();
});
