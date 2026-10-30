<?php
// Tambah / sunting item jualan murah (vendor/clearance/item-save): produk milik koperasi, varian milik produk (null = semua saiz),
// harga jualan (sen) > 0 dan lebih rendah dari harga biasa, had kuantiti kosong / ≥ 1, produk + varian unik dalam satu jualan.
class SaveClearanceItemJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $sale = ClearanceSales::find($this->vendorId, $this->cooperativeId, (int) ($this->input['sale_id'] ?? 0));
        $id = (int) ($this->input['id'] ?? 0);
        if ($sale === null || ($id > 0 && ClearanceSales::findItem($this->vendorId, $sale['id'], $id) === null)) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate($sale, $id);
        $values = [$data['product_id'], $data['variant_id'], $data['sale_price'], $data['qty_limit']];
        try {
            if ($id > 0) {
                Database::execute(
                    "UPDATE clearance_items SET product_id = ?, variant_id = ?, sale_price = ?, qty_limit = ? WHERE id = ? AND vendor_id = ? AND clearance_sale_id = ?",
                    array_merge($values, [$id, $this->vendorId, (int) $sale['id']])
                );
            } else {
                $id = Database::insert(
                    "INSERT INTO clearance_items (vendor_id, clearance_sale_id, product_id, variant_id, sale_price, qty_limit) VALUES (?, ?, ?, ?, ?, ?)",
                    array_merge([$this->vendorId, (int) $sale['id']], $values)
                );
            }
        } catch (PDOException $exception) {
            // Perlumbaan: UNIQUE (jualan, produk, varian) menolak walaupun semakan awal lulus
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new ValidationException(t('error.check_form'), ['product_id' => t('clearance.duplicate_item')]);
            }
            throw $exception;
        }
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'clearance_save', 'clearance_sale', (int) $sale['id'],
            $sale['name']))->run();
        return ClearanceSales::items($this->vendorId, $this->cooperativeId, $sale['id'], $id)[0];
    }

    private function validate(array $sale, $id)
    {
        $v = new Validator($this->input);
        $v->required('product_id')->required('sale_price')->integer('sale_price', 1, 100000000)->integer('qty_limit', 1, 100000);
        $product = ProductCatalog::find($this->vendorId, $this->cooperativeId, (int) ($this->input['product_id'] ?? 0));
        if ($product === null) {
            $v->addError('product_id', t('error.required'));
            ValidationException::throwIfFails($v);
        }
        $variantId = (int) ($this->input['variant_id'] ?? 0);
        $variant = null;
        if ($variantId > 0) {
            $variant = Database::fetchOne(
                "SELECT id, price_delta FROM product_variants WHERE id = ? AND vendor_id = ? AND product_id = ?",
                [$variantId, $this->vendorId, (int) $product['id']]
            );
            if ($variant === null) {
                $v->addError('variant_id', t('error.not_found'));
            }
        }
        $regular = ClearanceSales::regularPrice($product, $variant);
        $salePrice = (int) ($this->input['sale_price'] ?? 0);
        if (!$v->fails() && $salePrice >= $regular) {
            $v->addError('sale_price', t('clearance.price_not_lower', ['amount' => Money::format($regular)]));
        }
        if (Database::fetchValue(
            "SELECT 1 FROM clearance_items WHERE vendor_id = ? AND clearance_sale_id = ? AND product_id = ? AND variant_id = ? AND id <> ?",
            [$this->vendorId, (int) $sale['id'], (int) $product['id'], $variantId, $id]
        )) {
            $v->addError('product_id', t('clearance.duplicate_item'));
        }
        ValidationException::throwIfFails($v);
        $limit = $this->input['qty_limit'] ?? null;
        return [
            'product_id' => (int) $product['id'],
            'variant_id' => $variantId,
            'sale_price' => $salePrice,
            'qty_limit'  => ($limit === null || $limit === '') ? null : (int) $limit,
        ];
    }
}
