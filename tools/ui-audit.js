// Semakan UI menyeluruh (U7): semua halaman × 4 bahasa × 375px & 1366px.
// Guna: php -S 127.0.0.1:8080 -t .  (terminal lain), kemudian
//       node tools/ui-audit.js [shots] [lang=ms] [only=vendor_orders]
// Semak: ralat konsol/CSP, kunci bahasa mentah, NaN/undefined, skrol mendatar, medan tanpa label, butang tanpa nama,
// gambar tanpa alt; axe-core (kontras WCAG AA) jika pakej axe-core dipasang (npm i axe-core di luar repo, set AXE_PATH).
// 'shots' → tangkap layar ms & zh ke DOC/screenshots/U7/{lang}-{lebar}/. Laporan: tools/ui-audit-report.json (tidak di-commit)
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8080';
const { execFileSync } = require('child_process');
const REPO = path.resolve(__dirname, '..');
const SHOT_DIR = path.join(REPO, 'DOC/screenshots/U7');
const KEYS = new Set(JSON.parse(execFileSync('php', ['-r', 'echo json_encode(array_keys(require "lang/ms.php"));'], { cwd: REPO })));
const AXE = process.env.AXE_PATH && fs.existsSync(process.env.AXE_PATH) ? fs.readFileSync(process.env.AXE_PATH, 'utf8') : null;
const takeShots = process.argv.includes('shots');
const onlyLang = (process.argv.find(a => a.startsWith('lang=')) || '').slice(5);
const onlyFilter = (process.argv.find(a => a.startsWith('only=')) || '').slice(5);

