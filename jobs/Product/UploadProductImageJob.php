<?php
// Simpan gambar produk dari data URL (PELAN 8.8): uploads/{vendor_id}/{cooperative_id}/products/{hash}.{jpg|png|webp}.
// Jenis disahkan dengan finfo + getimagesize (core/Uploader), ≤ 2MB. Pulangkan laluan relatif untuk products.image_path.
class UploadProductImageJob
{
    private $vendorId;
    private $cooperativeId;
    private $dataUrl;

    public function __construct($vendorId, $cooperativeId, $dataUrl)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->dataUrl = $dataUrl;
    }

    public function run()
    {
        return Uploader::saveImage($this->dataUrl, $this->vendorId . '/' . $this->cooperativeId . '/products', 'image_url');
    }
}
