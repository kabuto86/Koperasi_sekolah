<?php
// Dimuatkan oleh setiap halaman dan endpoint sebelum apa-apa output.

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/core/Config.php';
require APP_ROOT . '/core/SecurityHeaders.php';
require APP_ROOT . '/core/View.php';
require APP_ROOT . '/core/Lang.php';
require APP_ROOT . '/core/PanelRole.php';

// Kelas lain dimuat bila diperlukan:
//   core/{Kelas}.php           cth. Database, Request, Response, Validator, Csrf, Crypto
//   jobs/{Folder}/{Kelas}.php  cth. jobs/Cooperative/ValidateCooperativeJob.php
//   payments/{Kelas}.php, payments/drivers/{Kelas}.php  cth. GatewayFactory, ToyyibPayGateway (B8)
spl_autoload_register('autoloadAppClass');

function autoloadAppClass($class)
{
    if (!preg_match('/^[A-Za-z0-9]+$/', $class)) {
        return;
    }

    $coreFile = APP_ROOT . '/core/' . $class . '.php';
    if (is_file($coreFile)) {
        require $coreFile;
        return;
    }

    $jobFiles = glob(APP_ROOT . '/jobs/*/' . $class . '.php');
    if (!empty($jobFiles)) {
        require $jobFiles[0];
        return;
    }

    foreach (['/payments/', '/payments/drivers/', '/messaging/'] as $folder) {
        if (is_file(APP_ROOT . $folder . $class . '.php')) {
            require APP_ROOT . $folder . $class . '.php';
            return;
        }
    }
}

// Endpoint api/ memulangkan JSON walaupun berlaku ralat yang tidak dijangka
define('IS_API_REQUEST', strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/api/') !== false);
set_exception_handler('handleUncaughtException');

function handleUncaughtException($exception)
{
    // Job yang gagal di tengah transaksi tidak meninggalkan separuh perubahan
    Database::rollBack();

    // Ralat dijangka dari job (input salah dsb.) → { ok:false, error, errors }
    if ($exception instanceof ValidationException && IS_API_REQUEST) {
        Response::fail($exception->getMessage(), $exception->errors(), $exception->status(), $exception->extra());
    }

    error_log('[KoperasiKu] ' . $exception->getMessage() . ' @ ' . $exception->getFile() . ':' . $exception->getLine());

    if (IS_API_REQUEST) {
        $extra = Config::get('debug') ? ['debug' => $exception->getMessage()] : [];
        Response::fail(t('error.server'), [], 500, $extra);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }
    echo e(t('error.server'));
    if (Config::get('debug') && PHP_SAPI === 'cli') {
        echo "\n" . $exception->getMessage() . "\n";
    }
}

date_default_timezone_set(Config::get('timezone'));

SecurityHeaders::send();
Lang::init();
