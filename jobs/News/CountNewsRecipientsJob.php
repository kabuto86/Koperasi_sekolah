<?php
// Bilangan penerima e-mel berita (vendor/news/recipients): ibu bapa dengan anak aktif di koperasi ini.
class CountNewsRecipientsJob
{
    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        return ['count' => count(NewsPosts::recipients($this->vendorId, $this->cooperativeId))];
    }
}
