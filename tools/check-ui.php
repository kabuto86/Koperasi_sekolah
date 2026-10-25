<?php
// Semakan UI (CLI): php tools/check-ui.php
// 1. Tiada atribut style= atau tag <style> dalam kod projek (assets/lib & fonts dikecualikan)
// 2. Keempat-empat fail bahasa mempunyai kunci yang sama
// 3. Tiada CDN (skrip/gaya dari luar) dan tiada teks paparan ditulis terus dalam HTML fail .php
if (PHP_SAPI !== 'cli') {
    exit;
}

$root = dirname(__DIR__);
$errors = [];

function projectFiles($root)
{
    $skip = ['/assets/lib/', '/assets/fonts/', '/.git/', '/tools/'];
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if (!preg_match('/\.(php|html|js)$/', $path)) {
            continue;
        }
        foreach ($skip as $part) {
            if (strpos($path, $part) !== false) {
                continue 2;
            }
        }
        $files[] = $path;
    }

    return $files;
}

$reference = array_keys(require $root . '/lang/ms.php');

foreach (projectFiles($root) as $path) {
    // Kunci literal t('x.y') / T('x.y') mesti wujud dalam lang/ms.php
    preg_match_all('/\b[tT]\(\s*[\'"]([a-z0-9_]+(?:\.[a-zA-Z0-9_]+)+)[\'"]/', file_get_contents($path), $matches);
    foreach (array_unique($matches[1]) as $key) {
        if (!in_array($key, $reference, true)) {
            $errors[] = 'Kunci tiada dalam lang/ms.php: ' . $key . ' (' . substr($path, strlen($root) + 1) . ')';
        }
    }

    $lines = file($path);
    foreach ($lines as $number => $line) {
        if (preg_match('/\sstyle\s*=|<style[\s>]|\.style\.|setAttribute\(\s*[\'"]style/i', $line)) {
            $errors[] = 'Inline CSS: ' . substr($path, strlen($root) + 1) . ':' . ($number + 1);
        }
    }
}

// Teks yang dibenarkan ditulis terus (unit, singkatan teknikal, nama kekunci)
$allowedText = ['RM', 'RM 0.00', 'RM 1.80', '58 mm', '80 mm', 'TLS', 'SSL', 'Enter', 'Esc', 'F2'];

foreach (projectFiles($root) as $path) {
    $relative = substr($path, strlen($root) + 1);
    $source = file_get_contents($path);

    if (preg_match('/<(script|link)[^>]+(src|href)\s*=\s*["\'](https?:)?\/\//i', $source)) {
        $errors[] = 'CDN / sumber luar: ' . $relative;
    }
    if (substr($path, -4) !== '.php' || strpos($relative, 'lang/') === 0) {
        continue;
    }

    // Buang kod PHP & skrip, kemudian cari teks atau atribut paparan yang bukan dari t()
    $html = preg_replace('/<\?(php|=).*?(\?>|$)/s', ' ', $source);
    if (preg_match_all('/\s(placeholder|title|aria-label|alt)="([^"]*[A-Za-z]{2,}[^"]*)"/', $html, $matches)) {
        foreach ($matches[2] as $value) {
            $errors[] = 'Atribut paparan ditulis terus: ' . $relative . ' → ' . $value;
        }
    }
    foreach (explode("\n", preg_replace('/<[^>]*>/', "\n", $html)) as $text) {
        $text = trim($text);
        if (preg_match('/[A-Za-z]{2,}/', $text) && !in_array($text, $allowedText, true)) {
            $errors[] = 'Teks ditulis terus: ' . $relative . ' → ' . $text;
        }
    }
}

foreach (['en', 'zh', 'ta'] as $code) {
    $keys = array_keys(require $root . '/lang/' . $code . '.php');
    foreach (array_diff($reference, $keys) as $missing) {
        $errors[] = "Kunci hilang dalam lang/$code.php: $missing";
    }
    foreach (array_diff($keys, $reference) as $extra) {
        $errors[] = "Kunci tiada dalam lang/ms.php tetapi ada dalam $code.php: $extra";
    }
}

if ($errors) {
    echo implode(PHP_EOL, $errors) . PHP_EOL;
    exit(1);
}

echo 'OK — tiada inline CSS, tiada CDN, tiada teks ditulis terus, ' . count($reference) . ' kunci sama dalam 4 bahasa.' . PHP_EOL;
