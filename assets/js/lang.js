// Terjemahan sisi klien.
// PHP mengeluarkan semua teks dalam atribut data-lang pada <body> (bukan skrip inline — CSP).
// Kunci hilang → kunci itu sendiri (PHP sudah gabungkan jatuh balik Bahasa Melayu).

window.LANG = {};

function loadLang() {
    var raw = document.body.getAttribute('data-lang') || '{}';
    try {
        window.LANG = JSON.parse(raw);
    } catch (error) {
        window.LANG = {};
    }
}

// Contoh: T('common.current_cooperative', { name: 'SK Taman Melati' })
function T(key, params) {
    var text = Object.prototype.hasOwnProperty.call(window.LANG, key) ? window.LANG[key] : key;

    if (params) {
        Object.keys(params).forEach(function (name) {
            text = text.split('{' + name + '}').join(String(params[name]));
        });
    }

    return text;
}

// Kod bahasa semasa: ms, en, zh atau ta
function currentLang() {
    return document.documentElement.getAttribute('lang') || 'ms';
}

loadLang();
