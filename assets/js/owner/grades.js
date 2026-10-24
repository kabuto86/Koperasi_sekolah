// Darjah & tingkatan

var gradeForm = document.getElementById('grade-form');

function loadGrades() {
    tableLoading('grades-body', 8);
    Api.get('owner/grades/list').then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }
        var columns = [{ render: function (row) { return createEl('span', 'badge text-bg-dark font-monospace', row.code); } }];
        ['ms', 'en', 'zh', 'ta'].forEach(function (code) {
            columns.push({ render: function (row) {
                var span = createEl('span', '', row.names[code]);
                span.setAttribute('lang', code);
                return span;
            } });
        });
        columns.push({ render: function (row) { return T('grade.level.' + row.level_type); } });
        columns.push({ key: 'sort_order', className: 'text-center' });
        columns.push({ className: 'text-end', render: function (row) {
            return createButton('', 'btn-outline-secondary', 'bi-pencil', function () {
                fillForm(gradeForm, row);
                showModal('grade-modal');
            }, T('btn.edit'));
        } });
        renderTable('grades-body', res.data, columns);
    });
}

bindForm(gradeForm, 'owner/grades/save', function () {
    hideModal('grade-modal');
    showToast(T('common.saved'), 'success');
    loadGrades();
});

loadGrades();
