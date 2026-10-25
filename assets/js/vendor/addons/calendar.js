// Modul kalendar cuti — kalendar bulanan koperasi semasa

var calState = { year: new Date().getFullYear(), month: new Date().getMonth(), events: [], today: localDay(new Date()) };
var eventForm = document.getElementById('event-form');
var CAL_LOCALES = { ms: 'ms-MY', en: 'en-MY', zh: 'zh-CN', ta: 'ta-MY' };

function monthStart() {
    return localDay(new Date(calState.year, calState.month, 1));
}

function monthEnd() {
    return localDay(new Date(calState.year, calState.month + 1, 0));
}

function eventsOn(day) {
    return calState.events.filter(function (event) {
        return event.starts_on <= day && event.ends_on >= day;
    });
}

function eventButton(event, compact) {
    var button = createEl('button', 'btn btn-sm app-cal-event app-cal-' + event.event_type + (compact ? ' text-truncate' : ''));
    button.type = 'button';
    if (event.blocks_orders) {
        button.appendChild(createIcon('bi-slash-circle me-1'));
    }
    button.appendChild(document.createTextNode(event.title));
    button.title = event.title + ' · ' + dateRangeText(event.starts_on, event.ends_on);
    button.addEventListener('click', function () {
        openEventForm(event);
    });
    return button;
}

function dayCell(date) {
    var day = localDay(date);
    var inMonth = date.getMonth() === calState.month;
    var cell = createEl('td', 'app-cal-day' + (inMonth ? '' : ' app-cal-outside') + (day === calState.today ? ' app-cal-today' : ''));
    var head = createEl('button', 'btn btn-link btn-sm p-0 text-decoration-none app-cal-number', String(date.getDate()));
    head.type = 'button';
    head.setAttribute('aria-label', T('calendar.add_on', { date: formatDate(day) }));
    head.addEventListener('click', function () {
        openEventForm(null, day);
    });
    cell.appendChild(head);
    var events = eventsOn(day);
    if (events.some(function (event) { return event.blocks_orders; })) {
        cell.classList.add('app-cal-blocked');
    }
    var list = createEl('div', 'd-grid gap-1 mt-1');
    events.forEach(function (event) {
        list.appendChild(eventButton(event, true));
    });
    cell.appendChild(list);
    return cell;
}

function renderGrid() {
    var grid = document.getElementById('cal-grid');
    grid.replaceChildren();
    var first = new Date(calState.year, calState.month, 1);
    var offset = (first.getDay() + 6) % 7;
    var cursor = new Date(calState.year, calState.month, 1 - offset);
    do {
        var row = createEl('tr');
        for (var i = 0; i < 7; i++) {
            row.appendChild(dayCell(cursor));
            cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + 1);
        }
        grid.appendChild(row);
    } while (cursor.getMonth() === calState.month);
    var title = new Intl.DateTimeFormat(CAL_LOCALES[currentLang()] || 'ms-MY', { month: 'long', year: 'numeric' }).format(first);
    document.getElementById('cal-month').textContent = title;
}

function renderList() {
    var list = document.getElementById('cal-list');
    list.replaceChildren();
    if (calState.events.length === 0) {
        list.appendChild(createEl('li', 'list-group-item text-body-secondary small', T('calendar.no_events')));
        return;
    }
    calState.events.forEach(function (event) {
        var item = createEl('li', 'list-group-item d-flex gap-2 align-items-start');
        item.appendChild(createEl('span', 'app-cal-dot mt-2 app-cal-' + event.event_type));
        var body = createEl('div', 'flex-grow-1 min-w-0');
        body.appendChild(createEl('div', 'fw-semibold', event.title));
        body.appendChild(createEl('div', 'small text-body-secondary', dateRangeText(event.starts_on, event.ends_on) + ' · ' + T('calendar.type.' + event.event_type)));
        if (event.blocks_orders) {
            body.appendChild(createEl('span', 'badge text-bg-danger', T('calendar.orders_closed')));
        }
        item.appendChild(body);
        item.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
            openEventForm(event);
        }, T('btn.edit')));
        list.appendChild(item);
    });
}

function renderToday(blocked) {
    var box = document.getElementById('cal-today');
    box.classList.remove('d-none', 'alert-success', 'alert-danger');
    box.classList.add(blocked ? 'alert-danger' : 'alert-success');
    document.getElementById('cal-today-icon').className = 'bi fs-4 ' + (blocked ? 'bi-door-closed' : 'bi-door-open');
    document.getElementById('cal-today-text').textContent = blocked
        ? T('calendar.today_blocked', { title: blocked.title, date: formatDate(blocked.ends_on) })
        : T('calendar.today_open');
}

function loadCalendar() {
    Api.get('vendor/calendar/list', withCoop({ from: monthStart(), to: monthEnd() })).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        calState.events = res.data.items;
        calState.today = res.data.today;
        renderGrid();
        renderList();
        renderToday(res.data.today_blocked);
    });
}

function changeMonth(step) {
    var date = new Date(calState.year, calState.month + step, 1);
    calState.year = date.getFullYear();
    calState.month = date.getMonth();
    loadCalendar();
}

function openEventForm(event, day) {
    document.getElementById('event-modal-title').textContent = event ? T('calendar.edit') : T('calendar.add');
    var start = day || calState.today;
    fillForm(eventForm, event || { event_type: 'coop_closed', starts_on: start, ends_on: start, blocks_orders: true });
    document.getElementById('event-delete').classList.toggle('d-none', !event);
    showModal('event-modal');
}

// Jenis "Koperasi tutup" biasanya menyekat tempahan; jenis lain tidak
document.getElementById('event-type').addEventListener('change', function () {
    if (!eventForm.elements.id.value) {
        document.getElementById('event-blocks').checked = this.value === 'coop_closed';
    }
});

document.getElementById('event-delete').addEventListener('click', function () {
    var id = Number(eventForm.elements.id.value);
    confirmDialog(T('calendar.delete_confirm', { title: eventForm.elements.title.value }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/calendar/delete', withCoop({ id: id })).then(function (res) {
            if (moduleResponse(res)) {
                hideModal('event-modal');
                showToast(T('common.deleted'), 'success');
                loadCalendar();
            }
        });
    });
});

document.getElementById('cal-prev').addEventListener('click', function () { changeMonth(-1); });
document.getElementById('cal-next').addEventListener('click', function () { changeMonth(1); });
document.getElementById('cal-current').addEventListener('click', function () {
    var now = new Date();
    calState.year = now.getFullYear();
    calState.month = now.getMonth();
    loadCalendar();
});
document.getElementById('btn-add-event').addEventListener('click', function () {
    openEventForm(null);
});

bindForm(eventForm, 'vendor/calendar/save', function () {
    hideModal('event-modal');
    showToast(T('common.saved'), 'success');
    loadCalendar();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    return withCoop(payload);
});

onModuleReady(loadCalendar);
