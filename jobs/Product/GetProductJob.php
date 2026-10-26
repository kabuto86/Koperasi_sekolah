<?php
// Butiran produk + varian + darjah + terjemahan (PELAN 18.8). null = tiada / koperasi atau vendor lain.
class GetProductJob
{
    private $vendorId;
    private $cooperativeId;
    private $productId;

    public function __construct($vendorId, $cooperativeId, $productId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->productId = (int) $productId;
    }

    public function run()
    {
        $row = ProductCatalog::find($this->vendorId, $this->cooperativeId, $this->productId);
        if ($row === null) {
            return null;
        }
        $grades = ProductCatalog::gradeLevels($this->vendorId, [$row['id']]);
        return [
            'id'            => (int) $row['id'],
            'cooperative_id' => (int) $row['cooperative_id'],
            'category_id'   => $row['category_id'] === null ? null : (int) $row['category_id'],
            'name'          => $row['name'],
            'description'   => (string) $row['description'],
            'sku'           => (string) $row['sku'],
            'barcode'       => (string) $row['barcode'],
            'price'         => (int) $row['price'],
            'cost_price'    => (int) $row['cost_price'],
            'unit'          => $row['unit'],
            'image_url'     => Uploader::url($row['image_path']),
            'max_per_order' => $row['max_per_order'] === null ? null : (int) $row['max_per_order'],
            'has_variants'  => (bool) $row['has_variants'],
            'is_active'     => (bool) $row['is_active'],
            'sort_order'    => (int) $row['sort_order'],
            'grade_levels'  => $grades[(int) $row['id']] ?? [],
            'variants'      => ProductCatalog::variants($this->vendorId, $row['id']),
            'translations'  => $this->translations(),
        ];
    }

    // { en: { name, description }, … } — bahasa tanpa terjemahan tidak dihantar
    private function translations()
    {
        $rows = Database::fetchAll(
            "SELECT language_code, name, description FROM product_translations WHERE vendor_id = ? AND product_id = ?",
            [$this->vendorId, $this->productId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['language_code']] = ['name' => $row['name'], 'description' => (string) $row['description']];
        }
        return (object) $result;
    }
}
