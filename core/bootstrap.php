<?php
// Dimuatkan oleh setiap halaman dan endpoint sebelum apa-apa output.

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/core/Config.php';
require APP_ROOT . '/core/SecurityHeaders.php';
require APP_ROOT . '/core/View.php';
require APP_ROOT . '/core/Lang.php';
require APP_ROOT . '/core/PanelRole.php';

date_default_timezone_set(Config::get('timezone'));

SecurityHeaders::send();
Lang::init();
