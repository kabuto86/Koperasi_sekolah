<?php
// Simpan templat e-mel satu bahasa. Pemegang tempat yang tiada dalam senarai kunci itu ditolak
// (elak e-mel dengan {salah_eja}).
class SaveEmailTemplateJob
{
    private $actorId;
    private $input;

    public function __construct($actorId, array $input)
    {
        $this->actorId = (int) $actorId;
        $this->input = $input;
    }

    public function run()
    {
        $key = (string) ($this->input['template_key'] ?? '');
        $language = (string) ($this->input['language_code'] ?? '');
        $placeholders = EmailTemplateCatalog::placeholders($key);
        if ($placeholders === null || !Lang::isSupported($language)) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $v = new Validator($this->input);
        $v->required('subject')->maxLength('subject', 255)->required('body_html')->maxLength('body_html', 100000);
        ValidationException::throwIfFails($v);

        $subject = trim($this->input['subject']);
        $body = trim($this->input['body_html']);
        preg_match_all('/\{[a-z_]+\}/', $subject . ' ' . $body, $matches);
        $unknown = array_values(array_unique(array_diff($matches[0], $placeholders)));
        if (count($unknown)) {
            $message = t('email_template.unknown_placeholder', ['list' => implode(', ', $unknown)]);
            throw new ValidationException($message, ['body_html' => $message]);
        }

        Database::execute(
            "INSERT INTO email_templates (template_key, language_code, subject, body_html) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE subject = VALUES(subject), body_html = VALUES(body_html), updated_at = NOW()",
            [$key, $language, $subject, $body]
        );
        (new WriteAuditLogJob('owner', $this->actorId, null, 'email_template.update', 'email_template', null, $key . '/' . $language))->run();
        return (new GetEmailTemplateJob($key, $language))->run();
    }
}
