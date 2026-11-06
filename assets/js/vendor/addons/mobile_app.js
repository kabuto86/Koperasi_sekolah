// Modul apps mudah alih — penggunaan apps ibu bapa (kiraan), push 30 hari, pautan muat turun & poster QR (koperasi semasa).
// QR dijana dalam pelayar (assets/lib/qrcode) — tiada perkhidmatan luar.

var MA_EVENTS = ['order_ready', 'order_collected', 'news'];

function qrDataUrl(text) {
    var qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr.createDataURL(5, 2);
}

function showLink(prefix, url) {
    var box = document.getElementById('ma-' + prefix);
    box.classList.toggle('d-none', !url);
    if (url) {
        document.getElementById('ma-' + prefix + '-qr').src = qrDataUrl(url);
        document.getElementById('ma-' + prefix + '-link').href = url;
    }
}

function renderStats(data) {
    ['parents', 'app_parents', 'devices'].forEach(function (key) {
        document.getElementById('ma-' + key).textContent = String(data[key]);
    });
    MA_EVENTS.forEach(function (event) {
        ['sent', 'pending', 'failed'].forEach(function (status) {
            document.getElementById('ma-push-' + event + '-' + status).textContent = String(data.push[event][status]);
        });
    });
    document.getElementById('ma-poster-school').textContent = data.school_name;
    showLink('android', data.android_url);
    showLink('ios', data.ios_url);
    document.getElementById('ma-store-qr').src = qrDataUrl(data.store_url);
    document.getElementById('ma-store-link').href = data.store_url;
    document.getElementById('ma-not-published').classList.toggle('d-none', Boolean(data.android_url || data.ios_url));
}

document.getElementById('btn-print-poster').addEventListener('click', function () {
    window.print();
});

onModuleReady(function () {
    Api.get('vendor/mobile-app/stats', withCoop()).then(function (res) {
        if (moduleResponse(res)) {
            renderStats(res.data);
        }
    });
});
