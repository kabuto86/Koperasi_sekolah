<?php
// Troli ibu bapa di koperasi slug — CartQuote dikira semula di pelayan (PELAN 18.12 store/cart/get).
class GetCartJob
{
    private $customerId;
    private $cooperative;

    public function __construct($customerId, array $cooperative)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
    }

    public function run()
    {
        return Carts::quote($this->customerId, $this->cooperative);
    }
}
