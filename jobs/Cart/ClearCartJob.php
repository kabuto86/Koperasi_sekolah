<?php
// Kosongkan troli ibu bapa sesi di koperasi ini.
class ClearCartJob
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
        $cart = Carts::find($this->customerId, $this->cooperative);
        if ($cart !== null) {
            Database::execute("DELETE FROM cart_items WHERE cart_id = ?", [$cart['id']]);
            Carts::touch($cart['id']);
        }
        return Carts::quote($this->customerId, $this->cooperative);
    }
}
