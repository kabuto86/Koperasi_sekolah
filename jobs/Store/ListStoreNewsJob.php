<?php
// Berita koperasi untuk ibu bapa (modul news — PELAN 18.13 store/news/list): diterbitkan & aktif sahaja, terbaru dahulu.
class ListStoreNewsJob
{
    private $cooperative;
    private $input;

    public function __construct(array $cooperative, array $input)
    {
        $this->cooperative = $cooperative;
        $this->input = $input;
    }

    public function run()
    {
        StoreSchool::requireModule($this->cooperative, 'news');
        list($page, $perPage, $offset) = Pagination::from($this->input);
        $args = [(int) $this->cooperative['vendor_id'], (int) $this->cooperative['id']];
        $where = "WHERE vendor_id = ? AND cooperative_id = ? AND is_active = 1 AND published_at IS NOT NULL AND published_at <= NOW()";
        $total = Database::fetchValue("SELECT COUNT(*) FROM news_posts $where", $args);
        $rows = Database::fetchAll(
            "SELECT id, title, body, image_path, published_at FROM news_posts $where ORDER BY published_at DESC, id DESC LIMIT $perPage OFFSET $offset",
            $args
        );
        $items = array_map(function ($row) {
            return [
                'id'           => (int) $row['id'],
                'title'        => $row['title'],
                'body'         => $row['body'],
                'image_url'    => Uploader::url($row['image_path']),
                'published_at' => IsoDate::format($row['published_at']),
            ];
        }, $rows);
        return Pagination::result($items, $total, $page, $perPage);
    }
}