const panelPages = fs.readFileSync(path.join(__dirname, 'panelpages.txt'), 'utf8').trim().split('\n');
const ids = {
    'owner/vendor.php': 'id=1', 'owner/invoice.php': 'id=1', 'vendor/order.php': 'id=1', 'vendor/product-form.php': 'id=1',
    'vendor/purchase-order.php': 'id=1', 'vendor/school-kit.php': 'id=1', 'vendor/stock-take.php': 'id=1',
    'vendor/addons/clearance-sale.php': 'id=1', 'vendor/cooperative-form.php': 'id=1'
};
const pages = [];
['owner/login.php', 'vendor/login.php'].forEach(p => pages.push({ name: p.replace('.php', '').replace(/\//g, '_'), url: '/' + p }));
panelPages.forEach(p => {
    const q = ids[p] ? '?' + ids[p] : '';
    pages.push({ name: p.replace('.php', '').replace(/\//g, '_'), url: '/' + p + q });
});
const storePublic = ['home', 'school-select', 'login', 'register', 'forgot-password'];
const storeSchool = ['home', 'catalog', 'product', 'kit', 'clearance', 'news', 'cart', 'checkout', 'orders', 'order-detail', 'order-success', 'profile', 'children'];
storePublic.forEach(p => pages.push({ name: 'store_' + p, url: '/store/index.php?page=' + p }));
storeSchool.forEach(p => pages.push({ name: 'store_school_' + p, url: '/store/index.php?school=sk-taman-melati&page=' + p, store: true }));

async function storeLogin(page) {
    await page.goto(BASE + '/store/index.php?school=sk-taman-melati&page=login');
    await page.evaluate(() => Api.post('store/auth/login', { login: 'rosnah@contoh.my', password: 'rahsia123' }));
    // Pesanan pertama ibu bapa demo — untuk halaman butiran / berjaya
    const orderId = await page.evaluate(async () => {
        const list = await storeGet('store/orders/list', {});
        if (list.ok && list.data.items && list.data.items.length) return list.data.items[0].id;
        return null;
    });
    return orderId;
}

async function auditPage(page, item, lang, width, errors) {
    const url = BASE + item.url + (item.url.includes('?') ? '&' : '?') + 'lang=' + lang;
    const resp = await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const result = await page.evaluate((keys) => {
        const out = { rawKeys: [], hscroll: false, unlabeled: [], unnamed: [], noalt: [], url: location.href };
        const text = document.body.innerText;
        (text.match(/\b[a-z0-9_]+(?:\.[a-zA-Z0-9_]+)+\b/g) || []).forEach(w => { if (keys.includes(w)) out.rawKeys.push(w); });
        out.bad = (text.match(/NaN|Invalid Date|undefined|\[object Object\]/g) || []);
        out.hscroll = document.documentElement.scrollWidth > document.documentElement.clientWidth + 1;
        document.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.type === 'hidden') return;
            const id = el.id;
            const labelled = el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || el.title || el.closest('label') ||
                (id && document.querySelector('label[for="' + CSS.escape(id) + '"]'));
            if (!labelled) out.unlabeled.push(el.outerHTML.slice(0, 140));
        });
        document.querySelectorAll('button, a[href], [role="button"]').forEach(el => {
            const name = (el.textContent || '').trim() || el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || el.title ||
                (el.querySelector('img[alt]') && el.querySelector('img[alt]').alt);
            if (!name) out.unnamed.push(el.outerHTML.slice(0, 160));
        });
        document.querySelectorAll('img:not([alt])').forEach(el => out.noalt.push(el.outerHTML.slice(0, 120)));
        return out;
    }, [...KEYS]);
    // axe: kontras & peraturan asas (hanya ms 1366 untuk jimat masa)
    let axe = [];
    if (AXE && lang === 'ms' && width === 1366) {
        await page.evaluate(AXE);
        axe = await page.evaluate(async () => {
            const r = await axe.run(document, { runOnly: ['wcag2a', 'wcag2aa'], resultTypes: ['violations'] });
            return r.violations.map(v => ({ id: v.id, impact: v.impact, n: v.nodes.length, sample: v.nodes.slice(0, 3).map(n => n.target.join(' ') + ' :: ' + (n.failureSummary || '').split('\n')[1]) }));
        });
    }
    if (takeShots && (lang === 'ms' || lang === 'zh')) {
        const dir = path.join(SHOT_DIR, lang + '-' + width);
        fs.mkdirSync(dir, { recursive: true });
        await page.screenshot({ path: path.join(dir, item.name + '.jpg'), type: 'jpeg', quality: 55, fullPage: width === 1366 ? false : false });
    }
    return Object.assign(result, { status: resp ? resp.status() : 0, errors: errors.splice(0), axe });
}

(async () => {
    const browser = await chromium.launch();
    const report = [];
    const langs = onlyLang ? [onlyLang] : ['ms', 'en', 'zh', 'ta'];
    for (const lang of langs) {
        for (const width of [375, 1366]) {
            const context = await browser.newContext({ viewport: { width, height: width === 375 ? 812 : 768 } });
            const page = await context.newPage();
            const errors = [];
            page.on('console', m => { if (m.type() === 'error') errors.push('console: ' + m.text()); });
            page.on('pageerror', e => errors.push('pageerror: ' + e.message));
            const orderId = await storeLogin(page);
            errors.splice(0);
            for (const item0 of pages) {
                if (onlyFilter && !item0.name.includes(onlyFilter)) continue;
                const item = Object.assign({}, item0);
                if (item.name === 'store_school_order-detail' || item.name === 'store_school_order-success') {
                    item.url += '&id=' + (orderId || 1);
                }
                if (item.name === 'store_school_product') item.url += '&id=1';
                const r = await auditPage(page, item, lang, width, errors);
                report.push(Object.assign({ page: item.name, lang, width }, r));
            }
            await context.close();
        }
    }
    await browser.close();
    fs.writeFileSync(path.join(__dirname, 'ui-audit-report.json'), JSON.stringify(report, null, 1));
    const bad = report.filter(r => r.status !== 200 || r.errors.length || r.rawKeys.length || r.hscroll || r.bad.length || r.unlabeled.length || r.unnamed.length || r.noalt.length);
    console.log('Halaman disemak:', report.length, '· ada isu:', bad.length, '(lihat tools/ui-audit-report.json)');
})();
