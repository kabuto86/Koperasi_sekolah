<?php
// Sesi ibu bapa di store (nama sesi KOPSTORE). customer_id SENTIASA dari sini (PELAN 8.1).
// "Ingat saya" → cookie 30 hari; tanpanya cookie tamat bila pelayar ditutup. Idle maksimum 30 hari.
// B15: apps Flutter menghantar `Authorization: Bearer {token}` (ApiTokenAuth) — sesi tidak dimulakan; token salah → 401.
class CustomerAuth
{
    const SESSION_NAME = 'KOPSTORE';
    const IDLE_SECONDS = 2592000;

    private static $customer = false;

    public static function start()
    {
        Session::start(self::SESSION_NAME, self::IDLE_SECONDS);
    }

    // [id, full_name, email, phone, relationship, language, email_verified] atau null
    public static function customer()
    {
        if (self::$customer !== false) {
            return self::$customer;
        }
        self::$customer = null;
        if (ApiTokenAuth::hasBearer()) {
            $token = ApiTokenAuth::authenticate();
            self::$customer = (new LoadCustomerSessionJob($token['customer_id']))->run();
            if (self::$customer === null) {
                ApiTokenAuth::reject();
            }
            return self::$customer;
        }
        self::start();
        $id = (int) ($_SESSION['customer_id'] ?? 0);
        if ($id > 0) {
            $job = new LoadCustomerSessionJob($id);
            self::$customer = $job->run();
            if (self::$customer === null) {
                self::logout();
            }
        }
        return self::$customer;
    }

    public static function id()
    {
        $customer = self::customer();
        return $customer ? (int) $customer['id'] : 0;
    }

    // API store: belum log masuk → auth_required (store/common.js membawa ke log masuk)
    public static function requireLogin()
    {
        if (self::customer() === null) {
            Response::fail(t('store.login_required'), [], 401, ['auth_required' => true]);
        }
    }

    public static function login($customerId, $remember)
    {
        self::start();
        Session::regenerate();
        $_SESSION['customer_id'] = (int) $customerId;
        if ($remember) {
            Session::extendCookie(self::IDLE_SECONDS);
        }
        self::$customer = false;
    }

    public static function logout()
    {
        // Apps: tiada sesi untuk dimusnahkan (token dibatal oleh job — RevokeApiTokenJob / padam akaun)
        if (ApiTokenAuth::token() !== null) {
            self::$customer = null;
            return;
        }
        self::start();
        Session::destroy();
        self::$customer = null;
    }
}
