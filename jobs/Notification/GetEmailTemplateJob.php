<?php
// Satu templat e-mel (kunci + bahasa). Belum diisi → subject/body kosong.
class GetEmailTemplateJob
{
    private $templateKey;
    private $languageCode;

    public function __construct($templateKey, $languageCode)
    {
        $this->templateKey = (string) $templateKey;
        $this->languageCode = (string) $languageCode;
    }

    public function run()
    {
        $placeholders = EmailTemplateCatalog::placeholders($this->templateKey);
        if ($placeholders === null || !Lang::isSupported($this->languageCode)) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $row = Database::fetchOne(
            "SELECT subject, body_html, created_at, updated_at FROM email_templates WHERE template_key = ? AND language_code = ?",
            [$this->templateKey, $this->languageCode]
        );
        return [
            'template_key'  => $this->templateKey,
            'language_code' => $this->languageCode,
            'subject'       => $row ? $row['subject'] : '',
            'body_html'     => $row ? $row['body_html'] : '',
            'updated_at'    => $row ? IsoDate::format($row['updated_at'] ?? $row['created_at']) : null,
            'placeholders'  => $placeholders,
        ];
    }
}
