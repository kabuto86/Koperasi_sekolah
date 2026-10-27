<?php
// Penghantar e-mel SMTP vanila (tanpa Composer / PHPMailer) — dipanggil HANYA oleh cron/send-emails.php melalui
// SendQueuedEmailsJob, tidak pernah semasa checkout (PELAN 9 I, 16 perkara 12).
// Sokong: tls (STARTTLS, port 587), ssl (port 465), none (pelayan dalaman / ujian), AUTH LOGIN.
// config app.php mail_driver = 'log' → tulis ke fail (pembangunan tanpa SMTP), tiada sambungan rangkaian.
//
//   $mailer = new Mailer(['host' => 'smtp.contoh.my', 'port' => 587, 'encryption' => 'tls', 'username' => '...',
//                         'password' => '...', 'from_address' => 'noreply@koperasiku.my', 'from_name' => 'KoperasiKu']);
//   $mailer->send('ibubapa@contoh.my', 'Resit pesanan', '<p>...</p>');   // gagal → RuntimeException
class Mailer
{
    private $options;
    private $socket = null;

    public function __construct(array $options)
    {
        $this->options = $options + [
            'driver' => 'smtp', 'host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '', 'password' => '',
            'from_address' => '', 'from_name' => '', 'timeout' => 15, 'log_file' => '',
        ];
    }

    public function isConfigured()
    {
        return $this->options['driver'] === 'log' || trim((string) $this->options['host']) !== '';
    }

    public function send($to, $subject, $html)
    {
        $to = self::cleanHeader($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Alamat penerima tidak sah.');
        }
        $message = $this->buildMessage($to, $subject, $html);
        if ($this->options['driver'] === 'log') {
            $this->writeLog($message);
            return;
        }
        try {
            $this->deliver($to, $message);
        } finally {
            $this->close();
        }
    }

    // Header + badan base64 (UTF-8 selamat untuk Cina/Tamil)
    public function buildMessage($to, $subject, $html)
    {
        $from = self::cleanHeader($this->options['from_address']);
        $name = self::cleanHeader($this->options['from_name']);
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $headers = [
            'Date: ' . date('r'),
            'From: ' . ($name !== '' ? self::encodeHeader($name) . ' ' : '') . '<' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . self::encodeHeader(self::cleanHeader($subject)),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $body = '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
        return implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n";
    }

    private function deliver($to, $message)
    {
        $encryption = $this->options['encryption'];
        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $address = $scheme . '://' . $this->options['host'] . ':' . (int) $this->options['port'];
        $this->socket = @stream_socket_client($address, $errno, $errstr, (int) $this->options['timeout']);
        if (!$this->socket) {
            throw new RuntimeException('Sambungan SMTP gagal: ' . $errstr);
        }
        stream_set_timeout($this->socket, (int) $this->options['timeout']);
        $this->expect([220]);
        $helo = $this->heloName();
        $this->command('EHLO ' . $helo, [250]);
        if ($encryption === 'tls') {
            $this->command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS gagal.');
            }
            $this->command('EHLO ' . $helo, [250]);
        }
        if ((string) $this->options['username'] !== '') {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($this->options['username']), [334]);
            $this->command(base64_encode($this->options['password']), [235]);
        }
        $this->command('MAIL FROM:<' . self::cleanHeader($this->options['from_address']) . '>', [250]);
        $this->command('RCPT TO:<' . $to . '>', [250, 251]);
        $this->command('DATA', [354]);
        // Titik di awal baris digandakan (RFC 5321 4.5.2)
        $data = preg_replace('/^\./m', '..', $message);
        $this->command($data . '.', [250]);
        $this->command('QUIT', [221]);
    }

    private function heloName()
    {
        $from = (string) $this->options['from_address'];
        $domain = strpos($from, '@') !== false ? substr($from, strpos($from, '@') + 1) : '';
        return preg_match('/^[A-Za-z0-9.-]+$/', $domain) ? $domain : 'localhost';
    }

    private function command($line, array $codes)
    {
        fwrite($this->socket, $line . "\r\n");
        return $this->expect($codes);
    }

    // Baca jawapan (boleh berbilang baris "250-...") dan semak kod
    private function expect(array $codes)
    {
        $reply = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP ' . ($reply === '' ? 'tiada jawapan' : trim($reply)));
        }
        return $reply;
    }

    private function close()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function writeLog($message)
    {
        $file = (string) $this->options['log_file'];
        if ($file === '' || file_put_contents($file, $message . "\r\n----\r\n", FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Fail log e-mel tidak boleh ditulis.');
        }
    }

    // Buang CR/LF — elak suntikan header
    private static function cleanHeader($value)
    {
        return trim(str_replace(["\r", "\n"], '', (string) $value));
    }

    private static function encodeHeader($text)
    {
        return preg_match('/^[\x20-\x7E]*$/', $text) ? $text : '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
}
