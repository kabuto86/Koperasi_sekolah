<?php
// Hash kata laluan & PIN: Argon2id 19456/2/1 (PELAN 11, config password_options).
class Password
{
    // Hash palsu untuk akaun yang tiada — masa semakan sama, tidak dedah sama ada e-mel wujud
    const DUMMY_HASH = '$argon2id$v=19$m=19456,t=2,p=1$c0l2bWhmUW91d1F3aG9Gdw$WKp1vojpA56zCffcfHNfLD6xpLDX/MxZl1F1b0sgFRw';

    public static function hash($plain)
    {
        return password_hash((string) $plain, PASSWORD_ARGON2ID, Config::get('password_options'));
    }

    public static function verify($plain, $hash)
    {
        return password_verify((string) $plain, $hash === null || $hash === '' ? self::DUMMY_HASH : $hash);
    }

    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, Config::get('password_options'));
    }
}
