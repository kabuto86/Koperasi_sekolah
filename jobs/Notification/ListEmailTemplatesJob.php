<?php
// Ringkasan templat e-mel: setiap kunci × 4 bahasa → { subject, updated_at } atau null (belum diisi).
class ListEmailTemplatesJob
{
    public function run()
    {
        $saved = [];
        foreach (Database::fetchAll("SELECT template_key, language_code, subject, created_at, updated_at FROM email_templates") as $row) {
            $saved[$row['template_key']][$row['language_code']] = [
                'subject'    => $row['subject'],
                'updated_at' => IsoDate::format($row['updated_at'] ?? $row['created_at']),
            ];
        }
        $items = [];
        foreach (array_keys(EmailTemplateCatalog::KEYS) as $key) {
            $languages = [];
            foreach (Config::get('languages') as $code) {
                $languages[$code] = $saved[$key][$code] ?? null;
            }
            $items[] = ['template_key' => $key, 'languages' => $languages];
        }
        return $items;
    }
}
