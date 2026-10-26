<?php
// Tukar kuantiti baris troli { id, qty } — baris dicari dalam troli ibu bapa sesi di koperasi ini sahaja.
// Kuantiti melebihi stok / had tetap disimpan tetapi CartQuote menanda `problem` (checkout disekat sehingga dibetulkan).
class UpdateCartItemJob
{
    private $customerId;
    private $cooperative;
    private $input;

    public function __construct($customerId, array $cooperative, array $input)
    {
        $this->customerId = (int) $customerId;
        $this->cooperative = $cooperative;
        $this->input = $input;
    }

    public function run()
    {
        $qty = Carts::validQty($this->input['qty'] ?? null);
        Database::beginTransaction();
        $cart = Carts::find($this->customerId, $this->cooperative, true);
        $itemId = (int) ($this->input['id'] ?? 0);
        if ($cart === null || !Database::fetchValue("SELECT 1 FROM cart_items WHERE cart_id = ? AND id = ?", [$cart['id'], $itemId])) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($qty === null) {
            throw Carts::qtyError();
        }
        Database::execute("UPDATE cart_items SET qty = ? WHERE cart_id = ? AND id = ?", [$qty, $cart['id'], $itemId]);
        Carts::touch($cart['id']);
        Database::commit();
        return Carts::quote($this->customerId, $this->cooperative);
    }
}
