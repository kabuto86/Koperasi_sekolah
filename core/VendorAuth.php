<?php
// Sesi panel vendor (nama sesi KOPVENDOR, idle 2 jam). 2FA pilihan (jika totp_secret ada).
// vendor_id SENTIASA dari sini — tidak pernah dari $_POST / $_GET (PELAN 8.1).
// Pengguna & status vendor disemak semula dari DB setiap permintaan.
class VendorAuth
{
    const SESSION_NAME = 'KOPVENDOR';
    const IDLE_SECONDS = 7200;
    const PENDING_SECONDS = 600;
    const ADMIN_ROLES = ['vendor_admin'];
    const PANEL_ROLES = ['vendor_admin', 'coop_manager'];
    const ALL_ROLES = ['vendor_admin', 'coop_manager', 'cashier'];

    private static $user = false;

    public static function start()
    {
        Session::start(self::SESSION_NAME, self::IDLE_SECONDS);
    }

    // [id, vendor_id, email, full_name, role, language, vendor_status, cooperative_ids, ...] atau null
    public static function user()
    {
        if (self::$user !== false) {
            return self::$user;
        }
        self::start();
        self::$user = null;
        $id = (int) ($_SESSION['vendor_user_id'] ?? 0);
        if ($id > 0) {
            $job = new LoadVendorSessionJob($id, (int) ($_SESSION['vendor_id'] ?? 0));
            self::$user = $job->run();
            if (self::$user === null) {
                self::logout();
            }
        }
        return self::$user;
    }

    public static function id()
    {
        $user = self::user();
        return $user ? (int) $user['id'] : 0;
    }

    public static function vendorId()
    {
        $user = self::user();
        return $user ? (int) $user['vendor_id'] : 0;
    }

    public static function role()
    {
        $user = self::user();
        return $user ? $user['role'] : '';
    }

    // Vendor digantung / dibatalkan → hanya halaman bil (CheckSubscriptionJob)
    public static function isBillingOnly()
    {
        $job = new CheckSubscriptionJob(self::vendorId());
        $status = $job->run();
        return $status['billing_only'];
    }

    // Koperasi yang boleh dibuka: pentadbir = semua milik vendor; lain-lain = senarai yang diberi
    public static function canAccessCooperative($cooperativeId)
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }
        if ($user['role'] === 'vendor_admin') {
            return true;
        }
        return in_array((int) $cooperativeId, $user['cooperative_ids'], true);
    }

    public static function requireLogin()
    {
        if (self::user() === null) {
            Response::fail(t('error.unauthenticated'), [], 401, ['auth_required' => true]);
        }
    }

    // Kebenaran sebenar (lalai peranan ± pengecualian — PELAN 4.1)
    public static function permissions()
    {
        $user = self::user();
        return $user ? $user['permissions'] : [];
    }

    // $codes = satu kod atau senarai (cukup salah satu)
    public static function can($codes)
    {
        return count(array_intersect((array) $codes, self::permissions())) > 0;
    }

    // API: tiada kebenaran → 403. $allowBillingOnly seperti requireRole
    public static function requirePermission($codes, $allowBillingOnly = false)
    {
        self::requireLogin();
        if (!self::can($codes)) {
            Response::forbidden();
        }
        if (!$allowBillingOnly && self::isBillingOnly()) {
            Response::fail(t('auth.vendor_suspended'), [], 403, ['billing_only' => true]);
        }
    }

    // API. $allowBillingOnly = true hanya untuk laluan bil/langganan/log keluar
    public static function requireRole(array $roles, $allowBillingOnly = false)
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            Response::forbidden();
        }
        if (!$allowBillingOnly && self::isBillingOnly()) {
            Response::fail(t('auth.vendor_suspended'), [], 403, ['billing_only' => true]);
        }
    }

    // Halaman panel / POS
    public static function requirePage($activeMenu)
    {
        if (self::user() === null) {
            Session::redirect('vendor/login.php');
        }
        if (self::isBillingOnly() && !PanelRole::isBillingMenu($activeMenu)) {
            if (self::role() !== 'vendor_admin') {
                self::logout();
                Session::redirect('vendor/login.php?reason=suspended');
            }
            Session::redirect('vendor/subscription.php');
        }
        if (!PanelRole::canOpen('vendor', self::permissions(), $activeMenu)) {
            self::redirectFirstPage();
        }
    }

    // Halaman pertama yang dibenarkan; kakitangan tanpa sebarang kebenaran menu → log keluar dengan notis
    public static function redirectFirstPage()
    {
        $url = PanelRole::firstPage('vendor', self::permissions());
        if ($url === null) {
            self::logout();
            Session::redirect('vendor/login.php?reason=no_access');
        }
        Session::redirect($url);
    }

    // Langkah 1 berjaya tetapi pengguna ada 2FA
    public static function beginPending($userId)
    {
        self::start();
        Session::regenerate();
        unset($_SESSION['vendor_user_id'], $_SESSION['vendor_id'], $_SESSION['cooperative_id']);
        $_SESSION['vendor_pending'] = ['id' => (int) $userId, 'at' => time()];
        self::$user = false;
    }

    public static function pendingId()
    {
        self::start();
        $pending = $_SESSION['vendor_pending'] ?? null;
        if (!is_array($pending) || time() - $pending['at'] > self::PENDING_SECONDS) {
            return 0;
        }
        return $pending['id'];
    }

    public static function completeLogin($userId, $vendorId)
    {
        self::start();
        Session::regenerate();
        unset($_SESSION['vendor_pending'], $_SESSION['cooperative_id']);
        $_SESSION['vendor_user_id'] = (int) $userId;
        $_SESSION['vendor_id'] = (int) $vendorId;
        self::$user = false;
    }

    // Koperasi aktif — hanya selepas ValidateCooperativeJob + canAccessCooperative
    public static function setCooperative($cooperativeId)
    {
        $_SESSION['cooperative_id'] = (int) $cooperativeId;
    }

    public static function cooperativeId()
    {
        return (int) ($_SESSION['cooperative_id'] ?? 0);
    }

    public static function logout()
    {
        self::start();
        Session::destroy();
        self::$user = null;
    }
}
