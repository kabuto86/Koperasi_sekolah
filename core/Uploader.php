<?php
// Simpan gambar yang dimuat naik (logo koperasi, gambar produk) — PELAN 8.8.
// Pelayar menghantar gambar sebagai data URL (data:image/png;base64,...) dalam badan JSON.
// Jenis fail disahkan dari KANDUNGAN (finfo), bukan dari nama / data URL. Nama fail = hash, bukan input pengguna.
// Sejak B14 gambar dikod semula dengan GD: metadata (EXIF — lokasi GPS foto telefon, PDPA) & bait tersembunyi dibuang,
// dimensi dihadkan (elak "bom piksel" menghabiskan memori).
//
//   $path = Uploader::saveImage($dataUrl, '1/3/products');   // → '1/3/products/9f2c….jpg' (relatif kepada uploads/)
//   Uploader::delete($path);
class Uploader
{
    const MAX_BYTES = 2097152; // 2MB
    const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    const MAX_SIDE = 4000; // piksel

    // Data URL gambar? (bukan URL gambar sedia ada)
    public static function isDataUrl($value)
    {
        return is_string($value) && strpos($value, 'data:') === 0;
    }

    // Pulangkan laluan relatif kepada uploads/. Gagal → ValidationException pada $field
    public static function saveImage($dataUrl, $relativeDir, $field = 'image')
    {
        // Had kasar sebelum nyahkod (base64 ≈ 4/3 saiz asal)
        $tooLarge = is_string($dataUrl) && strlen($dataUrl) > self::MAX_BYTES * 1.4 + 100;
        $bytes = $tooLarge ? null : self::decode($dataUrl);
        if ($bytes === null && !$tooLarge) {
            throw new ValidationException(t('upload.invalid_type'), [$field => t('upload.invalid_type')]);
        }
        if ($tooLarge || strlen($bytes) > self::MAX_BYTES) {
            throw new ValidationException(t('upload.too_large', ['max' => '2MB']), [$field => t('upload.too_large', ['max' => '2MB'])]);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $size = @getimagesizefromstring($bytes);
        if (!isset(self::IMAGE_TYPES[$mime]) || $size === false) {
            throw new ValidationException(t('upload.invalid_type'), [$field => t('upload.invalid_type')]);
        }
        if ($size[0] > self::MAX_SIDE || $size[1] > self::MAX_SIDE) {
            $message = t('upload.too_many_pixels', ['max' => self::MAX_SIDE]);
            throw new ValidationException($message, [$field => $message]);
        }
        $bytes = self::reencode($bytes, $mime);
        if ($bytes === null) {
            throw new ValidationException(t('upload.invalid_type'), [$field => t('upload.invalid_type')]);
        }

        $relativeDir = trim($relativeDir, '/');
        $dir = self::root() . '/' . $relativeDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder muat naik tidak dapat dicipta: ' . $relativeDir);
        }
        $name = hash('sha256', $bytes) . '.' . self::IMAGE_TYPES[$mime];
        if (file_put_contents($dir . '/' . $name, $bytes) === false) {
            throw new RuntimeException('Fail muat naik tidak dapat ditulis.');
        }
        return $relativeDir . '/' . $name;
    }

    // Padam fail lama (laluan dari DB sahaja). Tidak keluar dari folder uploads/
    public static function delete($relativePath)
    {
        if (!$relativePath || strpos($relativePath, '..') !== false) {
            return;
        }
        $file = self::root() . '/' . ltrim($relativePath, '/');
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function url($relativePath)
    {
        return $relativePath ? View::url('uploads/' . $relativePath) : null;
    }

    private static function root()
    {
        return APP_ROOT . '/uploads';
    }

    // Kod semula piksel sahaja (tiada EXIF / komen / data selepas penamat fail). PNG & WebP kekal lutsinar
    private static function reencode($bytes, $mime)
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }
        if ($mime !== 'image/jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
        ob_start();
        if ($mime === 'image/jpeg') {
            $written = imagejpeg($image, null, 85);
        } elseif ($mime === 'image/png') {
            $written = imagepng($image, null, 6);
        } else {
            $written = imagewebp($image, null, 85);
        }
        $output = ob_get_clean();
        imagedestroy($image);
        return $written && $output !== '' ? $output : null;
    }

    // data:image/xxx;base64,.... → bait mentah, atau null jika bukan base64 sah
    private static function decode($dataUrl)
    {
        if (!is_string($dataUrl) || !preg_match('/^data:[a-z0-9.+\/-]*;base64,/i', $dataUrl, $match)) {
            return null;
        }
        $bytes = base64_decode(substr($dataUrl, strlen($match[0])), true);
        return $bytes === false || $bytes === '' ? null : $bytes;
    }
}
