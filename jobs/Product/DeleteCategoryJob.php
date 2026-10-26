<?php
// Padam kategori { id } — ditolak jika masih ada produk (pindahkan atau nyahaktifkan dahulu).
class DeleteCategoryJob
{
    private $vendorId;
    private $cooperativeId;
    private $categoryId;

    public function __construct($vendorId, $cooperativeId, $categoryId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->categoryId = (int) $categoryId;
    }

    public function run()
    {
        $category = ListCategoriesJob::find($this->vendorId, $this->cooperativeId, $this->categoryId);
        if ($category === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        if ($category['product_count'] > 0) {
            throw new ValidationException(t('category.delete_in_use'));
        }
        Database::execute(
            "DELETE FROM categories WHERE vendor_id = ? AND cooperative_id = ? AND id = ?",
            [$this->vendorId, $this->cooperativeId, $this->categoryId]
        );
        return ['id' => $this->categoryId];
    }
}
