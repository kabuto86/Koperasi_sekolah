<?php
// Masukkan e-mel ke email_queue (dihantar oleh cron/send-emails — B7). Tidak pernah hantar terus.
// Kandungan: email_templates (owner boleh ubah) ikut bahasa penerima → jatuh balik kunci bahasa
// email.{kunci}.subject / email.{kunci}.body. Pemboleh ubah {name}, {link} dll. di-escape.
// $htmlVars (B7) = serpihan HTML yang DIBINA PELAYAN (jadual barang, QR) — dimasukkan tanpa escape, badan sahaja.
class QueueEmailJob
{
    private $vendorId;
    private $toEmail;
    private $templateKey;
    private $language;
    private $vars;
    private $htmlVars;

    public function __construct($vendorId, $toEmail, $templateKey, $language, array $vars, array $htmlVars = [])
    {
        $this->vendorId = $vendorId;
        $this->toEmail = $toEmail;
        $this->templateKey = $templateKey;
        $this->language = Lang::isSupported($language) ? $language : Config::get('default_language');
        $this->vars = $vars;
        $this->htmlVars = $htmlVars;
    }

    public function run()
    {
        list($subject, $bodyHtml) = $this->content();
        return Database::insert(
            "INSERT INTO email_queue (vendor_id, to_email, subject, body_html, template_key) VALUES (?, ?, ?, ?, ?)",
            [$this->vendorId, $this->toEmail, $subject, $bodyHtml, $this->templateKey]
        );
    }

    private function content()
    {
        $row = Database::fetchOne(
            "SELECT subject, body_html FROM email_templates WHERE template_key = ? AND language_code IN (?, ?)
              ORDER BY language_code = ? DESC LIMIT 1",
            [$this->templateKey, $this->language, Config::get('default_language'), $this->language]
        );
        $escaped = [];
        foreach ($this->vars as $name => $value) {
            $escaped[$name] = e($value);
        }
        $escaped['app_name'] = e(Config::get('name'));
        $subjectVars = $this->vars;
        foreach ($this->htmlVars as $name => $html) {
            $subjectVars[$name] = '';
        }

        if ($row !== null) {
            return [$this->replace($row['subject'], $subjectVars), $this->replace($row['body_html'], $this->htmlVars + $escaped)];
        }

        $subject = Lang::getFor($this->language, 'email.' . $this->templateKey . '.subject', $this->vars + ['app_name' => Config::get('name')]);
        $body = Lang::getFor($this->language, 'email.' . $this->templateKey . '.body', $escaped);
        $html = '<p>' . nl2br($body) . '</p>';
        foreach ($this->htmlVars as $name => $fragment) {
            $html = str_replace('{' . $name . '}', $fragment, $html);
        }
        if (isset($this->vars['link'])) {
            $html .= '<p><a href="' . e($this->vars['link']) . '">' . e($this->vars['link']) . '</a></p>';
        }
        return [$subject, $html];
    }

    private function replace($text, $vars)
    {
        foreach ($vars as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }
        return $text;
    }
}
