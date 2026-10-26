<?php
// Sesi panel owner (nama sesi KOPOWNER). 2FA TOTP wajib (PELAN 11):
//   langkah 1 e-mel + kata laluan → beginPending()  ·  langkah 2 kod TOTP → completeLogin()
// Pengguna disemak semula dari DB setiap permintaan (akaun dinyahaktifkan terus terkeluar).
class OwnerAuth
{
    const SESSION_NAME = 'KOPOWNER';
    const IDLE_SECONDS = 7200;
    const PENDING_SECONDS = 600;

    private static $user = false;

    public static function start()
    {
        Session::start(self::SESSION_NAME, self::IDLE_SECONDS);
    }

    // [id, email, full_name, role, language, totp_enabled] atau null
    public static function user()
    {
        if (self::$user !== false) {
            return self::$user;
        }
        self::start();
        self::$user = null;
        $id = (int) ($_SESSION['owner_user_id'] ?? 0);
        if ($id > 0) {
            $job = new LoadOwnerSessionJob($id);
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

    public static function role()
    {
        $user = self::user();
        return $user ? $user['role'] : '';
    }

    // API: belum log masuk → 401
    public static function requireLogin()
    {
        if (self::user() === null) {
            Response::fail(t('error.unauthenticated'), [], 401, ['auth_required' => true]);
        }
    }

    // API: peranan tidak dibenarkan → 403 (owner_staff tidak boleh harga/pelan/tetapan — PELAN 4)
    public static function requireRole(array $roles)
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            Response::forbidden();
        }
    }

    // Halaman panel: belum log masuk → log masuk; menu tidak dibenarkan → halaman pertama yang dibenarkan
    public static function requirePage($activeMenu)
    {
        if (self::user() === null) {
            Session::redirect('owner/login.php');
        }
        if (!PanelRole::canOpen('owner', self::role(), $activeMenu)) {
            Session::redirect(PanelRole::firstPage('owner', self::role()));
        }
    }

    // Langkah 1 berjaya. $setupSecret = rahsia TOTP baharu jika 2FA belum disediakan (log masuk pertama)
    public static function beginPending($userId, $setupSecret = null)
    {
        self::start();
        Session::regenerate();
        unset($_SESSION['owner_user_id']);
        $_SESSION['owner_pending'] = [
            'id'           => (int) $userId,
            'at'           => time(),
            'setup_secret' => $setupSecret === null ? null : Crypto::encrypt($setupSecret),
        ];
        self::$user = false;
    }

    // [id, setup_secret (teks base32 atau null)] atau null jika tiada / luput
    public static function pending()
    {
        self::start();
        $pending = $_SESSION['owner_pending'] ?? null;
        if (!is_array($pending) || time() - $pending['at'] > self::PENDING_SECONDS) {
            return null;
        }
        return [
            'id'           => $pending['id'],
            'setup_secret' => $pending['setup_secret'] === null ? null : Crypto::decrypt($pending['setup_secret']),
        ];
    }

    public static function completeLogin($userId)
    {
        self::start();
        Session::regenerate();
        unset($_SESSION['owner_pending']);
        $_SESSION['owner_user_id'] = (int) $userId;
        self::$user = false;
    }

    // Rahsia TOTP sementara semasa "pindah ke peranti baharu" (two-factor/setup → enable)
    public static function setSetupSecret($secret)
    {
        $_SESSION['owner_setup_secret'] = Crypto::encrypt($secret);
    }

    public static function setupSecret()
    {
        $value = $_SESSION['owner_setup_secret'] ?? null;
        return $value === null ? null : Crypto::decrypt($value);
    }

    public static function clearSetupSecret()
    {
        unset($_SESSION['owner_setup_secret']);
    }

    public static function logout()
    {
        self::start();
        Session::destroy();
        self::$user = null;
    }
}
