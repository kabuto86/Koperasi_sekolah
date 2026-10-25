// Modul berita & pengumuman — koperasi semasa

var newsList = { page: 1, per_page: 20, q: '', state: '' };
var newsForm = document.getElementById('news-form');
var newsImage = null;
var newsRecipients = 0;
var newsEditing = null;

// 'YYYY-MM-DDTHH:MM' untuk <input type="datetime-local"> (waktu Malaysia; lalai = sekarang)
function toLocalInput(iso) {
    var now = new Date();
    return iso ? iso.slice(0, 16) : localDay(now) + 'T' + String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
}

function emailCell(row) {
    if (row.email_sent_at) {
        return createStack(T('news.email_sent', { n: row.email_recipients }), formatDate(row.email_sent_at, true));
    }
    if (row.send_email) {
        return createEl('span', 'badge text-bg-info', T('news.email_pending'));
    }
    return createEl('span', 'small text-body-secondary', T('news.email_none'));
}

function loadNews() {
    tableLoading('news-body', 5);
    Api.get('vendor/news/list', withCoop(newsList)).then(function (res) {
        if (!moduleResponse(res)) {
            return;
        }
        renderTable('news-body', res.data.items, [
            { render: function (row) {
                var stack = createStack(row.title, row.body.length > 90 ? row.body.slice(0, 90) + '…' : row.body);
                if (row.image_url) {
                    stack.firstChild.prepend(createIcon('bi-image text-body-secondary me-1'));
                }
                return stack;
            } },
            { className: 'small text-nowrap', render: function (row) { return formatDate(row.published_at, true); } },
            { className: 'small', render: emailCell },
            { render: function (row) { return statusBadge(row.state, 'news.state'); } },
            { className: 'text-end text-nowrap', render: function (row) {
                var wrap = createEl('div', 'd-inline-flex gap-1');
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-eye', function () {
                    previewNews(row);
                }, T('news.preview')));
                wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                    openNewsForm(row);
                }, T('btn.edit')));
                wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
                    deleteNews(row);
                }, T('btn.delete')));
                return wrap;
            } }
        ]);
        renderPagination('news-pagination', res.data, function (page) {
            newsList.page = page;
            loadNews();
        });
    });
}

// Teks bawah kotak e-mel: berapa ibu bapa akan menerima, atau sudah dihantar
function updateEmailHelp() {
    var help = document.getElementById('news-email-help');
    var box = document.getElementById('news-email');
    if (newsEditing && newsEditing.email_sent_at) {
        box.checked = true;
        box.disabled = true;
        help.textContent = T('news.email_already_sent', { n: newsEditing.email_recipients, date: formatDate(newsEditing.email_sent_at, true) });
        return;
    }
    box.disabled = false;
    help.textContent = T('news.email_help', { n: newsRecipients });
}

function openNewsForm(post) {
    newsEditing = post;
    document.getElementById('news-modal-title').textContent = post ? T('news.edit') : T('news.add');
    var data = post ? Object.assign({}, post) : { is_active: true, send_email: false };
    fillForm(newsForm, data);
    document.getElementById('news-published').value = toLocalInput(post ? post.published_at : null);
    newsImage = post ? post.image_url : null;
    showImagePreview('news-image-preview', newsImage);
    updateEmailHelp();
    showModal('news-modal');
    Api.get('vendor/news/recipients', withCoop()).then(function (res) {
        if (res.ok) {
            newsRecipients = res.data.count;
            updateEmailHelp();
        }
    });
}

function previewNews(post) {
    document.getElementById('news-preview-title').textContent = post.title;
    document.getElementById('news-preview-date').textContent = formatDate(post.published_at, true) + ' · ' + T('news.state.' + post.state);
    document.getElementById('news-preview-body').textContent = post.body;
    var imageBox = document.getElementById('news-preview-image');
    imageBox.replaceChildren();
    imageBox.classList.toggle('d-none', !post.image_url);
    if (post.image_url) {
        var img = createEl('img', 'img-fluid rounded');
        img.src = post.image_url;
        img.alt = '';
        imageBox.appendChild(img);
    }
    showModal('news-preview-modal');
}

function deleteNews(post) {
    confirmDialog(T('news.delete_confirm', { title: post.title }), { okClass: 'btn-danger', okText: T('btn.delete') }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('vendor/news/delete', withCoop({ id: post.id })).then(function (res) {
            if (moduleResponse(res)) {
                showToast(T('common.deleted'), 'success');
                loadNews();
            }
        });
    });
}

bindImageInput('news-image-file', 'news-image-preview', function (url) {
    newsImage = url;
});

document.getElementById('news-image-remove').addEventListener('click', function () {
    newsImage = null;
    document.getElementById('news-image-file').value = '';
    showImagePreview('news-image-preview', null);
});

document.getElementById('btn-add-news').addEventListener('click', function () {
    openNewsForm(null);
});

bindForm(newsForm, 'vendor/news/save', function (data) {
    hideModal('news-modal');
    showToast(data.emails_queued ? T('news.saved_email', { n: data.emails_queued }) : T('common.saved'), 'success');
    loadNews();
}, function (payload) {
    payload.id = payload.id ? Number(payload.id) : null;
    payload.image_url = newsImage;
    payload.send_email = document.getElementById('news-email').checked;
    return withCoop(payload);
});

bindListFilters('news-filters', newsList, loadNews);

onModuleReady(function () {
    newsList.page = 1;
    loadNews();
});
