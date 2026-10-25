// Halaman modul add-on (U6): semak langganan koperasi semasa sebelum memuat data.
// Belum dilanggan → kad "Langgan modul ini" (vendor/_layout/module-gate.php); kandungan & butang tindakan disembunyikan.
// Pelayan tetap menolak setiap endpoint modul yang tidak dilanggan (ModuleAccess::has).

var ModulePage = {
    content: document.getElementById('module-content'),
    code: document.getElementById('module-content').getAttribute('data-module-code'),
    active: false
};

function setModuleLocked(locked, coop) {
    ModulePage.active = !locked;
    document.getElementById('module-checking').classList.add('d-none');
    document.getElementById('module-locked').classList.toggle('d-none', !locked);
    ModulePage.content.classList.toggle('d-none', locked);
    var actions = document.getElementById('page-actions');
    if (actions) {
        actions.classList.toggle('d-none', locked);
    }
    document.getElementById('module-locked-coop').textContent = coop ? T('common.current_cooperative', { name: coop.school_name }) : '';
}

// Panggil fn(coop) bila modul aktif untuk koperasi semasa (sekarang + setiap kali koperasi ditukar)
function onModuleReady(fn) {
    onCooperativeReady(function (coop) {
        Api.get('vendor/modules/active', withCoop()).then(function (res) {
            var active = res.ok && res.data.modules.indexOf(ModulePage.code) !== -1;
            setModuleLocked(!active, coop);
            if (active) {
                fn(coop);
            }
        });
    });
}

// Jawapan API modul: langganan tamat semasa halaman dibuka → tunjuk kad langgan
function moduleResponse(res) {
    if (!res.ok && res.module_locked) {
        setModuleLocked(true, currentCooperative());
    } else if (!res.ok) {
        showToast(res.error, 'danger');
    }
    return res.ok;
}

// "8 Nov 2026" atau "8 – 9 Nov 2026"
function dateRangeText(from, to) {
    return from === to ? formatDate(from) : formatDate(from) + ' – ' + formatDate(to);
}

// 'YYYY-MM-DD' tempatan (bukan UTC)
function localDay(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}
