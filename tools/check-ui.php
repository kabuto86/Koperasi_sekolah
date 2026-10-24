<?php
// Semakan UI (CLI): php tools/check-ui.php
// 1. Tiada atribut style= atau tag <style> dalam kod projek (assets/lib & fonts dikecualikan)
// 2. Keempat-empat fail bahasa mempunyai kunci yang sama
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

foreach (projectFiles($root) as $path) {
    $lines = file($path);
    foreach ($lines as $number => $line) {
        if (preg_match('/\sstyle\s*=|<style[\s>]|\.style\.|setAttribute\(\s*[\'"]style/i', $line)) {
            $errors[] = 'Inline CSS: ' . substr($path, strlen($root) + 1) . ':' . ($number + 1);
        }
    }
}

$reference = array_keys(require $root . '/lang/ms.php');
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

echo 'OK — tiada inline CSS, ' . count($reference) . ' kunci sama dalam 4 bahasa.' . PHP_EOL;
