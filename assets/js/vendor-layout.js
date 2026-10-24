// Layout vendor: pemilih koperasi + menu modul add-on berkunci.
// Halaman lain boleh dengar: document.addEventListener('cooperativechange', fn)

var VendorLayout = {
    storageKey: 'vendor.cooperative_id',
    cooperatives: [],
    current: null
};

function currentCooperativeId() {
    return VendorLayout.current ? VendorLayout.current.id : null;
}

function readSavedCooperativeId() {
    try {
        return Number(localStorage.getItem(VendorLayout.storageKey)) || null;
    } catch (error) {
        return null;
    }
}

function saveCooperativeId(id) {
    try {
        localStorage.setItem(VendorLayout.storageKey, String(id));
    } catch (error) {
        // abaikan — pilihan tidak diingat dalam mod peribadi
    }
}

function loadCooperativeOptions() {
    Api.get('vendor/cooperatives/list', { per_page: 100 }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }

        VendorLayout.cooperatives = res.data.items;
        fillCooperativeSelects();

        var saved = readSavedCooperativeId();
        var first = VendorLayout.cooperatives[0];
        selectCooperative(findCooperative(saved) ? saved : (first ? first.id : null));
    });
}

function findCooperative(id) {
    return VendorLayout.cooperatives.filter(function (coop) {
        return coop.id === id;
    })[0] || null;
}

function fillCooperativeSelects() {
    document.querySelectorAll('.js-coop-select').forEach(function (select) {
        select.replaceChildren();
        VendorLayout.cooperatives.forEach(function (coop) {
            var option = createEl('option', '', coop.school_name);
            option.value = coop.id;
            select.appendChild(option);
        });
        select.disabled = false;
    });
}

function selectCooperative(id) {
    var coop = findCooperative(id);
    if (!coop) {
        return;
    }

    Api.post('vendor/session/cooperative', { cooperative_id: coop.id }).then(function (res) {
        if (!res.ok) {
            showToast(res.error, 'danger');
            return;
        }

        VendorLayout.current = coop;
        saveCooperativeId(coop.id);
        document.querySelectorAll('.js-coop-select').forEach(function (select) {
            select.value = coop.id;
        });
        loadAddonMenu(coop.id);
        document.dispatchEvent(new CustomEvent('cooperativechange', { detail: { cooperative: coop } }));
    });
}

// Menu add-on: buka kunci modul yang dilanggan oleh koperasi semasa
function loadAddonMenu(cooperativeId) {
    Api.get('vendor/modules/active', { cooperative_id: cooperativeId }).then(function (res) {
        var active = res.ok ? res.data.modules : [];
        document.querySelectorAll('[data-module]').forEach(function (link) {
            setAddonLocked(link, active.indexOf(link.getAttribute('data-module')) === -1);
        });
    });
}

function setAddonLocked(link, locked) {
    link.href = locked ? link.getAttribute('data-locked-href') : link.getAttribute('data-open-href');
    link.classList.toggle('is-locked', locked);
    link.title = locked ? T('module.locked') : '';
    link.querySelector('.js-addon-lock').classList.toggle('d-none', !locked);
}

document.querySelectorAll('.js-coop-select').forEach(function (select) {
    select.addEventListener('change', function () {
        selectCooperative(Number(select.value));
    });
});

loadCooperativeOptions();
