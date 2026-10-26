<?php
// No. telefon bimbit Malaysia → bentuk tetap '012-3456789' (sama seperti mock U5 storeNormalizePhone).
class PhoneNumber
{
    // null jika bukan no. telefon bimbit Malaysia yang sah
    public static function normalize($phone)
    {
        $digits = preg_replace('/[\s-]/', '', (string) $phone);
        $digits = preg_replace('/^\+?60/', '0', $digits);
        if (!preg_match('/^01[0-9]{8,9}$/', $digits)) {
            return null;
        }
        return substr($digits, 0, 3) . '-' . substr($digits, 3);
    }
}
