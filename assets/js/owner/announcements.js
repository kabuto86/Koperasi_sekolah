// Pengumuman vendor

var announcementForm = document.getElementById('announcement-form');
var announcementList = { page: 1, per_page: 20 };

function loadAnnouncements() {
    tableLoading('announcements-body', 5);
    Api.get('owner/announcements/list', announcementList).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        renderTable('announcements-body', res.data.items, [
            { render: function (row) { return createStack(row.title, row.body.length > 90 ? row.body.slice(0, 90) + '…' : row.body); } },
            { render: function (row) {
                return row.target === 'all' ? T('announcement.target_all') : T('announcement.vendor_count', { n: row.vendor_ids.length }) + ': ' + row.vendor_names.join(', ');
            } },
            { render: function (row) {
                return row.published_at
                    ? createStack(createEl('span', 'badge text-bg-success', T('announcement.published')), formatDate(row.published_at, true))
                    : createEl('span', 'badge text-bg-light border', T('announcement.draft'));
            } },
            { render: function (row) { return createStack(row.created_by, formatDate(row.created_at)); } },
            { className: 'text-end text-nowrap', render: announcementActions }
        ]);
        renderPagination('announcements-pagination', res.data, function (page) {
            announcementList.page = page;
            loadAnnouncements();
        });
    });
}

function announcementActions(row) {
    var wrap = createEl('div', 'd-inline-flex gap-1');
    // Pengumuman yang sudah diterbitkan tidak boleh disunting (vendor sudah membacanya)
    if (!row.published_at) {
        wrap.appendChild(createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
            openAnnouncementForm(row);
        }, T('btn.edit')));
    }
    wrap.appendChild(createButton('', 'btn-outline-danger', 'bi-trash', function () {
        deleteAnnouncement(row);
    }, T('btn.delete')));
    return wrap;
}

function loadVendorChoices() {
    Api.get('owner/vendors/options').then(function (res) {
        if (!res.ok) {
            return;
        }
        var box = document.getElementById('ann-vendors');
        res.data.forEach(function (vendor) {
            var check = createEl('div', 'form-check');
            var input = createEl('input', 'form-check-input');
            input.type = 'checkbox';
            input.name = 'vendor_ids';
            input.value = vendor.id;
            input.id = 'ann-vendor-' + vendor.id;
            input.setAttribute('data-multi', '');
            input.setAttribute('data-type', 'int');
            var label = createEl('label', 'form-check-label', vendor.code + ' — ' + vendor.company_name);
            label.htmlFor = input.id;
            check.appendChild(input);
            check.appendChild(label);
            box.appendChild(check);
        });
    });
}

function toggleVendorBox() {
    var selected = document.getElementById('ann-target-selected').checked;
    document.getElementById('ann-vendors').classList.toggle('d-none', !selected);
    document.getElementById('ann-vendors-error').classList.add('d-none');
}

function openAnnouncementForm(item) {
    document.getElementById('announcement-modal-title').textContent = item ? T('announcement.edit') : T('announcement.add');
    fillForm(announcementForm, item || { target: 'all', vendor_ids: [] });
    toggleVendorBox();
    showModal('announcement-modal');
}

function saveAnnouncement(publish) {
    var payload = formToObject(announcementForm);
    if (payload.target === 'selected' && payload.vendor_ids.length === 0) {
        validateForm(announcementForm);
        document.getElementById('ann-vendors-error').classList.remove('d-none');
        return;
    }
    var ask = publish ? confirmDialog(T('announcement.publish_confirm'), { okText: T('announcement.publish') }) : Promise.resolve(true);

    ask.then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        submitForm(announcementForm, 'owner/announcements/save', function () {
            hideModal('announcement-modal');
            showToast(publish ? T('announcement.published_toast') : T('common.saved'), 'success');
            loadAnnouncements();
        }, function (data) {
            data.id = data.id ? Number(data.id) : null;
            data.publish = publish;
            return data;
        });
    });
}

function deleteAnnouncement(item) {
    confirmDialog(T('announcement.delete_confirm', { title: item.title }), { okText: T('btn.delete'), okClass: 'btn-danger' }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        Api.post('owner/announcements/delete', { id: item.id }).then(function (res) {
            if (!res.ok) {
                showToast(res.error, 'danger');
                return;
            }
            showToast(T('common.deleted'), 'success');
            loadAnnouncements();
        });
    });
}

document.getElementById('btn-add-announcement').addEventListener('click', function () {
    openAnnouncementForm(null);
});
document.getElementById('ann-target-all').addEventListener('change', toggleVendorBox);
document.getElementById('ann-target-selected').addEventListener('change', toggleVendorBox);
document.getElementById('btn-save-draft').addEventListener('click', function () {
    saveAnnouncement(false);
});
document.getElementById('btn-publish').addEventListener('click', function () {
    saveAnnouncement(true);
});
announcementForm.addEventListener('submit', function (event) {
    event.preventDefault();
});

loadVendorChoices();
loadAnnouncements();
