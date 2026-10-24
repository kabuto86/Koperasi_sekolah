// Data mock panel owner (Peringkat 1). Bentuk objek = kontrak API (PELAN 18).
// Data disimpan dalam memori sahaja — hilang bila halaman dimuat semula.

var MockOwner = {
    plans: [
        { id: 1, code: 'ASAS', name: 'Asas', price_monthly: 4900, price_yearly: 49000, max_cooperatives: 2, max_products: 300, max_staff: 3,
          features: ['Store ibu bapa', 'Kit sekolah', 'Serah barang'], is_active: true, sort_order: 1 },
        { id: 2, code: 'PRO', name: 'Pro', price_monthly: 9900, price_yearly: 99000, max_cooperatives: 5, max_products: 2000, max_staff: 10,
          features: ['Semua dalam Asas', 'POS kaunter', 'Laporan penuh'], is_active: true, sort_order: 2 },
        { id: 3, code: 'PREMIUM', name: 'Premium', price_monthly: 19900, price_yearly: 199000, max_cooperatives: null, max_products: null, max_staff: null,
          features: ['Semua dalam Pro', 'Koperasi tanpa had', 'Sokongan keutamaan'], is_active: true, sort_order: 3 }
    ],

    // plan_coop_tiers — koperasi ke-1 termasuk dalam harga pelan
    tiers: [
        { id: 1, plan_id: 1, from_coop: 2, to_coop: null, price_monthly: 4900, price_yearly: 49000 },
        { id: 2, plan_id: 2, from_coop: 2, to_coop: 3, price_monthly: 7900, price_yearly: 79000 },
        { id: 3, plan_id: 2, from_coop: 4, to_coop: null, price_monthly: 5900, price_yearly: 59000 },
        { id: 4, plan_id: 3, from_coop: 2, to_coop: null, price_monthly: 9900, price_yearly: 99000 }
    ],

    vendors: [
        { id: 1, code: 'VENDOR01', company_name: 'Niaga Sekolah Sdn Bhd', registration_no: '202301012345', contact_name: 'Ahmad bin Ismail',
          phone: '012-3456789', email: 'ahmad@niagasekolah.my', address: 'No. 12, Jalan Melati 3, 53100 Gombak, Selangor',
          status: 'active', trial_ends_at: null, plan_id: 2, billing_cycle: 'yearly', created_at: '2026-01-05T09:00:00+08:00' },
        { id: 2, code: 'VENDOR02', company_name: 'Koperasi Bestari Enterprise', registration_no: 'JM0876543-T', contact_name: 'Lim Mei Ling',
          phone: '017-2223344', email: 'meiling@bestari.my', address: '8, Jalan Indah 2, 81200 Johor Bahru, Johor',
          status: 'active', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-02-11T10:30:00+08:00' },
        { id: 3, code: 'VENDOR03', company_name: 'Pustaka Ilmu Trading', registration_no: 'SA0123456-A', contact_name: 'Nurul Huda',
          phone: '019-8887766', email: 'huda@pustakailmu.my', address: '22, Jalan Seri Pagi, 08000 Sungai Petani, Kedah',
          status: 'trial', trial_ends_at: '2026-11-03T23:59:59+08:00', plan_id: 1, billing_cycle: 'monthly', created_at: '2026-10-20T14:00:00+08:00' },
        { id: 4, code: 'VENDOR04', company_name: 'Kedai Buku Cemerlang', registration_no: 'PG0456789-K', contact_name: 'Muthu a/l Rajan',
          phone: '016-5554433', email: 'muthu@cemerlang.my', address: '5, Lorong Ladang, 14000 Bukit Mertajam, Pulau Pinang',
          status: 'past_due', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-03-02T08:15:00+08:00' },
        { id: 5, code: 'VENDOR05', company_name: 'Seri Murni Supplies', registration_no: 'KT0345678-P', contact_name: 'Wan Zainab',
          phone: '013-9998877', email: 'zainab@serimurni.my', address: '17, Jalan Sultan Zainal Abidin, 20000 Kuala Terengganu',
          status: 'suspended', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-04-18T11:00:00+08:00' },
        { id: 6, code: 'VENDOR06', company_name: 'Delima Niaga', registration_no: 'NS0234567-D', contact_name: 'Faizal Rahman',
          phone: '011-23456789', email: 'faizal@delima.my', address: '3, Jalan Delima, 70000 Seremban, Negeri Sembilan',
          status: 'cancelled', trial_ends_at: null, plan_id: 1, billing_cycle: 'monthly', created_at: '2026-02-01T09:45:00+08:00' },
        { id: 7, code: 'VENDOR07', company_name: 'Mutiara Edu Sdn Bhd', registration_no: '202201098765', contact_name: 'Tan Wei Jie',
          phone: '018-7776655', email: 'weijie@mutiaraedu.my', address: 'Presint 9, 62250 Putrajaya',
          status: 'active', trial_ends_at: null, plan_id: 3, billing_cycle: 'monthly', created_at: '2026-01-20T16:20:00+08:00' }
    ],

    vendorNotes: [
        { id: 1, vendor_id: 4, body: 'Telah dihubungi melalui telefon tentang invois tertunggak.', created_by: 'Siti Aminah', created_at: '2026-10-20T11:00:00+08:00' },
        { id: 2, vendor_id: 5, body: 'Digantung kerana invois Ogos & September belum dibayar.', created_by: 'Pemilik Platform', created_at: '2026-10-02T09:30:00+08:00' }
    ],

    cooperatives: [
        { id: 1, vendor_id: 1, slug: 'sk-taman-melati', school_name: 'SK Taman Melati', school_code: 'BBA1234', school_type: 'SK', state: 'Selangor', district: 'Gombak', status: 'active', template_id: 1, modules: ['news', 'school_opening'], created_at: '2026-01-06T10:00:00+08:00' },
        { id: 2, vendor_id: 1, slug: 'sjkc-chung-hwa', school_name: 'SJKC Chung Hwa', school_code: 'BBC5678', school_type: 'SJKC', state: 'Selangor', district: 'Petaling', status: 'active', template_id: 2, modules: [], created_at: '2026-02-14T10:00:00+08:00' },
        { id: 3, vendor_id: 2, slug: 'smk-bukit-indah', school_name: 'SMK Bukit Indah', school_code: 'JEA9012', school_type: 'SMK', state: 'Johor', district: 'Johor Bahru', status: 'active', template_id: 1, modules: ['clearance'], created_at: '2026-02-12T10:00:00+08:00' },
        { id: 4, vendor_id: 3, slug: 'sk-seri-pagi', school_name: 'SK Seri Pagi', school_code: 'KBA3456', school_type: 'SK', state: 'Kedah', district: 'Kuala Muda', status: 'active', template_id: 1, modules: [], created_at: '2026-10-20T15:00:00+08:00' },
        { id: 5, vendor_id: 4, slug: 'sjkt-ladang-bukit', school_name: 'SJKT Ladang Bukit', school_code: 'PBD2345', school_type: 'SJKT', state: 'Pulau Pinang', district: 'Seberang Perai Tengah', status: 'active', template_id: 1, modules: [], created_at: '2026-03-03T10:00:00+08:00' },
        { id: 6, vendor_id: 5, slug: 'smka-al-amin', school_name: 'SMKA Al-Amin', school_code: 'TEA7788', school_type: 'SMKA', state: 'Terengganu', district: 'Kuala Terengganu', status: 'suspended', template_id: 1, modules: ['news'], created_at: '2026-04-19T10:00:00+08:00' },
        { id: 7, vendor_id: 7, slug: 'sk-presint-9', school_name: 'SK Putrajaya Presint 9 (1)', school_code: 'WPA0011', school_type: 'SK', state: 'W.P. Putrajaya', district: 'Putrajaya', status: 'active', template_id: 2, modules: ['news', 'calendar', 'mobile_app'], created_at: '2026-01-21T10:00:00+08:00' },
        { id: 8, vendor_id: 7, slug: 'smk-presint-9', school_name: 'SMK Putrajaya Presint 9 (1)', school_code: 'WEA0022', school_type: 'SMK', state: 'W.P. Putrajaya', district: 'Putrajaya', status: 'active', template_id: 1, modules: ['news'], created_at: '2026-02-01T10:00:00+08:00' },
        { id: 9, vendor_id: 7, slug: 'sk-bandar-baru-bangi', school_name: 'SK Bandar Baru Bangi', school_code: 'BBA9090', school_type: 'SK', state: 'Selangor', district: 'Hulu Langat', status: 'closed', template_id: 1, modules: [], created_at: '2026-03-15T10:00:00+08:00' }
    ],

    modules: [
        { id: 1, code: 'news', names: { ms: 'Berita & pengumuman', en: 'News & announcements', zh: '新闻与公告', ta: 'செய்திகள் & அறிவிப்புகள்' },
          descriptions: { ms: 'Pos berita kepada ibu bapa koperasi itu.', en: 'Post news to the cooperative\'s parents.', zh: '', ta: '' },
          price_monthly: 1500, price_yearly: 15000, is_core: false, is_active: true, sort_order: 1 },
        { id: 2, code: 'clearance', names: { ms: 'Jualan murah', en: 'Clearance sale', zh: '清仓特卖', ta: 'தள்ளுபடி விற்பனை' },
          descriptions: { ms: 'Harga khas dengan tempoh dan lencana Clearance.', en: 'Special prices with a period and a Clearance badge.', zh: '', ta: '' },
          price_monthly: 1000, price_yearly: 10000, is_core: false, is_active: true, sort_order: 2 },
        { id: 3, code: 'calendar', names: { ms: 'Kalendar cuti', en: 'Holiday calendar', zh: '假期日历', ta: 'விடுமுறை நாட்காட்டி' },
          descriptions: { ms: 'Tempahan ditutup automatik pada hari cuti.', en: 'Orders close automatically on holidays.', zh: '', ta: '' },
          price_monthly: 500, price_yearly: 5000, is_core: false, is_active: true, sort_order: 3 },
        { id: 4, code: 'school_opening', names: { ms: 'Pembukaan sekolah', en: 'School opening', zh: '开学季', ta: 'பள்ளி திறப்பு' },
          descriptions: { ms: 'Musim pre-order kit dan slot ambil barang.', en: 'Kit pre-order season and pickup slots.', zh: '', ta: '' },
          price_monthly: 2000, price_yearly: 20000, is_core: false, is_active: true, sort_order: 4 },
        { id: 5, code: 'mobile_app', names: { ms: 'Apps telefon', en: 'Mobile app', zh: '手机应用', ta: 'கைபேசி செயலி' },
          descriptions: { ms: 'Ibu bapa guna apps Flutter dengan notifikasi push.', en: 'Parents use the Flutter app with push notifications.', zh: '', ta: '' },
          price_monthly: 3000, price_yearly: 30000, is_core: false, is_active: true, sort_order: 5 },
        { id: 6, code: 'whatsapp_sms', names: { ms: 'Notifikasi WhatsApp/SMS', en: 'WhatsApp/SMS notifications', zh: 'WhatsApp/短信通知', ta: 'WhatsApp/SMS அறிவிப்புகள்' },
          descriptions: { ms: 'Mesej "sedia diambil" dan "telah diambil".', en: '"Ready" and "collected" messages.', zh: '', ta: '' },
          price_monthly: 2500, price_yearly: 25000, is_core: false, is_active: true, sort_order: 6 },
        { id: 7, code: 'advanced_report', names: { ms: 'Laporan lanjutan', en: 'Advanced reports', zh: '高级报表', ta: 'மேம்பட்ட அறிக்கைகள்' },
          descriptions: { ms: 'Perbandingan tahun dan analisis kit.', en: 'Year comparison and kit analysis.', zh: '', ta: '' },
          price_monthly: 2000, price_yearly: 20000, is_core: false, is_active: true, sort_order: 7 },
        { id: 8, code: 'membership', names: { ms: 'Keahlian koperasi', en: 'Cooperative membership', zh: '合作社会员', ta: 'கூட்டுறவு உறுப்பினர்' },
          descriptions: { ms: 'Ahli, syer dan dividen.', en: 'Members, shares and dividends.', zh: '', ta: '' },
          price_monthly: 3000, price_yearly: 30000, is_core: false, is_active: false, sort_order: 8 }
    ],

    templates: [
        { id: 1, code: 'template1', name: 'Template 1 — Asas', folder: 'templates/template1', preview_image: null,
          price_monthly: 0, price_yearly: 0, is_free: true, is_active: true, description: 'Ringkas, mobile-first, navbar bawah.' },
        { id: 2, code: 'template2', name: 'Template 2 — Ceria', folder: 'templates/template2', preview_image: null,
          price_monthly: 1000, price_yearly: 10000, is_free: false, is_active: true, description: 'Warna ceria untuk sekolah rendah.' },
        { id: 3, code: 'template3', name: 'Template 3 — Klasik', folder: 'templates/template3', preview_image: null,
          price_monthly: 1500, price_yearly: 15000, is_free: false, is_active: false, description: 'Rupa formal untuk sekolah menengah.' }
    ],

    subscriptions: [
        { id: 1, vendor_id: 1, plan_id: 2, billing_cycle: 'yearly', status: 'active', started_at: '2026-01-05', current_period_start: '2026-01-05', current_period_end: '2027-01-04' },
        { id: 2, vendor_id: 2, plan_id: 1, billing_cycle: 'monthly', status: 'active', started_at: '2026-02-11', current_period_start: '2026-10-11', current_period_end: '2026-11-10' },
        { id: 3, vendor_id: 3, plan_id: 1, billing_cycle: 'monthly', status: 'trial', started_at: '2026-10-20', current_period_start: '2026-10-20', current_period_end: '2026-11-03' },
        { id: 4, vendor_id: 4, plan_id: 1, billing_cycle: 'monthly', status: 'past_due', started_at: '2026-03-02', current_period_start: '2026-10-02', current_period_end: '2026-11-01' },
        { id: 5, vendor_id: 5, plan_id: 1, billing_cycle: 'monthly', status: 'suspended', started_at: '2026-04-18', current_period_start: '2026-08-18', current_period_end: '2026-09-17' },
        { id: 6, vendor_id: 6, plan_id: 1, billing_cycle: 'monthly', status: 'cancelled', started_at: '2026-02-01', current_period_start: '2026-06-01', current_period_end: '2026-06-30' },
        { id: 7, vendor_id: 7, plan_id: 3, billing_cycle: 'monthly', status: 'active', started_at: '2026-01-20', current_period_start: '2026-10-20', current_period_end: '2026-11-19' }
    ],

    invoices: [
        { id: 1, invoice_no: 'INV-2026-0001', vendor_id: 1, period_start: '2026-01-05', period_end: '2027-01-04', subtotal: 178000, tax: 14240, total: 192240, status: 'paid',
          issued_at: '2026-01-05T09:00:00+08:00', due_at: '2026-01-12T23:59:59+08:00', paid_at: '2026-01-06T10:12:00+08:00',
          lines: [
              { description: 'Pelan Pro (tahunan)', item_type: 'plan', qty: 1, unit_price: 99000, amount: 99000, is_prorated: false },
              { description: 'Koperasi tambahan: SJKC Chung Hwa', item_type: 'cooperative', qty: 1, unit_price: 79000, amount: 79000, is_prorated: false }
          ],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-88812', amount: 192240, paid_at: '2026-01-06T10:12:00+08:00' }] },
        { id: 2, invoice_no: 'INV-2026-0046', vendor_id: 2, period_start: '2026-10-11', period_end: '2026-11-10', subtotal: 5900, tax: 472, total: 6372, status: 'paid',
          issued_at: '2026-10-11T00:05:00+08:00', due_at: '2026-10-18T23:59:59+08:00', paid_at: '2026-10-12T08:40:00+08:00',
          lines: [
              { description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false },
              { description: 'Modul Jualan murah: SMK Bukit Indah', item_type: 'module', qty: 1, unit_price: 1000, amount: 1000, is_prorated: false }
          ],
          payments: [{ method: 'online', gateway_code: 'toyyibpay', reference: 'TP-90211', amount: 6372, paid_at: '2026-10-12T08:40:00+08:00' }] },
        { id: 3, invoice_no: 'INV-2026-0041', vendor_id: 4, period_start: '2026-10-02', period_end: '2026-11-01', subtotal: 4900, tax: 392, total: 5292, status: 'issued',
          issued_at: '2026-10-02T00:05:00+08:00', due_at: '2026-10-09T23:59:59+08:00', paid_at: null,
          lines: [{ description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false }],
          payments: [] },
        { id: 4, invoice_no: 'INV-2026-0032', vendor_id: 5, period_start: '2026-08-18', period_end: '2026-09-17', subtotal: 6400, tax: 512, total: 6912, status: 'issued',
          issued_at: '2026-08-18T00:05:00+08:00', due_at: '2026-08-25T23:59:59+08:00', paid_at: null,
          lines: [
              { description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false },
              { description: 'Modul Berita: SMKA Al-Amin', item_type: 'module', qty: 1, unit_price: 1500, amount: 1500, is_prorated: false }
          ],
          payments: [] },
        { id: 5, invoice_no: 'INV-2026-0048', vendor_id: 7, period_start: '2026-10-20', period_end: '2026-11-19', subtotal: 49300, tax: 3944, total: 53244, status: 'issued',
          issued_at: '2026-10-20T00:05:00+08:00', due_at: '2026-10-27T23:59:59+08:00', paid_at: null,
          lines: [
              { description: 'Pelan Premium (bulanan)', item_type: 'plan', qty: 1, unit_price: 19900, amount: 19900, is_prorated: false },
              { description: 'Koperasi tambahan: SMK Putrajaya Presint 9 (1)', item_type: 'cooperative', qty: 1, unit_price: 9900, amount: 9900, is_prorated: false },
              { description: 'Modul Berita × 2 koperasi', item_type: 'module', qty: 2, unit_price: 1500, amount: 3000, is_prorated: false },
              { description: 'Modul Kalendar cuti: SK Putrajaya Presint 9 (1)', item_type: 'module', qty: 1, unit_price: 500, amount: 500, is_prorated: false },
              { description: 'Modul Apps telefon: SK Putrajaya Presint 9 (1)', item_type: 'module', qty: 1, unit_price: 3000, amount: 3000, is_prorated: false },
              { description: 'Template Ceria: SK Putrajaya Presint 9 (1)', item_type: 'template', qty: 1, unit_price: 1000, amount: 1000, is_prorated: false },
              { description: 'Koperasi tambahan: SK Bandar Baru Bangi (prorata 12 hari)', item_type: 'cooperative', qty: 1, unit_price: 12000, amount: 12000, is_prorated: true }
          ],
          payments: [] },
        { id: 6, invoice_no: 'INV-2026-0049', vendor_id: 3, period_start: '2026-11-04', period_end: '2026-12-03', subtotal: 4900, tax: 392, total: 5292, status: 'draft',
          issued_at: null, due_at: null, paid_at: null,
          lines: [{ description: 'Pelan Asas (bulanan) — selepas trial', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false }],
          payments: [] },
        { id: 7, invoice_no: 'INV-2026-0012', vendor_id: 6, period_start: '2026-06-01', period_end: '2026-06-30', subtotal: 4900, tax: 392, total: 5292, status: 'void',
          issued_at: '2026-06-01T00:05:00+08:00', due_at: '2026-06-08T23:59:59+08:00', paid_at: null,
          lines: [{ description: 'Pelan Asas (bulanan)', item_type: 'plan', qty: 1, unit_price: 4900, amount: 4900, is_prorated: false }],
          payments: [] }
    ],

    gateways: [
        { code: 'toyyibpay', name: 'ToyyibPay', allowed_for_vendors: true, supports_refund: false,
          fields: [{ name: 'secret_key', secret: true }, { name: 'category_code', secret: false }] },
        { code: 'billplz', name: 'Billplz', allowed_for_vendors: true, supports_refund: false,
          fields: [{ name: 'api_key', secret: true }, { name: 'collection_id', secret: false }, { name: 'x_signature', secret: true }] },
        { code: 'senangpay', name: 'SenangPay', allowed_for_vendors: false, supports_refund: false,
          fields: [{ name: 'merchant_id', secret: false }, { name: 'secret_key', secret: true }] },
        { code: 'ipay88', name: 'iPay88', allowed_for_vendors: false, supports_refund: false,
          fields: [{ name: 'merchant_code', secret: false }, { name: 'merchant_key', secret: true }] },
        { code: 'stripe', name: 'Stripe', allowed_for_vendors: false, supports_refund: true,
          fields: [{ name: 'publishable_key', secret: false }, { name: 'secret_key', secret: true }, { name: 'webhook_secret', secret: true }] }
    ],

    // Gateway owner untuk kutip yuran langganan. Nilai rahsia TIDAK pernah dipulangkan — hanya *_set
    ownerGateway: { driver_code: 'toyyibpay', sandbox: true, values: { category_code: 'kpk2026' }, secrets_set: ['secret_key'] },

    // Contoh jadual terjemahan (Peringkat 2: dijana dari jadual translations)
    translations: [
        { key: 'menu.dashboard', values: { ms: 'Papan pemuka', en: 'Dashboard', zh: '仪表板', ta: 'முகப்புப் பலகை' } },
        { key: 'menu.orders', values: { ms: 'Pesanan online', en: 'Online orders', zh: '网上订单', ta: 'இணைய ஆர்டர்கள்' } },
        { key: 'menu.handover', values: { ms: 'Serah barang', en: 'Hand over items', zh: '交付物品', ta: 'பொருள் ஒப்படைப்பு' } },
        { key: 'btn.save', values: { ms: 'Simpan', en: 'Save', zh: '保存', ta: 'சேமி' } },
        { key: 'btn.cancel', values: { ms: 'Batal', en: 'Cancel', zh: '取消', ta: 'ரத்து செய்' } },
        { key: 'order.status.ready', values: { ms: 'Sedia diambil', en: 'Ready for collection', zh: '可领取', ta: 'பெறத் தயார்' } },
        { key: 'order.status.collected', values: { ms: 'Telah diambil', en: 'Collected', zh: '已领取', ta: 'பெறப்பட்டது' } },
        { key: 'order.status.unpaid_cash', values: { ms: 'Bayar tunai semasa ambil', en: 'Pay cash on collection', zh: '领取时付现金', ta: '' } },
        { key: 'pickup.collected_by', values: { ms: 'Diambil oleh {name} ({class}) pada {time}', en: 'Collected by {name} ({class}) at {time}', zh: '', ta: '' } },
        { key: 'store.kit.uncheck_hint', values: { ms: 'Nyahtanda barang yang tidak diperlukan.', en: 'Untick items you do not need.', zh: '取消勾选不需要的物品。', ta: '' } },
        { key: 'store.checkout.pickup_by', values: { ms: 'Siapa akan ambil barang?', en: 'Who will collect the items?', zh: '', ta: '' } },
        { key: 'email.order_ready.subject', values: { ms: 'Pesanan {order_no} sedia diambil', en: 'Order {order_no} is ready for collection', zh: '订单 {order_no} 可领取', ta: 'ஆர்டர் {order_no} பெறத் தயார்' } },
        { key: 'grade.D1', values: { ms: 'Darjah 1', en: 'Year 1', zh: '一年级', ta: 'ஆண்டு 1' } },
        { key: 'grade.T1', values: { ms: 'Tingkatan 1', en: 'Form 1', zh: '中一', ta: 'படிவம் 1' } },
        { key: 'common.no_data', values: { ms: 'Tiada data untuk dipaparkan.', en: 'No data to display.', zh: '没有可显示的数据。', ta: 'காட்ட தரவு இல்லை.' } },
        { key: 'error.network', values: { ms: 'Gagal menghubungi pelayan. Sila cuba lagi.', en: 'Could not reach the server. Please try again.', zh: '', ta: '' } },
        { key: 'vendor.closed_notice', values: { ms: 'Koperasi ditutup sementara', en: 'Cooperative temporarily closed', zh: '合作社暂时关闭', ta: '' } }
    ],

    grades: [
        { code: 'PRA', level_type: 'preschool', sort_order: 0, names: { ms: 'Prasekolah', en: 'Preschool', zh: '学前班', ta: 'பாலர் பள்ளி' } },
        { code: 'D1', level_type: 'primary', sort_order: 1, names: { ms: 'Darjah 1', en: 'Year 1', zh: '一年级', ta: 'ஆண்டு 1' } },
        { code: 'D2', level_type: 'primary', sort_order: 2, names: { ms: 'Darjah 2', en: 'Year 2', zh: '二年级', ta: 'ஆண்டு 2' } },
        { code: 'D3', level_type: 'primary', sort_order: 3, names: { ms: 'Darjah 3', en: 'Year 3', zh: '三年级', ta: 'ஆண்டு 3' } },
        { code: 'D4', level_type: 'primary', sort_order: 4, names: { ms: 'Darjah 4', en: 'Year 4', zh: '四年级', ta: 'ஆண்டு 4' } },
        { code: 'D5', level_type: 'primary', sort_order: 5, names: { ms: 'Darjah 5', en: 'Year 5', zh: '五年级', ta: 'ஆண்டு 5' } },
        { code: 'D6', level_type: 'primary', sort_order: 6, names: { ms: 'Darjah 6', en: 'Year 6', zh: '六年级', ta: 'ஆண்டு 6' } },
        { code: 'T1', level_type: 'secondary', sort_order: 7, names: { ms: 'Tingkatan 1', en: 'Form 1', zh: '中一', ta: 'படிவம் 1' } },
        { code: 'T2', level_type: 'secondary', sort_order: 8, names: { ms: 'Tingkatan 2', en: 'Form 2', zh: '中二', ta: 'படிவம் 2' } },
        { code: 'T3', level_type: 'secondary', sort_order: 9, names: { ms: 'Tingkatan 3', en: 'Form 3', zh: '中三', ta: 'படிவம் 3' } },
        { code: 'T4', level_type: 'secondary', sort_order: 10, names: { ms: 'Tingkatan 4', en: 'Form 4', zh: '中四', ta: 'படிவம் 4' } },
        { code: 'T5', level_type: 'secondary', sort_order: 11, names: { ms: 'Tingkatan 5', en: 'Form 5', zh: '中五', ta: 'படிவம் 5' } },
        { code: 'T6', level_type: 'secondary', sort_order: 12, names: { ms: 'Tingkatan 6', en: 'Form 6', zh: '中六', ta: 'படிவம் 6' } }
    ],

    // template_key → pemegang tempat yang dibenarkan
    emailTemplateKeys: {
        order_new_vendor: ['{order_no}', '{school_name}', '{customer_name}', '{total}', '{order_url}'],
        order_receipt: ['{order_no}', '{customer_name}', '{school_name}', '{total}', '{items_table}', '{pickup_code}', '{order_url}'],
        order_ready: ['{order_no}', '{customer_name}', '{child_name}', '{child_class}', '{school_name}', '{opening_hours}'],
        order_collected: ['{order_no}', '{customer_name}', '{child_name}', '{collected_by}', '{collected_at}'],
        invoice_issued: ['{invoice_no}', '{vendor_name}', '{total}', '{due_date}', '{pay_url}'],
        password_reset: ['{name}', '{reset_url}', '{expires_minutes}']
    },

    emailTemplates: [
        { template_key: 'order_new_vendor', language_code: 'ms', subject: 'Pesanan baharu {order_no} — {school_name}', body_html: '<p>Pesanan baharu <strong>{order_no}</strong> daripada {customer_name}.</p>\n<p>Jumlah: {total}</p>\n<p><a href="{order_url}">Lihat pesanan</a></p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'order_new_vendor', language_code: 'en', subject: 'New order {order_no} — {school_name}', body_html: '<p>New order <strong>{order_no}</strong> from {customer_name}.</p>\n<p>Total: {total}</p>\n<p><a href="{order_url}">View order</a></p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'order_receipt', language_code: 'ms', subject: 'Resit pesanan {order_no}', body_html: '<p>Terima kasih {customer_name}.</p>\n{items_table}\n<p>Jumlah: <strong>{total}</strong></p>\n<p>Kod ambil: {pickup_code}</p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'order_receipt', language_code: 'en', subject: 'Receipt for order {order_no}', body_html: '<p>Thank you {customer_name}.</p>\n{items_table}\n<p>Total: <strong>{total}</strong></p>\n<p>Pickup code: {pickup_code}</p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'order_receipt', language_code: 'zh', subject: '订单 {order_no} 收据', body_html: '<p>谢谢 {customer_name}。</p>\n{items_table}\n<p>总额：<strong>{total}</strong></p>', updated_at: '2026-10-02T10:00:00+08:00' },
        { template_key: 'order_ready', language_code: 'ms', subject: 'Pesanan {order_no} sedia diambil', body_html: '<p>Barang untuk {child_name} ({child_class}) sedia diambil di koperasi {school_name}.</p>\n<p>Waktu operasi: {opening_hours}</p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'order_ready', language_code: 'en', subject: 'Order {order_no} is ready for collection', body_html: '<p>Items for {child_name} ({child_class}) are ready at the {school_name} cooperative.</p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'order_ready', language_code: 'zh', subject: '订单 {order_no} 可领取', body_html: '<p>{child_name}（{child_class}）的物品已可在 {school_name} 合作社领取。</p>', updated_at: '2026-10-02T10:00:00+08:00' },
        { template_key: 'order_ready', language_code: 'ta', subject: 'ஆர்டர் {order_no} பெறத் தயார்', body_html: '<p>{child_name} ({child_class}) பொருட்கள் {school_name} கூட்டுறவில் தயார்.</p>', updated_at: '2026-10-03T10:00:00+08:00' },
        { template_key: 'order_collected', language_code: 'ms', subject: 'Pesanan {order_no} telah diambil', body_html: '<p>Barang telah diambil oleh {collected_by} pada {collected_at}.</p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'invoice_issued', language_code: 'ms', subject: 'Invois {invoice_no} — KoperasiKu', body_html: '<p>Kepada {vendor_name},</p>\n<p>Invois {invoice_no} berjumlah {total} perlu dibayar sebelum {due_date}.</p>\n<p><a href="{pay_url}">Bayar sekarang</a></p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'password_reset', language_code: 'ms', subject: 'Tetapkan semula kata laluan', body_html: '<p>Hai {name},</p>\n<p><a href="{reset_url}">Tetapkan semula kata laluan</a> (sah {expires_minutes} minit).</p>', updated_at: '2026-10-01T10:00:00+08:00' },
        { template_key: 'password_reset', language_code: 'en', subject: 'Reset your password', body_html: '<p>Hi {name},</p>\n<p><a href="{reset_url}">Reset your password</a> (valid for {expires_minutes} minutes).</p>', updated_at: '2026-10-01T10:00:00+08:00' }
    ],

    announcements: [
        { id: 1, title: 'Penyelenggaraan sistem 1 November', body: 'Sistem akan ditutup dari 1:00 hingga 3:00 pagi untuk penyelenggaraan.', target: 'all', vendor_ids: [], published_at: '2026-10-22T09:00:00+08:00', created_by: 'Pemilik Platform', created_at: '2026-10-22T08:50:00+08:00' },
        { id: 2, title: 'Modul Pembukaan sekolah kini tersedia', body: 'Langgan modul ini untuk musim pre-order kit 2027.', target: 'all', vendor_ids: [], published_at: '2026-10-10T10:00:00+08:00', created_by: 'Pemilik Platform', created_at: '2026-10-10T09:00:00+08:00' },
        { id: 3, title: 'Peringatan invois tertunggak', body: 'Sila jelaskan invois tertunggak untuk mengelakkan penggantungan.', target: 'selected', vendor_ids: [4, 5], published_at: null, created_by: 'Siti Aminah', created_at: '2026-10-23T15:00:00+08:00' }
    ],

    settings: {
        platform_name: 'KoperasiKu', support_email: 'sokongan@koperasiku.my', support_phone: '03-12345678',
        smtp_host: 'smtp.koperasiku.my', smtp_port: 587, smtp_encryption: 'tls', smtp_username: 'noreply@koperasiku.my', smtp_password_set: true,
        mail_from_address: 'noreply@koperasiku.my', mail_from_name: 'KoperasiKu',
        sst_rate: 8, sst_registration_no: 'W10-1808-32000123', trial_days: 14, grace_days: 7, default_language: 'ms'
    },

    staff: [
        { id: 1, full_name: 'Pemilik Platform', email: 'owner@koperasiku.my', role: 'owner', is_active: true, totp_enabled: true, last_login_at: '2026-10-24T08:30:00+08:00' },
        { id: 2, full_name: 'Siti Aminah', email: 'siti@koperasiku.my', role: 'owner_staff', is_active: true, totp_enabled: true, last_login_at: '2026-10-23T17:10:00+08:00' },
        { id: 3, full_name: 'Raj Kumar', email: 'raj@koperasiku.my', role: 'owner_staff', is_active: false, totp_enabled: false, last_login_at: '2026-08-14T12:00:00+08:00' }
    ],

    // Pengguna owner semasa (meniru sesi)
    sessionUserId: 1,
    profile: { id: 1, full_name: 'Pemilik Platform', email: 'owner@koperasiku.my', language: 'ms', totp_enabled: true },

    auditLogs: [],

    planById: function (id) {
        return Mock.findById(MockOwner.plans, id);
    },

    vendorById: function (id) {
        return Mock.findById(MockOwner.vendors, id);
    },

    templateById: function (id) {
        return Mock.findById(MockOwner.templates, id);
    },

    // Rekod audit setiap tindakan sensitif (seperti AuditLog di B3)
    audit: function (action, entity, entityId, detail) {
        var actor = Mock.findById(MockOwner.staff, MockOwner.sessionUserId);
        MockOwner.auditLogs.unshift({
            id: Mock.nextId(MockOwner.auditLogs), actor_type: 'owner', actor_name: actor.full_name, vendor_code: null,
            action: action, entity: entity, entity_id: entityId, detail: detail || '', ip_address: '127.0.0.1', created_at: Mock.now()
        });
    }
};

// ---------- Log audit contoh ----------
(function seedAuditLogs() {
    var samples = [
        ['owner', 'Pemilik Platform', null, 'vendor.suspend', 'vendor', 5, 'Invois tertunggak'],
        ['owner', 'Siti Aminah', null, 'invoice.mark_paid', 'invoice', 2, 'TP-90211'],
        ['vendor_user', 'Ahmad bin Ismail', 'VENDOR01', 'gateway.update_keys', 'cooperative', 1, 'toyyibpay'],
        ['system', 'cron', null, 'invoice.generate', 'invoice', 5, 'INV-2026-0048'],
        ['vendor_user', 'Lim Mei Ling', 'VENDOR02', 'staff.create', 'vendor_user', 21, 'cashier'],
        ['owner', 'Pemilik Platform', null, 'plan.update', 'plan', 2, 'price_monthly 8900 → 9900'],
        ['customer', 'Rosnah Ali', null, 'account.delete', 'customer', 311, 'PDPA'],
        ['system', 'cron', null, 'vendor.past_due', 'vendor', 4, 'grace 7 hari'],
        ['owner', 'Siti Aminah', null, 'login.success', 'platform_user', 2, ''],
        ['vendor_user', 'Tan Wei Jie', 'VENDOR07', 'refund.create', 'order', 5521, 'RM 25.00']
    ];
    for (var i = 0; i < 34; i++) {
        var sample = samples[i % samples.length];
        var hour = 9 - (i % 6);
        var day = 24 - Math.floor(i / 6);
        MockOwner.auditLogs.push({
            id: 34 - i, actor_type: sample[0], actor_name: sample[1], vendor_code: sample[2], action: sample[3], entity: sample[4],
            entity_id: sample[5], detail: sample[6], ip_address: '175.139.' + (10 + i) + '.' + (100 + i),
            created_at: '2026-10-' + (day < 10 ? '0' + day : day) + 'T' + (hour < 10 ? '0' + hour : hour) + ':15:00+08:00'
        });
    }
})();

// ---------- Ringkasan (dikira dari data mock) ----------

function mockVendorSummary(vendor) {
    var plan = MockOwner.planById(vendor.plan_id);
    var coops = MockOwner.cooperatives.filter(function (coop) {
        return coop.vendor_id === vendor.id;
    });
    var subscription = MockOwner.subscriptions.filter(function (sub) {
        return sub.vendor_id === vendor.id;
    })[0];

    return {
        id: vendor.id, code: vendor.code, company_name: vendor.company_name, contact_name: vendor.contact_name,
        email: vendor.email, phone: vendor.phone, status: vendor.status, trial_ends_at: vendor.trial_ends_at,
        plan_id: vendor.plan_id, plan_name: plan ? plan.name : '', billing_cycle: vendor.billing_cycle,
        cooperative_count: coops.length, current_period_end: subscription ? subscription.current_period_end : null,
        created_at: vendor.created_at
    };
}

function mockCooperativeSummary(coop) {
    var vendor = MockOwner.vendorById(coop.vendor_id);
    var template = MockOwner.templateById(coop.template_id);
    return {
        id: coop.id, vendor_id: coop.vendor_id, vendor_code: vendor.code, vendor_name: vendor.company_name,
        slug: coop.slug, school_name: coop.school_name, school_code: coop.school_code, school_type: coop.school_type,
        state: coop.state, district: coop.district, status: coop.status,
        template_code: template.code, template_name: template.name, modules: coop.modules, created_at: coop.created_at
    };
}

function mockSubscriptionSummary(sub) {
    var vendor = MockOwner.vendorById(sub.vendor_id);
    var plan = MockOwner.planById(sub.plan_id);
    var coopCount = MockOwner.cooperatives.filter(function (coop) {
        return coop.vendor_id === sub.vendor_id && coop.status !== 'closed';
    }).length;

    return {
        id: sub.id, vendor_id: sub.vendor_id, vendor_code: vendor.code, vendor_name: vendor.company_name,
        plan_id: sub.plan_id, plan_name: plan.name, billing_cycle: sub.billing_cycle, status: sub.status,
        started_at: sub.started_at, current_period_start: sub.current_period_start, current_period_end: sub.current_period_end,
        cooperative_count: coopCount
    };
}

function mockInvoiceSummary(invoice) {
    var vendor = MockOwner.vendorById(invoice.vendor_id);
    return {
        id: invoice.id, invoice_no: invoice.invoice_no, vendor_id: invoice.vendor_id, vendor_code: vendor.code,
        vendor_name: vendor.company_name, period_start: invoice.period_start, period_end: invoice.period_end,
        subtotal: invoice.subtotal, tax: invoice.tax, total: invoice.total, status: invoice.status,
        issued_at: invoice.issued_at, due_at: invoice.due_at, paid_at: invoice.paid_at
    };
}

function mockPlanWithCounts(plan) {
    var copy = JSON.parse(JSON.stringify(plan));
    copy.vendor_count = MockOwner.vendors.filter(function (vendor) {
        return vendor.plan_id === plan.id && vendor.status !== 'cancelled';
    }).length;
    return copy;
}

function mockModuleWithCounts(module) {
    var copy = JSON.parse(JSON.stringify(module));
    copy.cooperative_count = MockOwner.cooperatives.filter(function (coop) {
        return coop.modules.indexOf(module.code) !== -1;
    }).length;
    return copy;
}

function mockTemplateWithCounts(template) {
    var copy = JSON.parse(JSON.stringify(template));
    copy.cooperative_count = MockOwner.cooperatives.filter(function (coop) {
        return coop.template_id === template.id;
    }).length;
    return copy;
}

function mockSortBy(rows, field) {
    return rows.slice().sort(function (a, b) {
        return a[field] - b[field];
    });
}

// Ralat jika nilai medan sudah digunakan baris lain
function mockDuplicateError(rows, field, value, ownId) {
    var clash = rows.filter(function (row) {
        return String(row[field]).toLowerCase() === String(value).toLowerCase() && row.id !== Number(ownId);
    });
    return clash.length ? T('error.duplicate') : null;
}

// ---------- Log masuk ----------

// Langkah 1: e-mel + kata laluan → perlu 2FA (wajib untuk owner). Demo: kata laluan "salah" → ralat
Mock.register('owner/auth/login', function (body) {
    if (body.password === 'salah') {
        return Mock.fail(T('auth.invalid_credentials'));
    }
    return Mock.ok({ requires_2fa: true });
});

// Langkah 2: kod TOTP 6 digit. Demo: "000000" ditolak
Mock.register('owner/auth/verify-2fa', function (body) {
    if (body.code === '000000') {
        return Mock.fail(T('auth.otp_invalid'), { code: T('auth.otp_invalid') });
    }
    return Mock.ok({ user: { id: 1, name: 'Pemilik Platform', role: 'owner' } });
});

// ---------- Papan pemuka ----------

Mock.register('owner/dashboard/summary', function () {
    var counts = { trial: 0, active: 0, past_due: 0, suspended: 0, cancelled: 0 };
    MockOwner.vendors.forEach(function (vendor) {
        counts[vendor.status] += 1;
    });

    var expiring = MockOwner.subscriptions.filter(function (sub) {
        return ['active', 'trial', 'past_due'].indexOf(sub.status) !== -1 && sub.current_period_end <= '2026-11-10';
    }).map(mockSubscriptionSummary);

    return Mock.ok({
        mrr: 52800,
        mrr_change_percent: 6.4,
        vendors: counts,
        cooperatives_total: MockOwner.cooperatives.length,
        cooperatives_active: MockOwner.cooperatives.filter(function (coop) { return coop.status === 'active'; }).length,
        orders_this_month: 1284,
        orders_value_this_month: 9876500,
        invoices_unpaid: MockOwner.invoices.filter(function (inv) { return inv.status === 'issued'; }).length,
        mrr_history: [
            { month: '2026-05', amount: 31200 }, { month: '2026-06', amount: 35400 }, { month: '2026-07', amount: 39900 },
            { month: '2026-08', amount: 44100 }, { month: '2026-09', amount: 49600 }, { month: '2026-10', amount: 52800 }
        ],
        expiring: expiring
    });
});

// ---------- Vendor ----------

Mock.register('owner/vendors/list', function (params) {
    var rows = Mock.filterBy(MockOwner.vendors, params, ['status', 'plan_id']).map(mockVendorSummary);
    return Mock.ok(Mock.paginate(rows, params, ['code', 'company_name', 'contact_name', 'email', 'phone']));
});

// Pilihan ringkas untuk dropdown (pengumuman, tapisan)
Mock.register('owner/vendors/options', function () {
    return Mock.ok(MockOwner.vendors.map(function (vendor) {
        return { id: vendor.id, code: vendor.code, company_name: vendor.company_name, status: vendor.status };
    }));
});

Mock.register('owner/vendors/get', function (params) {
    var vendor = MockOwner.vendorById(params.id);
    if (!vendor) {
        return Mock.fail(T('error.not_found'));
    }

    var data = JSON.parse(JSON.stringify(vendor));
    data.plan_name = MockOwner.planById(vendor.plan_id).name;
    data.subscription = mockSubscriptionSummary(MockOwner.subscriptions.filter(function (sub) {
        return sub.vendor_id === vendor.id;
    })[0]);
    data.cooperatives = MockOwner.cooperatives.filter(function (coop) {
        return coop.vendor_id === vendor.id;
    }).map(mockCooperativeSummary);
    data.invoices = MockOwner.invoices.filter(function (inv) {
        return inv.vendor_id === vendor.id;
    }).map(mockInvoiceSummary);
    data.notes = MockOwner.vendorNotes.filter(function (note) {
        return note.vendor_id === vendor.id;
    }).reverse();

    return Mock.ok(data);
});

// status: 'active' (lulus / aktifkan semula) atau 'suspended' (gantung, sebab wajib)
Mock.register('owner/vendors/set-status', function (body) {
    var vendor = MockOwner.vendorById(body.id);
    if (!vendor) {
        return Mock.fail(T('error.not_found'));
    }
    if (['active', 'suspended'].indexOf(body.status) === -1) {
        return Mock.fail(T('error.invalid'));
    }
    if (body.status === 'suspended' && !String(body.reason || '').trim()) {
        return Mock.fail(T('error.required'), { reason: T('error.required') });
    }

    vendor.status = body.status;
    MockOwner.subscriptions.forEach(function (sub) {
        if (sub.vendor_id === vendor.id) {
            sub.status = body.status;
        }
    });
    if (body.reason) {
        MockOwner.vendorNotes.push({ id: Mock.nextId(MockOwner.vendorNotes), vendor_id: vendor.id, body: body.reason, created_by: 'Pemilik Platform', created_at: Mock.now() });
    }
    MockOwner.audit('vendor.' + (body.status === 'active' ? 'activate' : 'suspend'), 'vendor', vendor.id, body.reason);
    return Mock.ok(mockVendorSummary(vendor));
});

Mock.register('owner/vendors/add-note', function (body) {
    var errors = Mock.requireFields(body, ['body']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    var note = { id: Mock.nextId(MockOwner.vendorNotes), vendor_id: Number(body.id), body: body.body, created_by: 'Pemilik Platform', created_at: Mock.now() };
    MockOwner.vendorNotes.push(note);
    return Mock.ok(note);
});

// ---------- Koperasi (baca sahaja) ----------

Mock.register('owner/cooperatives/list', function (params) {
    var rows = Mock.filterBy(MockOwner.cooperatives, params, ['vendor_id', 'status', 'school_type']).map(mockCooperativeSummary);
    return Mock.ok(Mock.paginate(rows, params, ['school_name', 'school_code', 'slug', 'vendor_code', 'vendor_name', 'district', 'state']));
});

// ---------- Pelan ----------

Mock.register('owner/plans/list', function () {
    return Mock.ok(mockSortBy(MockOwner.plans, 'sort_order').map(mockPlanWithCounts));
});

Mock.register('owner/plans/save', function (body) {
    var errors = Mock.requireFields(body, ['code', 'name', 'price_monthly', 'price_yearly']);
    var duplicate = mockDuplicateError(MockOwner.plans, 'code', body.code || '', body.id);
    if (duplicate) {
        errors.code = duplicate;
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var plan = body.id ? MockOwner.planById(body.id) : null;
    if (!plan) {
        plan = { id: Mock.nextId(MockOwner.plans) };
        MockOwner.plans.push(plan);
        // Lalai: koperasi tambahan sama harga dengan pelan
        MockOwner.tiers.push({ id: Mock.nextId(MockOwner.tiers), plan_id: plan.id, from_coop: 2, to_coop: null, price_monthly: body.price_monthly, price_yearly: body.price_yearly });
    }
    ['code', 'name', 'price_monthly', 'price_yearly', 'max_cooperatives', 'max_products', 'max_staff', 'features', 'is_active', 'sort_order'].forEach(function (field) {
        plan[field] = body[field];
    });
    plan.code = String(plan.code).toUpperCase();
    MockOwner.audit('plan.save', 'plan', plan.id, plan.code);
    return Mock.ok(mockPlanWithCounts(plan));
});

Mock.register('owner/plans/delete', function (body) {
    var plan = MockOwner.planById(body.id);
    if (!plan) {
        return Mock.fail(T('error.not_found'));
    }
    if (mockPlanWithCounts(plan).vendor_count > 0) {
        return Mock.fail(T('plan.delete_in_use'));
    }
    MockOwner.plans = MockOwner.plans.filter(function (row) { return row.id !== plan.id; });
    MockOwner.tiers = MockOwner.tiers.filter(function (row) { return row.plan_id !== plan.id; });
    MockOwner.audit('plan.delete', 'plan', plan.id, plan.code);
    return Mock.ok({ id: plan.id });
});

// ---------- Harga koperasi tambahan (plan_coop_tiers) ----------

Mock.register('owner/plans/tiers', function (params) {
    var plan = MockOwner.planById(params.plan_id);
    if (!plan) {
        return Mock.fail(T('error.not_found'));
    }
    var tiers = MockOwner.tiers.filter(function (tier) {
        return tier.plan_id === plan.id;
    }).sort(function (a, b) {
        return a.from_coop - b.from_coop;
    });
    return Mock.ok({ plan: mockPlanWithCounts(plan), tiers: tiers });
});

// Peringkat mesti berturutan: mula 2, setiap from = to sebelumnya + 1, terakhir to = null
Mock.register('owner/plans/save-tiers', function (body) {
    var plan = MockOwner.planById(body.plan_id);
    if (!plan) {
        return Mock.fail(T('error.not_found'));
    }
    var tiers = body.tiers || [];
    var expectedFrom = 2;
    for (var i = 0; i < tiers.length; i++) {
        var tier = tiers[i];
        var isLast = (i === tiers.length - 1);
        var badRange = tier.from_coop !== expectedFrom || (isLast ? tier.to_coop !== null : (tier.to_coop === null || tier.to_coop < tier.from_coop));
        if (badRange || tier.price_monthly === null || tier.price_yearly === null) {
            return Mock.fail(T('coop_pricing.invalid_tiers'));
        }
        expectedFrom = tier.to_coop + 1;
    }
    if (tiers.length === 0) {
        return Mock.fail(T('coop_pricing.invalid_tiers'));
    }

    MockOwner.tiers = MockOwner.tiers.filter(function (row) { return row.plan_id !== plan.id; });
    tiers.forEach(function (tierInput) {
        MockOwner.tiers.push({ id: Mock.nextId(MockOwner.tiers), plan_id: plan.id, from_coop: tierInput.from_coop, to_coop: tierInput.to_coop,
            price_monthly: tierInput.price_monthly, price_yearly: tierInput.price_yearly });
    });
    MockOwner.audit('plan.tiers_update', 'plan', plan.id, tiers.length + ' peringkat');
    return Mock.ok({ plan_id: plan.id, tiers: tiers });
});

// ---------- Modul add-on ----------

Mock.register('owner/modules/list', function () {
    return Mock.ok(mockSortBy(MockOwner.modules, 'sort_order').map(mockModuleWithCounts));
});

Mock.register('owner/modules/save', function (body) {
    var errors = Mock.requireFields(body, ['code', 'names.ms', 'price_monthly', 'price_yearly']);
    if (body.code && !/^[a-z][a-z0-9_]{1,30}$/.test(body.code)) {
        errors.code = T('module.code_format');
    }
    var duplicate = mockDuplicateError(MockOwner.modules, 'code', body.code || '', body.id);
    if (duplicate) {
        errors.code = duplicate;
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var module = body.id ? Mock.findById(MockOwner.modules, body.id) : null;
    if (!module) {
        module = { id: Mock.nextId(MockOwner.modules), is_core: false };
        MockOwner.modules.push(module);
    }
    ['code', 'names', 'descriptions', 'price_monthly', 'price_yearly', 'is_active', 'sort_order'].forEach(function (field) {
        module[field] = body[field];
    });
    MockOwner.audit('module.save', 'module', module.id, module.code);
    return Mock.ok(mockModuleWithCounts(module));
});

// ---------- Template frontend ----------

Mock.register('owner/templates/list', function () {
    return Mock.ok(MockOwner.templates.map(mockTemplateWithCounts));
});

Mock.register('owner/templates/save', function (body) {
    var template = Mock.findById(MockOwner.templates, body.id);
    if (!template) {
        return Mock.fail(T('error.not_found'));
    }
    var errors = Mock.requireFields(body, ['name']);
    if (!body.is_free) {
        Object.assign(errors, Mock.requireFields(body, ['price_monthly', 'price_yearly']));
    }
    if (template.code === 'template1' && (!body.is_free || !body.is_active)) {
        return Mock.fail(T('template.template1_locked'));
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    template.name = body.name;
    template.description = body.description;
    template.is_free = body.is_free;
    template.is_active = body.is_active;
    template.price_monthly = body.is_free ? 0 : body.price_monthly;
    template.price_yearly = body.is_free ? 0 : body.price_yearly;
    MockOwner.audit('template.save', 'template', template.id, template.code);
    return Mock.ok(mockTemplateWithCounts(template));
});

// ---------- Langganan & invois ----------

Mock.register('owner/subscriptions/list', function (params) {
    var rows = Mock.filterBy(MockOwner.subscriptions, params, ['status', 'billing_cycle', 'plan_id']).map(mockSubscriptionSummary);
    return Mock.ok(Mock.paginate(rows, params, ['vendor_code', 'vendor_name', 'plan_name']));
});

Mock.register('owner/invoices/list', function (params) {
    var rows = Mock.filterBy(MockOwner.invoices, params, ['status', 'vendor_id']).slice().sort(function (a, b) {
        return b.id - a.id;
    }).map(mockInvoiceSummary);
    return Mock.ok(Mock.paginate(rows, params, ['invoice_no', 'vendor_code', 'vendor_name']));
});

Mock.register('owner/invoices/get', function (params) {
    var invoice = Mock.findById(MockOwner.invoices, params.id);
    if (!invoice) {
        return Mock.fail(T('error.not_found'));
    }
    var data = mockInvoiceSummary(invoice);
    var vendor = MockOwner.vendorById(invoice.vendor_id);
    data.vendor = { id: vendor.id, code: vendor.code, company_name: vendor.company_name, registration_no: vendor.registration_no, address: vendor.address, email: vendor.email };
    data.lines = invoice.lines;
    data.payments = invoice.payments;
    data.sst_rate = MockOwner.settings.sst_rate;
    return Mock.ok(data);
});

// Tanda dibayar secara manual (pindahan bank / tunai / lain-lain)
Mock.register('owner/invoices/mark-paid', function (body) {
    var invoice = Mock.findById(MockOwner.invoices, body.id);
    if (!invoice) {
        return Mock.fail(T('error.not_found'));
    }
    if (invoice.status !== 'issued') {
        return Mock.fail(T('invoice.cannot_mark_paid'));
    }
    var errors = Mock.requireFields(body, ['method', 'reference', 'paid_at']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var paidAt = body.paid_at + 'T12:00:00+08:00';
    invoice.status = 'paid';
    invoice.paid_at = paidAt;
    invoice.payments.push({ method: body.method, gateway_code: null, reference: body.reference, amount: invoice.total, paid_at: paidAt, note: body.note || '' });
    MockOwner.audit('invoice.mark_paid', 'invoice', invoice.id, body.reference);
    return Mock.ok(mockInvoiceSummary(invoice));
});

Mock.register('owner/invoices/issue', function (body) {
    var invoice = Mock.findById(MockOwner.invoices, body.id);
    if (!invoice || invoice.status !== 'draft') {
        return Mock.fail(T('error.invalid'));
    }
    invoice.status = 'issued';
    invoice.issued_at = Mock.now();
    invoice.due_at = '2026-10-31T23:59:59+08:00';
    MockOwner.audit('invoice.issue', 'invoice', invoice.id, invoice.invoice_no);
    return Mock.ok(mockInvoiceSummary(invoice));
});

Mock.register('owner/invoices/void', function (body) {
    var invoice = Mock.findById(MockOwner.invoices, body.id);
    if (!invoice || ['draft', 'issued'].indexOf(invoice.status) === -1) {
        return Mock.fail(T('error.invalid'));
    }
    invoice.status = 'void';
    MockOwner.audit('invoice.void', 'invoice', invoice.id, invoice.invoice_no);
    return Mock.ok(mockInvoiceSummary(invoice));
});

// ---------- Gateway ----------

Mock.register('owner/gateways/list', function () {
    var drivers = MockOwner.gateways.map(function (gateway) {
        var copy = JSON.parse(JSON.stringify(gateway));
        copy.vendor_count = gateway.code === 'toyyibpay' ? 4 : (gateway.code === 'billplz' ? 1 : 0);
        return copy;
    });
    return Mock.ok({ drivers: drivers, owner_gateway: MockOwner.ownerGateway });
});

Mock.register('owner/gateways/toggle', function (body) {
    var gateway = MockOwner.gateways.filter(function (row) { return row.code === body.code; })[0];
    if (!gateway) {
        return Mock.fail(T('error.not_found'));
    }
    gateway.allowed_for_vendors = Boolean(body.allowed);
    MockOwner.audit('gateway.toggle', 'gateway', gateway.code, body.allowed ? 'allowed' : 'blocked');
    return Mock.ok({ code: gateway.code, allowed_for_vendors: gateway.allowed_for_vendors });
});

// Medan rahsia kosong = kekalkan nilai lama
Mock.register('owner/gateways/save-owner', function (body) {
    var gateway = MockOwner.gateways.filter(function (row) { return row.code === body.driver_code; })[0];
    if (!gateway) {
        return Mock.fail(T('error.required'), { driver_code: T('error.required') });
    }

    var keepSecrets = body.driver_code === MockOwner.ownerGateway.driver_code ? MockOwner.ownerGateway.secrets_set : [];
    var values = {};
    var secretsSet = [];
    var errors = {};
    var credentials = body.credentials || {};

    gateway.fields.forEach(function (field) {
        var value = String(credentials[field.name] || '').trim();
        if (field.secret) {
            if (value !== '' || keepSecrets.indexOf(field.name) !== -1) {
                secretsSet.push(field.name);
            } else {
                errors['credentials.' + field.name] = T('error.required');
            }
        } else if (value === '') {
            errors['credentials.' + field.name] = T('error.required');
        } else {
            values[field.name] = value;
        }
    });
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    MockOwner.ownerGateway = { driver_code: gateway.code, sandbox: Boolean(body.sandbox), values: values, secrets_set: secretsSet };
    MockOwner.audit('gateway.owner_update', 'gateway', gateway.code, '');
    return Mock.ok(MockOwner.ownerGateway);
});

// ---------- Bahasa & terjemahan ----------

Mock.register('owner/translations/list', function (params) {
    var languages = ['ms', 'en', 'zh', 'ta'];
    var rows = MockOwner.translations.map(function (row) {
        var missing = languages.filter(function (code) {
            return !String(row.values[code] || '').trim();
        });
        return { key: row.key, group: row.key.split('.')[0], values: row.values, missing: missing };
    }).filter(function (row) {
        if (params.missing && row.missing.indexOf(params.missing) === -1 && !(params.missing === 'any' && row.missing.length)) {
            return false;
        }
        return !params.group || row.group === params.group;
    });

    var q = String(params.q || '').toLowerCase();
    if (q) {
        rows = rows.filter(function (row) {
            return row.key.toLowerCase().indexOf(q) !== -1 || languages.some(function (code) {
                return String(row.values[code] || '').toLowerCase().indexOf(q) !== -1;
            });
        });
    }

    var data = Mock.paginate(rows, { page: params.page, per_page: params.per_page }, []);
    data.groups = MockOwner.translations.map(function (row) {
        return row.key.split('.')[0];
    }).filter(function (group, index, all) {
        return all.indexOf(group) === index;
    }).sort();
    data.missing_counts = {};
    languages.forEach(function (code) {
        data.missing_counts[code] = MockOwner.translations.filter(function (row) {
            return !String(row.values[code] || '').trim();
        }).length;
    });
    return Mock.ok(data);
});

// Nilai Melayu wajib — ia jatuh balik untuk bahasa lain
Mock.register('owner/translations/save', function (body) {
    var row = MockOwner.translations.filter(function (item) { return item.key === body.key; })[0];
    if (!row) {
        return Mock.fail(T('error.not_found'));
    }
    if (!String((body.values || {}).ms || '').trim()) {
        return Mock.fail(T('translation.ms_required'), { 'values.ms': T('error.required') });
    }
    row.values = body.values;
    MockOwner.audit('translation.update', 'translation', row.key, '');
    return Mock.ok(row);
});

// ---------- Darjah & tingkatan ----------

Mock.register('owner/grades/list', function () {
    return Mock.ok(mockSortBy(MockOwner.grades, 'sort_order'));
});

Mock.register('owner/grades/save', function (body) {
    var grade = MockOwner.grades.filter(function (row) { return row.code === body.code; })[0];
    if (!grade) {
        return Mock.fail(T('error.not_found'));
    }
    var errors = Mock.requireFields(body, ['names.ms', 'names.en', 'names.zh', 'names.ta', 'sort_order']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    grade.names = body.names;
    grade.level_type = body.level_type;
    grade.sort_order = body.sort_order;
    MockOwner.audit('grade.update', 'grade_level', grade.code, '');
    return Mock.ok(grade);
});

// ---------- Templat e-mel ----------

Mock.register('owner/email-templates/list', function () {
    var items = Object.keys(MockOwner.emailTemplateKeys).map(function (key) {
        var languages = {};
        ['ms', 'en', 'zh', 'ta'].forEach(function (code) {
            var found = MockOwner.emailTemplates.filter(function (row) {
                return row.template_key === key && row.language_code === code;
            })[0];
            languages[code] = found ? { subject: found.subject, updated_at: found.updated_at } : null;
        });
        return { template_key: key, languages: languages };
    });
    return Mock.ok(items);
});

Mock.register('owner/email-templates/get', function (params) {
    var placeholders = MockOwner.emailTemplateKeys[params.template_key];
    if (!placeholders) {
        return Mock.fail(T('error.not_found'));
    }
    var found = MockOwner.emailTemplates.filter(function (row) {
        return row.template_key === params.template_key && row.language_code === params.language_code;
    })[0];
    return Mock.ok({
        template_key: params.template_key, language_code: params.language_code,
        subject: found ? found.subject : '', body_html: found ? found.body_html : '',
        updated_at: found ? found.updated_at : null, placeholders: placeholders
    });
});

Mock.register('owner/email-templates/save', function (body) {
    var placeholders = MockOwner.emailTemplateKeys[body.template_key];
    if (!placeholders) {
        return Mock.fail(T('error.not_found'));
    }
    var errors = Mock.requireFields(body, ['subject', 'body_html']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    // Pemegang tempat yang tidak dikenali → ralat (elak e-mel dengan {salah_eja})
    var used = (body.subject + ' ' + body.body_html).match(/\{[a-z_]+\}/g) || [];
    var unknown = used.filter(function (item) { return placeholders.indexOf(item) === -1; });
    if (unknown.length) {
        return Mock.fail(T('email_template.unknown_placeholder', { list: unknown.join(', ') }), { body_html: T('email_template.unknown_placeholder', { list: unknown.join(', ') }) });
    }

    var row = MockOwner.emailTemplates.filter(function (item) {
        return item.template_key === body.template_key && item.language_code === body.language_code;
    })[0];
    if (!row) {
        row = { template_key: body.template_key, language_code: body.language_code };
        MockOwner.emailTemplates.push(row);
    }
    row.subject = body.subject;
    row.body_html = body.body_html;
    row.updated_at = Mock.now();
    MockOwner.audit('email_template.update', 'email_template', body.template_key + '/' + body.language_code, '');
    return Mock.ok(row);
});

// ---------- Pengumuman vendor ----------

function mockAnnouncementSummary(item) {
    var copy = JSON.parse(JSON.stringify(item));
    copy.vendor_names = item.vendor_ids.map(function (id) {
        return MockOwner.vendorById(id).code;
    });
    return copy;
}

Mock.register('owner/announcements/list', function (params) {
    var rows = MockOwner.announcements.slice().sort(function (a, b) {
        return b.id - a.id;
    }).map(mockAnnouncementSummary);
    return Mock.ok(Mock.paginate(rows, params, ['title', 'body']));
});

// publish: true = terbit sekarang, false = simpan sebagai draf
Mock.register('owner/announcements/save', function (body) {
    var errors = Mock.requireFields(body, ['title', 'body', 'target']);
    if (body.target === 'selected' && (!body.vendor_ids || body.vendor_ids.length === 0)) {
        errors.vendor_ids = T('announcement.choose_vendor');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var item = body.id ? Mock.findById(MockOwner.announcements, body.id) : null;
    if (item && item.published_at) {
        return Mock.fail(T('announcement.already_published'));
    }
    if (!item) {
        item = { id: Mock.nextId(MockOwner.announcements), created_by: 'Pemilik Platform', created_at: Mock.now(), published_at: null };
        MockOwner.announcements.push(item);
    }
    item.title = body.title;
    item.body = body.body;
    item.target = body.target;
    item.vendor_ids = body.target === 'selected' ? body.vendor_ids.map(Number) : [];
    if (body.publish) {
        item.published_at = Mock.now();
    }
    MockOwner.audit(body.publish ? 'announcement.publish' : 'announcement.save', 'announcement', item.id, item.title);
    return Mock.ok(mockAnnouncementSummary(item));
});

Mock.register('owner/announcements/delete', function (body) {
    var item = Mock.findById(MockOwner.announcements, body.id);
    if (!item) {
        return Mock.fail(T('error.not_found'));
    }
    MockOwner.announcements = MockOwner.announcements.filter(function (row) { return row.id !== item.id; });
    MockOwner.audit('announcement.delete', 'announcement', item.id, item.title);
    return Mock.ok({ id: item.id });
});

// ---------- Log audit ----------

Mock.register('owner/audit-logs/list', function (params) {
    var rows = Mock.filterBy(MockOwner.auditLogs, params, ['actor_type']).filter(function (row) {
        var day = row.created_at.slice(0, 10);
        return (!params.date_from || day >= params.date_from) && (!params.date_to || day <= params.date_to);
    });
    return Mock.ok(Mock.paginate(rows, params, ['action', 'actor_name', 'vendor_code', 'entity', 'detail', 'ip_address']));
});

// ---------- Tetapan platform ----------

Mock.register('owner/settings/get', function () {
    return Mock.ok(MockOwner.settings);
});

// smtp_password kosong = kekalkan kata laluan lama
Mock.register('owner/settings/save', function (body) {
    var errors = Mock.requireFields(body, ['platform_name', 'support_email', 'smtp_host', 'smtp_port', 'mail_from_address', 'trial_days', 'grace_days']);
    if (body.sst_rate !== null && (body.sst_rate < 0 || body.sst_rate > 100)) {
        errors.sst_rate = T('error.invalid');
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var passwordSet = MockOwner.settings.smtp_password_set || Boolean(body.smtp_password);
    delete body.smtp_password;
    MockOwner.settings = Object.assign({}, MockOwner.settings, body, { smtp_password_set: passwordSet });
    MockOwner.audit('settings.update', 'platform_settings', 'platform', '');
    return Mock.ok(MockOwner.settings);
});

Mock.register('owner/settings/test-email', function (body) {
    if (!body.to) {
        return Mock.fail(T('error.required'), { to: T('error.required') });
    }
    return Mock.ok({ queued: true });
});

// ---------- Kakitangan owner ----------

Mock.register('owner/staff/list', function () {
    return Mock.ok(MockOwner.staff);
});

// Kakitangan baharu menerima e-mel jemputan untuk tetapkan kata laluan & 2FA
Mock.register('owner/staff/save', function (body) {
    var errors = Mock.requireFields(body, ['full_name', 'email', 'role']);
    var duplicate = mockDuplicateError(MockOwner.staff, 'email', body.email || '', body.id);
    if (duplicate) {
        errors.email = duplicate;
    }
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }

    var person = body.id ? Mock.findById(MockOwner.staff, body.id) : null;
    if (person && person.id === MockOwner.sessionUserId && body.role !== 'owner') {
        return Mock.fail(T('staff.cannot_demote_self'));
    }
    if (!person) {
        person = { id: Mock.nextId(MockOwner.staff), is_active: true, totp_enabled: false, last_login_at: null };
        MockOwner.staff.push(person);
    }
    person.full_name = body.full_name;
    person.email = body.email;
    person.role = body.role;
    MockOwner.audit('staff.save', 'platform_user', person.id, person.email);
    return Mock.ok(person);
});

Mock.register('owner/staff/set-active', function (body) {
    var person = Mock.findById(MockOwner.staff, body.id);
    if (!person) {
        return Mock.fail(T('error.not_found'));
    }
    if (person.id === MockOwner.sessionUserId) {
        return Mock.fail(T('staff.cannot_disable_self'));
    }
    person.is_active = Boolean(body.is_active);
    MockOwner.audit(person.is_active ? 'staff.activate' : 'staff.deactivate', 'platform_user', person.id, person.email);
    return Mock.ok(person);
});

Mock.register('owner/staff/reset-2fa', function (body) {
    var person = Mock.findById(MockOwner.staff, body.id);
    if (!person) {
        return Mock.fail(T('error.not_found'));
    }
    person.totp_enabled = false;
    MockOwner.audit('staff.reset_2fa', 'platform_user', person.id, person.email);
    return Mock.ok(person);
});

// ---------- Profil & 2FA pengguna semasa ----------

Mock.register('owner/profile/get', function () {
    return Mock.ok(MockOwner.profile);
});

Mock.register('owner/profile/save', function (body) {
    var errors = Mock.requireFields(body, ['full_name', 'email']);
    if (Mock.hasErrors(errors)) {
        return Mock.fail(T('error.check_form'), errors);
    }
    MockOwner.profile.full_name = body.full_name;
    MockOwner.profile.email = body.email;
    MockOwner.profile.language = body.language;
    return Mock.ok(MockOwner.profile);
});

// Demo: kata laluan semasa "salah" → ralat
Mock.register('owner/profile/change-password', function (body) {
    if (body.current_password === 'salah') {
        return Mock.fail(T('profile.current_password_wrong'), { current_password: T('profile.current_password_wrong') });
    }
    if (String(body.new_password || '').length < 10) {
        return Mock.fail(T('profile.password_too_short'), { new_password: T('profile.password_too_short') });
    }
    MockOwner.audit('profile.change_password', 'platform_user', MockOwner.sessionUserId, '');
    return Mock.ok({ changed: true });
});

// Rahsia TOTP baharu (Peringkat 2: dijana di pelayan, disimpan disulitkan selepas disahkan)
Mock.register('owner/two-factor/setup', function () {
    var secret = 'JBSWY3DPEHPK3PXP';
    return Mock.ok({
        enabled: MockOwner.profile.totp_enabled,
        secret: secret,
        otpauth_url: 'otpauth://totp/KoperasiKu:' + encodeURIComponent(MockOwner.profile.email) + '?secret=' + secret + '&issuer=KoperasiKu'
    });
});

// Demo: kod "000000" ditolak
Mock.register('owner/two-factor/enable', function (body) {
    if (!/^[0-9]{6}$/.test(body.code || '') || body.code === '000000') {
        return Mock.fail(T('auth.otp_invalid'), { code: T('auth.otp_invalid') });
    }
    MockOwner.profile.totp_enabled = true;
    MockOwner.audit('two_factor.enable', 'platform_user', MockOwner.sessionUserId, '');
    return Mock.ok({
        enabled: true,
        recovery_codes: ['7KQ2-M9XD', 'P4LW-8ZRC', 'H3VN-2TBY', 'Q8FJ-6KSE', 'D5RA-9MXU', 'W2CT-7PLG', 'B6YH-3NQZ', 'L9EK-4VDW']
    });
});

// Owner tidak boleh mematikan 2FA (wajib) — hanya jana semula kod pemulihan
Mock.register('owner/two-factor/regenerate-codes', function (body) {
    if (!/^[0-9]{6}$/.test(body.code || '') || body.code === '000000') {
        return Mock.fail(T('auth.otp_invalid'), { code: T('auth.otp_invalid') });
    }
    return Mock.ok({ recovery_codes: ['R1AB-2CDE', 'F3GH-4IJK', 'L5MN-6OPQ', 'R7ST-8UVW', 'X9YZ-1ABC', 'D2EF-3GHI', 'J4KL-5MNO', 'P6QR-7STU'] });
});
