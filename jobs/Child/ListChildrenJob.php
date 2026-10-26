<?php
// Anak saya — semua anak aktif ibu bapa sesi di semua sekolah (PELAN 18.12 store/children/list).
class ListChildrenJob
{
    private $customerId;

    public function __construct($customerId)
    {
        $this->customerId = (int) $customerId;
    }

    public function run()
    {
        return array_map(['Children', 'publicShape'], Children::all($this->customerId));
    }
}
