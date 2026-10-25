// Log aktiviti vendor (semua koperasi atau koperasi semasa sahaja)

var activityList = { page: 1, per_page: 25, q: '', user_id: '', scope: '', date_from: '', date_to: '' };
var usersLoaded = false;

function loadActivity() {
    tableLoading('activity-body', 5);
    var params = activityList.scope === 'current' ? withCoop(activityList) : activityList;
    Api.get('vendor/activity-logs/list', params).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        if (!usersLoaded) {
            fillSelect('act-user', res.data.users, 'id', function (user) { return user.full_name; }, true);
            usersLoaded = true;
        }
        renderTable('activity-body', res.data.items, [
            { className: 'small text-nowrap', render: function (row) { return formatDate(row.created_at, true); } },
            { className: 'small', key: 'user_name' },
            { render: function (row) { return T('activity.action.' + row.action); } },
            { className: 'small text-body-secondary', render: function (row) { return row.detail || '—'; } },
            { className: 'small', render: function (row) { return row.cooperative_name || T('activity.vendor_level'); } }
        ]);
        renderPagination('activity-pagination', res.data, function (page) {
            activityList.page = page;
            loadActivity();
        });
    });
}

bindListFilters('activity-filters', activityList, loadActivity);

onCooperativeReady(function () {
    activityList.page = 1;
    loadActivity();
});
