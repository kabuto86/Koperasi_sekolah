// Data mock panel owner (Peringkat 1). Ditambah dalam U2.

// Log masuk langkah 1: e-mel + kata laluan → perlu 2FA (wajib untuk owner)
// Demo: kata laluan "salah" memaparkan ralat
Mock.register('owner/auth/login', function (body) {
    if (body.password === 'salah') {
        return Mock.fail(T('auth.invalid_credentials'));
    }
    return Mock.ok({ requires_2fa: true });
});

// Log masuk langkah 2: kod TOTP 6 digit. Demo: "000000" ditolak
Mock.register('owner/auth/verify-2fa', function (body) {
    if (body.code === '000000') {
        return Mock.fail(T('auth.otp_invalid'), { code: T('auth.otp_invalid') });
    }
    return Mock.ok({ user: { id: 1, name: 'Pemilik Platform', role: 'owner' } });
});
