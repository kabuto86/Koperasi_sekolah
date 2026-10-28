<?php
// Juruwang POS di kaunter (PELAN 11, 18.11). Peranti kaunter log masuk ke panel vendor seperti biasa (VendorAuth);
// juruwang kemudian dikenal pasti dengan PIN → $_SESSION['pos_user_id'][cooperative_id]. Disemak semula dari DB
// setiap permintaan (PosCashiers::find): kakitangan vendor sesi, aktif, PIN ditetapkan, ada akses koperasi.
class PosAuth
{
    public static function cashierId($cooperativeId)
    {
        VendorAuth::start();
        return (int) ($_SESSION['pos_user_id'][(int) $cooperativeId] ?? 0);
    }

    // Selepas PinLogin berjaya — id sesi baharu (elak session fixation)
    public static function setCashier($cooperativeId, $userId)
    {
        VendorAuth::start();
        Session::regenerate();
        $_SESSION['pos_user_id'][(int) $cooperativeId] = (int) $userId;
    }

    // Kunci kaunter / tukar juruwang / tutup syif — syif kekal dibuka
    public static function clear($cooperativeId)
    {
        VendorAuth::start();
        unset($_SESSION['pos_user_id'][(int) $cooperativeId]);
    }

    // [id, full_name, role] atau null (dibuang dari sesi jika tidak lagi sah)
    public static function cashier(array $cooperative)
    {
        $userId = self::cashierId($cooperative['id']);
        if ($userId === 0) {
            return null;
        }
        $cashier = PosCashiers::find(VendorAuth::vendorId(), $cooperative['id'], $userId);
        if ($cashier === null) {
            self::clear($cooperative['id']);
        }
        return $cashier;
    }

    // Laluan POS selain session / pin-login / lock / catalog
    public static function requireCashier(array $cooperative)
    {
        $cashier = self::cashier($cooperative);
        if ($cashier === null) {
            Response::fail(t('pos.error.login_required'), [], 403, ['pos_login_required' => true]);
        }
        return $cashier;
    }

    // Kakitangan yang menerima wang / menyerah barang di kaunter: juruwang PIN jika ada, jika tidak pengguna panel
    public static function counterUserId(array $cooperative)
    {
        $cashier = self::cashier($cooperative);
        return $cashier !== null ? $cashier['id'] : VendorAuth::id();
    }
}
