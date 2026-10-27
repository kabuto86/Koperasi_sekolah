<?php
// Pelayan SMTP palsu untuk ujian (tools/verify-b7.php) — CLI sahaja, 127.0.0.1 sahaja, tiada TLS.
//   php tools/fake-smtp.php 2526 /tmp/folder-mesej
// Setiap mesej (DATA) disimpan sebagai fail .eml dalam folder. AUTH LOGIN diterima (apa-apa kata laluan).
if (PHP_SAPI !== 'cli' || $argc < 3) {
    exit(1);
}
$port = (int) $argv[1];
$folder = rtrim($argv[2], '/');
$server = stream_socket_server('tcp://127.0.0.1:' . $port, $errno, $errstr);
if (!$server) {
    fwrite(STDERR, $errstr . "\n");
    exit(1);
}
$count = 0;
while (true) {
    $client = @stream_socket_accept($server, 60);
    if (!$client) {
        continue;
    }
    fwrite($client, "220 fake-smtp ESMTP\r\n");
    $data = null;
    while (($line = fgets($client, 4096)) !== false) {
        if ($data !== null) {
            if ($line === ".\r\n") {
                $count++;
                file_put_contents($folder . '/' . sprintf('%04d', $count) . '.eml', str_replace("\r\n..", "\r\n.", $data));
                $data = null;
                fwrite($client, "250 OK diterima\r\n");
            } else {
                $data .= $line;
            }
            continue;
        }
        $command = strtoupper(substr(trim($line), 0, 4));
        if ($command === 'EHLO' || $command === 'HELO') {
            fwrite($client, "250-fake-smtp\r\n250 AUTH LOGIN\r\n");
        } elseif ($command === 'AUTH') {
            fwrite($client, "334 VXNlcm5hbWU6\r\n");
            fgets($client, 4096);
            fwrite($client, "334 UGFzc3dvcmQ6\r\n");
            fgets($client, 4096);
            fwrite($client, "235 OK\r\n");
        } elseif ($command === 'DATA') {
            $data = '';
            fwrite($client, "354 Mula\r\n");
        } elseif ($command === 'QUIT') {
            fwrite($client, "221 Selamat tinggal\r\n");
            break;
        } else {
            fwrite($client, "250 OK\r\n");
        }
    }
    fclose($client);
}
