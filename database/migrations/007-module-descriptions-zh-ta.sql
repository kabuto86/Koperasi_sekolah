SET NAMES utf8mb4;
-- B14 — Terjemahan: keterangan modul add-on dalam Bahasa Cina & Tamil (sebelum ini hanya ms/en → 8 kunci hilang setiap bahasa).
-- Pemasangan baharu: seed.sql sudah mengandungi baris ini. Tiada perubahan jadual (schema.sql tidak berubah).
--   mysql --default-character-set=utf8mb4 -u koperasi_user -p koperasi_saas < database/migrations/007-module-descriptions-zh-ta.sql
-- Selepas import: php tools/generate-lang.php (jana semula storage/lang/*.php)

INSERT IGNORE INTO translations (language_code, translation_key, value) VALUES
('zh', 'module.news.description',            '向该合作社的家长发布新闻。'),
('ta', 'module.news.description',            'அந்தக் கூட்டுறவின் பெற்றோருக்குச் செய்திகளை வெளியிடுங்கள்.'),
('zh', 'module.clearance.description',       '带有期限和清仓标签的特价。'),
('ta', 'module.clearance.description',       'காலவரம்பும் Clearance பதக்கமும் கொண்ட சிறப்பு விலை.'),
('zh', 'module.calendar.description',        '假期自动停止接受订单。'),
('ta', 'module.calendar.description',        'விடுமுறை நாட்களில் ஆர்டர்கள் தானாக மூடப்படும்.'),
('zh', 'module.school_opening.description',  '文具套装预购季和领取时段。'),
('ta', 'module.school_opening.description',  'கிட் முன்பதிவுப் பருவமும் பொருள் பெறும் நேரங்களும்.'),
('zh', 'module.mobile_app.description',      '家长使用带推送通知的 Flutter 应用。'),
('ta', 'module.mobile_app.description',      'பெற்றோர் புஷ் அறிவிப்புகளுடன் Flutter செயலியைப் பயன்படுத்துகின்றனர்.'),
('zh', 'module.whatsapp_sms.description',    '"可领取"和"已领取"消息。'),
('ta', 'module.whatsapp_sms.description',    '"பெறத் தயார்", "பெறப்பட்டது" செய்திகள்.'),
('zh', 'module.advanced_report.description', '年度比较和套装分析。'),
('ta', 'module.advanced_report.description', 'ஆண்டு ஒப்பீடும் கிட் பகுப்பாய்வும்.'),
('zh', 'module.membership.description',      '会员、股份和股息。'),
('ta', 'module.membership.description',      'உறுப்பினர்கள், பங்குகள், ஈவுத்தொகை.');
