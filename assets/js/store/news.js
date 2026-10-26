// Berita & pengumuman (modul add-on 'news')

var newsState = { page: 1, per_page: 10 };

function newsCard(post) {
    var card = createEl('article', 'card border-0 shadow-sm');
    card.id = 'news-' + post.id;
    if (post.image_url) {
        var img = createEl('img', 'card-img-top store-news-image');
        img.src = post.image_url;
        img.alt = '';
        card.appendChild(img);
    }
    var body = createEl('div', 'card-body');
    body.appendChild(createEl('div', 'small text-body-secondary', formatDate(post.published_at)));
    body.appendChild(createEl('h2', 'h6 mt-1', post.title));
    body.appendChild(createEl('p', 'mb-0 store-news-body', post.body));
    card.appendChild(body);
    return card;
}

function loadNews() {
    var list = document.getElementById('news-list');
    loadingState(list);
    storeGet('store/news/list', newsState).then(function (res) {
        if (!res.ok) {
            emptyState(list, 'bi-megaphone', res.error, T('store.nav.home'), storeUrl('home'));
            return;
        }
        if (res.data.items.length === 0) {
            emptyState(list, 'bi-megaphone', T('store.news.none'));
            return;
        }
        list.replaceChildren();
        res.data.items.forEach(function (post) {
            list.appendChild(newsCard(post));
        });
        var target = queryParam('id') ? document.getElementById('news-' + queryParam('id')) : null;
        if (target) {
            target.classList.add('border', 'border-brand');
            target.scrollIntoView();
        }
        renderPagination(document.getElementById('news-pagination'), res.data, function (page) {
            newsState.page = page;
            loadNews();
        });
    });
}

// Modul tidak aktif untuk koperasi ini → mesej terus (tanpa permintaan ke pelayan)
onStoreReady(function (store) {
    if (store.school.modules.indexOf('news') === -1) {
        emptyState(document.getElementById('news-list'), 'bi-megaphone', T('store.module_unavailable'), T('store.nav.home'), storeUrl('home'));
        return;
    }
    loadNews();
});
