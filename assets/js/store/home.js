// Halaman utama store

function schoolListItem(school, hint) {
    var link = createEl('a', 'list-group-item list-group-item-action d-flex align-items-center gap-3');
    link.href = storeUrl('home', null, school.slug);
    link.appendChild(createIcon('bi-building fs-4 text-brand'));
    link.appendChild(createStack(school.school_name, [school.district, school.state].filter(Boolean).join(', ') || hint));
    return link;
}

function renderNoSchool(store) {
    var last = lastSchool();
    var schools = store.session.schools || [];
    if (last && !schools.some(function (row) { return row.slug === last.slug; })) {
        document.getElementById('home-last-list').replaceChildren(schoolListItem(last));
        document.getElementById('home-last-school').classList.remove('d-none');
    }
    if (schools.length) {
        var list = document.getElementById('home-my-list');
        list.replaceChildren();
        schools.forEach(function (school) {
            list.appendChild(schoolListItem(school));
        });
        document.getElementById('home-my-schools').classList.remove('d-none');
    }
}

function renderHours(hours) {
    var list = document.getElementById('home-hours');
    list.replaceChildren();
    ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'].forEach(function (day) {
        var row = hours[day];
        list.appendChild(createEl('dt', 'col-5 fw-normal text-body-secondary', T('day.' + day)));
        list.appendChild(createEl('dd', 'col-7 mb-1', row && row.open ? row.from + ' – ' + row.to : T('store.home.closed')));
    });
}

function renderChildren(store) {
    var box = document.getElementById('home-children');
    if (!store.customer) {
        var guest = createEl('div', 'text-body-secondary');
        guest.appendChild(createEl('p', 'small', T('store.home.guest_text')));
        var actions = createEl('div', 'd-flex gap-2');
        actions.appendChild(createLink(T('store.auth.register_title'), storeUrl('register'), 'btn btn-brand'));
        actions.appendChild(createLink(T('btn.login'), storeUrl('login'), 'btn btn-outline-secondary'));
        guest.appendChild(actions);
        box.replaceChildren(guest);
        return;
    }
    storeGet('store/children/list').then(function (res) {
        if (!res.ok) {
            return;
        }
        var here = res.data.filter(function (child) { return child.school_slug === Store.slug; });
        if (here.length === 0) {
            emptyState(box, 'bi-person-plus', T('store.home.no_children', { school: store.school.school_name }), T('store.children.add'), storeUrl('children', { add: 1 }));
            return;
        }
        var list = createEl('div', 'list-group list-group-flush');
        here.forEach(function (child) {
            var row = createEl('div', 'list-group-item px-0 d-flex align-items-center gap-2');
            row.appendChild(createIcon('bi-person-badge fs-4 text-brand'));
            var text = createStack(child.full_name, childClassLine(child) + ' · ' + child.school_year);
            text.classList.add('flex-grow-1', 'min-w-0');
            row.appendChild(text);
            row.appendChild(createIconLinkText(storeUrl('kit', { child: child.id }), 'bi-backpack', T('store.home.buy_kit'), 'btn-brand'));
            list.appendChild(row);
        });
        box.replaceChildren(list);
    });
}

function renderReadyOrders(store) {
    if (!store.customer) {
        return;
    }
    document.getElementById('home-verify').classList.toggle('d-none', store.customer.email_verified);
    storeGet('store/orders/list', { per_page: 1 }).then(function (res) {
        if (res.ok && res.data.ready_count > 0) {
            document.getElementById('home-ready-text').textContent = T('store.home.ready_orders', { n: res.data.ready_count });
            document.getElementById('home-ready').classList.remove('d-none');
        }
    });
}

function renderNews(school) {
    if (school.modules.indexOf('news') === -1) {
        return;
    }
    storeGet('store/news/list', { per_page: 2 }).then(function (res) {
        if (!res.ok) {
            return;
        }
        var list = document.getElementById('home-news-list');
        list.replaceChildren();
        res.data.items.forEach(function (post) {
            var link = createEl('a', 'list-group-item list-group-item-action px-0');
            link.href = storeUrl('news', { id: post.id });
            link.appendChild(createEl('div', 'fw-semibold', post.title));
            link.appendChild(createEl('div', 'small text-body-secondary', formatDate(post.published_at)));
            list.appendChild(link);
        });
    });
}

onStoreReady(function (store) {
    if (!store.slug) {
        renderNoSchool(store);
        return;
    }
    var school = store.school;
    document.querySelector('[data-school-welcome]').textContent = T('store.welcome', { school: school.school_name });
    document.getElementById('home-school-meta').textContent = [school.district, school.state].filter(Boolean).join(', ');
    renderHours(school.opening_hours || {});
    renderChildren(store);
    renderReadyOrders(store);
    renderNews(school);
});
