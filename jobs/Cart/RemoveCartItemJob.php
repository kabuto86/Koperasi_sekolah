<?php
// Buang satu baris troli { id } (troli ibu bapa sesi di koperasi ini sahaja).
class RemoveCartItemJob
{
    private $customerId;
    private $cooperative;
    private $itemId;

    public function __construct($customerId, array $cooperative, $itemId)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->itemId = (int) $itemId;
    }

    public function run()
    {
        $cart = Carts::find($this->customerId, $this->cooperative);
        $deleted = $cart ? Database::execute("DELETE FROM cart_items WHERE cart_id = ? AND id = ?", [$cart['id'], $this->itemId]) : 0;
        if (!$deleted) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Carts::touch($cart['id']);
        return Carts::quote($this->customerId, $this->cooperative);
    }
}
