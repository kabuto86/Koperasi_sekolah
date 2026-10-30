<?php
// Padam berita (vendor/news/delete). E-mel yang sudah dalam baris gilir tidak ditarik balik.
class DeleteNewsJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $id;

    public function __construct($vendorId, $cooperativeId, $userId, $id)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->id = (int) $id;
    }

    public function run()
    {
        $post = NewsPosts::find($this->vendorId, $this->cooperativeId, $this->id);
        if ($post === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        Database::execute("DELETE FROM news_posts WHERE id = ? AND vendor_id = ? AND cooperative_id = ?", [$this->id, $this->vendorId, $this->cooperativeId]);
        Uploader::delete($post['image_path']);
        (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'news_delete', 'news_post', $this->id, $post['title']))->run();
        return ['id' => $this->id];
    }
}
